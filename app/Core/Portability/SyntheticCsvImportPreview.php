<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-53: bounded, offline CSV *preview* for synthetic fixtures only.
 *
 * Parses RFC-4180-style comma/quote/CRLF or LF text in memory. It does not
 * ingest files/uploads, store cells, infer ownership, authenticate the tenant,
 * deduplicate contacts or authorize an import. No row/cell data leaves inspect.
 * F3-55 makes this the single canonical decoder for legacy preview + mapped preview.
 */
final class SyntheticCsvImportPreview
{
    private const MAX_BYTES = 32768;
    private const MAX_ROWS_WITH_HEADER = 51;
    private const MAX_COLUMNS = 8;
    private const MAX_CELL_BYTES = 512;

    public function inspect(string $csv): SyntheticImportPreviewSummary
    {
        return $this->decode($csv)[0];
    }

    /**
     * Parse once, validate a position-only mapping without leaking headers.
     *
     * @param array<mixed> $targetLabels
     * @param array<mixed> $mappingPairs
     */
    public function inspectMapped(
        string $csv,
        array $targetLabels,
        array $mappingPairs,
    ): SyntheticImportMappedPreviewSummary {
        [$preview, $sourceLabels] = $this->decode($csv);

        try {
            $mapping = (new SyntheticColumnMappingInspector())->assess(
                $sourceLabels, $targetLabels, $mappingPairs,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        return new SyntheticImportMappedPreviewSummary($preview, $mapping);
    }

    /** @return array{SyntheticImportPreviewSummary, list<string>} */
    private function decode(string $csv): array
    {
        if ($csv === '' || strlen($csv) > self::MAX_BYTES
            || preg_match('//u', $csv) !== 1 || str_contains($csv, "\0")) {
            self::invalid();
        }

        // A single UTF-8 BOM before the header is common in exported CSV.
        // It must not enter the first column label or bypass formula checks.
        if (str_starts_with($csv, "\xEF\xBB\xBF")) {
            $csv = substr($csv, 3);
        }
        if ($csv === '') {
            self::invalid();
        }

        $rows = [];
        $row = [];
        $cell = '';
        $mode = 'start'; // start | bare | quoted | closed
        $endedByRowSeparator = false;
        $rowHasRepresentation = false; // Distinguish blank line from quoted "".
        $length = strlen($csv);

        for ($i = 0; $i < $length; ++$i) {
            $char = $csv[$i];
            $endedByRowSeparator = false;

            if ($char === '"') {
                $rowHasRepresentation = true;
                if ($mode === 'start') {
                    $mode = 'quoted';
                } elseif ($mode === 'quoted') {
                    if ($i + 1 < $length && $csv[$i + 1] === '"') {
                        $cell .= '"';
                        ++$i;
                    } else {
                        $mode = 'closed';
                    }
                } else {
                    self::invalid();
                }
            } elseif ($char === ',' && $mode !== 'quoted') {
                $rowHasRepresentation = true;
                self::finishCell($row, $cell);
                $mode = 'start';
            } elseif (($char === "\r" || $char === "\n") && $mode !== 'quoted') {
                if (!$rowHasRepresentation) {
                    self::invalid(); // Legacy fgetcsv rejected empty physical rows.
                }
                if ($char === "\r") {
                    if ($i + 1 >= $length || $csv[$i + 1] !== "\n") {
                        self::invalid(); // Reject ambiguous CR-only line breaks.
                    }
                    ++$i;
                }
                self::finishCell($row, $cell);
                self::finishRow($rows, $row);
                $mode = 'start';
                $rowHasRepresentation = false;
                $endedByRowSeparator = true;
            } else {
                $rowHasRepresentation = true;
                if ($mode === 'closed') {
                    self::invalid(); // No characters allowed after closing quote.
                }
                $cell .= $char;
                if ($mode === 'start') {
                    $mode = 'bare';
                }
            }

            if (strlen($cell) > self::MAX_CELL_BYTES) {
                self::invalid();
            }
        }

        if ($mode === 'quoted') {
            self::invalid(); // Unterminated quoted field.
        }

        if (!$endedByRowSeparator) {
            self::finishCell($row, $cell);
            self::finishRow($rows, $row);
        }

        $columns = array_shift($rows);
        if (!is_array($columns)) {
            self::invalid();
        }

        try {
            $preview = (new SyntheticImportPreviewInspector())->inspect($columns, $rows);
        } catch (InvalidArgumentException) {
            self::invalid(); // Generic error; no original header or cell.
        }
        return [$preview, $columns];
    }

    /** @param list<string> $row */
    private static function finishCell(array &$row, string &$cell): void
    {
        $row[] = $cell;
        $cell = '';
        if (count($row) > self::MAX_COLUMNS) {
            self::invalid();
        }
    }

    /** @param list<list<string>> $rows
     *  @param list<string> $row
     */
    private static function finishRow(array &$rows, array &$row): void
    {
        $rows[] = $row;
        $row = [];
        if (count($rows) > self::MAX_ROWS_WITH_HEADER) {
            self::invalid();
        }
    }

    private static function invalid(): never
    {
        // Never include file content, PII, a cell, header or byte offset.
        throw new InvalidArgumentException('Estrutura CSV de pre-visualizacao invalida.');
    }
}
