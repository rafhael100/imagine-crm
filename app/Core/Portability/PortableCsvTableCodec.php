<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-210: reusable bounded in-memory CSV codec, no customer I/O.
 *
 * This is a data formatting primitive, NOT an IAM/export/import permission.
 * decode() yields untrusted cell strings; encode() conservatively refuses
 * formula-like prefixes rather than guessing an Excel-safe escaping policy.
 * Delimiter and line-ending choices are explicit; output is canonical CRLF.
 */
final class PortableCsvTableCodec
{
    private const MAX_BYTES = 262144;
    private const MAX_ROWS = 100;
    private const MAX_COLUMNS = 32;
    private const MAX_CELLS = 3200;
    private const MAX_CELL_BYTES = 4096;

    /** @return list<list<string>> Untrusted in-memory values, never verified content */
    public function decode(mixed $csv, mixed $delimiter = 'comma'): array
    {
        $sep = self::delimiter($delimiter);
        if (!is_string($csv) || $csv === '' || strlen($csv) > self::MAX_BYTES
            || str_contains($csv, "\0") || preg_match('//u', $csv) !== 1
            || str_starts_with($csv, "\xEF\xBB\xBF")) {
            self::invalid();
        }

        $rows = [];
        $row = [];
        $cell = '';
        $state = 'start';
        $cells = 0;
        $n = strlen($csv);
        $lineEnding = null;
        $endsWithRecordTerminator = false;

        $finishCell = static function () use (&$row, &$cell, &$cells): void {
            if (strlen($cell) > self::MAX_CELL_BYTES
                || count($row) >= self::MAX_COLUMNS
                || ++$cells > self::MAX_CELLS) {
                self::invalid();
            }
            $row[] = $cell;
            $cell = '';
        };
        $finishRow = static function () use (&$rows, &$row): void {
            if (count($rows) >= self::MAX_ROWS
                || ($rows !== [] && count($row) !== count($rows[0]))) {
                self::invalid();
            }
            $rows[] = $row;
            $row = [];
        };

        for ($i = 0; $i < $n; ++$i) {
            $char = $csv[$i];

            if ($state === 'quoted') {
                if ($char === '"') {
                    if ($i + 1 < $n && $csv[$i + 1] === '"') {
                        $cell .= '"';
                        ++$i;
                    } else {
                        $state = 'after_quote';
                    }
                } else {
                    $cell .= $char;
                }
                if (strlen($cell) > self::MAX_CELL_BYTES) {
                    self::invalid();
                }
                continue;
            }

            if ($char === $sep) {
                $finishCell();
                $state = 'start';
                $endsWithRecordTerminator = false;
                continue;
            }
            if ($char === "\r" || $char === "\n") {
                $term = $char === "\r" ? 'crlf' : 'lf';
                if ($char === "\r") {
                    if ($i + 1 >= $n || $csv[$i + 1] !== "\n") {
                        self::invalid();
                    }
                    ++$i;
                }
                if ($lineEnding !== null && $lineEnding !== $term) {
                    self::invalid();
                }
                $lineEnding = $term;
                $finishCell();
                $finishRow();
                $state = 'start';
                $endsWithRecordTerminator = true;
                continue;
            }
            if ($state === 'after_quote') {
                self::invalid();
            }
            if ($char === '"') {
                if ($state !== 'start') {
                    self::invalid();
                }
                $state = 'quoted';
            } else {
                $cell .= $char;
                $state = 'plain';
                if (strlen($cell) > self::MAX_CELL_BYTES) {
                    self::invalid();
                }
            }
            $endsWithRecordTerminator = false;
        }

        if ($state === 'quoted') {
            self::invalid();
        }
        if (!$endsWithRecordTerminator) {
            $finishCell();
            $finishRow();
        }

        return $rows;
    }

    /** @param mixed $rows Rectangular list of lists of fictional UTF-8 values */
    public function encode(mixed $rows, mixed $delimiter = 'comma'): string
    {
        $sep = self::delimiter($delimiter);
        if (!is_array($rows) || !array_is_list($rows) || $rows === []
            || count($rows) > self::MAX_ROWS) {
            self::invalid();
        }

        $result = '';
        $width = null;
        $cells = 0;
        foreach ($rows as $row) {
            if (!is_array($row) || !array_is_list($row) || $row === []
                || count($row) > self::MAX_COLUMNS) {
                self::invalid();
            }
            $width ??= count($row);
            if (count($row) !== $width) {
                self::invalid();
            }

            $parts = [];
            foreach ($row as $value) {
                if (!is_string($value) || strlen($value) > self::MAX_CELL_BYTES
                    || str_contains($value, "\0") || preg_match('//u', $value) !== 1
                    || ++$cells > self::MAX_CELLS
                    || self::hasFormulaPrefix($value)) {
                    self::invalid();
                }

                // Explicitly quote blank cells to distinguish them from
                // an empty input stream. Quote delimiters, CR/LF and quotes.
                $quoted = $value === '' || strpbrk($value, $sep."\"\r\n") !== false;
                $parts[] = $quoted
                    ? '"'.str_replace('"', '""', $value).'"'
                    : $value;
            }
            $result .= implode($sep, $parts)."\r\n";
            if (strlen($result) > self::MAX_BYTES) {
                self::invalid();
            }
        }

        return $result;
    }

    private static function delimiter(mixed $delimiter): string
    {
        if (!is_string($delimiter) || !in_array($delimiter, ['comma', 'semicolon'], true)) {
            self::invalid();
        }
        return $delimiter === 'comma' ? ',' : ';';
    }

    private static function hasFormulaPrefix(string $value): bool
    {
        // Strict refusal, not universal formula neutralization. Includes
        // whitespace, BOM, NBSP, Unicode format controls and fullwidth
        // compatibility symbols. May refuse legitimate negative numbers.
        return preg_match(
            '/\A(?:[\x01-\x20\x7F]|\x{00A0}|[\x{200B}-\x{200F}]|[\x{202A}-\x{202E}]|[\x{2060}-\x{2069}]|\x{FEFF})*(?:[=+\-@]|[＝＋－＠]|\x27[=+\-@＝＋－＠])/u',
            $value,
        ) === 1;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Tabela CSV em memoria invalida.');
    }
}
