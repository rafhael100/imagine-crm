<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-208 / IN-118: deliberately small, strictly bounded, in-memory CSV
 * structural rehearsal. NO file, network, storage, tenant, auth or output
 * containing source cells. No approved format/encoding/client policy is implied.
 *
 * The only allowed input is fictional UTF-8 text; it is not an import endpoint.
 */
final class SyntheticCsvStructureInspector
{
    private const MAX_BYTES = 8192;
    private const MAX_ROWS = 20;
    private const MAX_COLUMNS = 12;
    private const MAX_CELLS = 20;
    private const MAX_CELL_BYTES = 512;

    /** @return array<string,mixed> Aggregate, fixed-code warnings only */
    public function inspect(mixed $csv, mixed $delimiter = 'comma'): array
    {
        if (!is_string($csv) || strlen($csv) > self::MAX_BYTES
            || str_contains($csv, "\0") || preg_match('//u', $csv) !== 1
            || !is_string($delimiter) || !in_array($delimiter, ['comma', 'semicolon'], true)) {
            self::invalid();
        }

        $sep = $delimiter === 'comma' ? ',' : ';';
        $bom = str_starts_with($csv, "\xEF\xBB\xBF");
        $data = $bom ? substr($csv, 3) : $csv;
        $n = strlen($data);
        $rows = [];
        $row = [];
        $cell = '';
        $state = 'start'; // start | plain | quoted | after_quote
        $quotedCells = 0;
        $crlf = 0;
        $lf = 0;
        $cells = 0;
        $lastWasRecordTerminator = false;

        $endCell = static function () use (&$row, &$cell, &$cells): void {
            if (strlen($cell) > self::MAX_CELL_BYTES || ++$cells > self::MAX_CELLS
                || count($row) >= self::MAX_COLUMNS) {
                self::invalid();
            }
            $row[] = $cell;
            $cell = '';
        };
        $endRow = static function () use (&$rows, &$row): void {
            if (count($rows) >= self::MAX_ROWS) {
                self::invalid();
            }
            $rows[] = $row;
            $row = [];
        };

        for ($i = 0; $i < $n; ++$i) {
            $char = $data[$i];

            if ($state === 'quoted') {
                if ($char === '"') {
                    if ($i + 1 < $n && $data[$i + 1] === '"') {
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
                $endCell();
                $state = 'start';
                $lastWasRecordTerminator = false;
                continue;
            }

            if ($char === "\n" || $char === "\r") {
                if ($char === "\r") {
                    if ($i + 1 >= $n || $data[$i + 1] !== "\n") {
                        self::invalid(); // no bare CR outside quoted cells
                    }
                    ++$i;
                    ++$crlf;
                } else {
                    ++$lf;
                }
                $endCell();
                $endRow();
                $state = 'start';
                $lastWasRecordTerminator = true;
                continue;
            }

            if ($state === 'after_quote') {
                // No arbitrary whitespace / payload after a closing quote.
                self::invalid();
            }

            if ($char === '"') {
                if ($state !== 'start') {
                    self::invalid();
                }
                $state = 'quoted';
                ++$quotedCells;
                $lastWasRecordTerminator = false;
                continue;
            }

            $state = 'plain';
            $lastWasRecordTerminator = false;
            $cell .= $char;
            if (strlen($cell) > self::MAX_CELL_BYTES) {
                self::invalid();
            }
        }

        if ($state === 'quoted') {
            self::invalid(); // unterminated quoted field
        }
        if ($n > 0 && !$lastWasRecordTerminator) {
            $endCell();
            $endRow();
        }

        $expectedWidth = count($rows[0] ?? []);
        $unevenRows = 0;
        $emptyRows = 0;
        $flat = [];
        foreach ($rows as $values) {
            if (count($values) !== $expectedWidth) {
                ++$unevenRows;
            }
            if (count($values) === 1 && $values[0] === '') {
                ++$emptyRows;
            }
            foreach ($values as $value) {
                $flat[] = $value;
            }
        }

        // Existing F3-205 risks are deliberately reused, never silently
        // replaced by CSV quoting or an apostrophe-escape assumption.
        $formula = (new SyntheticSpreadsheetFormulaSignalInspector())->inspect($flat);
        $warnings = [];
        if ($rows === []) {
            $warnings[] = 'NO_FICTIONAL_RECORDS';
        }
        if ($bom) {
            $warnings[] = 'UTF8_BOM_REVIEW_REQUIRED';
        }
        if ($crlf > 0 && $lf > 0) {
            $warnings[] = 'MIXED_RECORD_LINE_ENDINGS';
        }
        if ($unevenRows > 0) {
            $warnings[] = 'INCONSISTENT_COLUMN_COUNT';
        }
        if ($emptyRows > 0) {
            $warnings[] = 'EMPTY_RECORD_REVIEW_REQUIRED';
        }
        foreach ($formula['warning_codes'] as $code) {
            if ($code !== 'NO_FICTIONAL_CELLS_INSPECTED') {
                $warnings[] = 'CELL_'.$code;
            }
        }

        return [
            'declared_delimiter' => $delimiter,
            'synthetic_record_count' => count($rows),
            'synthetic_total_cell_count' => count($flat),
            'synthetic_expected_column_count' => $expectedWidth,
            'synthetic_irregular_record_count' => $unevenRows,
            'synthetic_empty_record_count' => $emptyRows,
            'synthetic_quoted_field_count' => $quotedCells,
            'synthetic_crlf_terminator_count' => $crlf,
            'synthetic_lf_terminator_count' => $lf,
            'synthetic_flagged_formula_cell_count' => $formula['flagged_fictional_cell_count'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'real_file_verified' => false,
            'production_csv_policy_verified' => false,
            'spreadsheet_client_behavior_verified' => false,
            'customer_content_verified' => false,
            'schema_verified' => false,
            'consent_evidence_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_import' => false,
            'can_export' => false,
            'can_download' => false,
            'can_sanitize' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Estrutura CSV sintetica invalida.');
    }
}
