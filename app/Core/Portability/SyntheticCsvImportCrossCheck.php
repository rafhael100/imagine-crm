<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-209 / IN-118: connect the fictional CSV structural sample to the
 * existing fictional import/mapping preflight, without enabling import.
 *
 * All contracts are validated before any comparison. The sample is not a
 * customer file; header interpretation and row counts are caller claims.
 */
final class SyntheticCsvImportCrossCheck
{
    /**
     * @param mixed $csv Tiny fictional in-memory CSV, never a file
     * @param mixed $delimiter 'comma' or 'semicolon'
     * @param mixed $headerClaimed Boolean: first record is a fictional header
     * @param mixed $operation Strict F3-198 operation descriptor
     * @param mixed $mapping Strict F3-202 mapping metadata descriptor
     * @return array<string,mixed> Fixed warnings and aggregate counts only
     */
    public function inspect(
        mixed $csv,
        mixed $delimiter,
        mixed $headerClaimed,
        mixed $operation,
        mixed $mapping,
    ): array {
        if (!is_bool($headerClaimed)) {
            self::invalid();
        }
        try {
            $syntax = (new SyntheticCsvStructureInspector())->inspect($csv, $delimiter);
            $preflight = (new SyntheticImportReadinessInspector())->inspect($operation, $mapping);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        // A CSV sample must never silently validate an XLSX workbook.
        if ($preflight['claimed_import_format'] !== 'import_csv') {
            self::invalid();
        }

        $records = $syntax['synthetic_record_count'];
        $sampleRows = max(0, $records - ($headerClaimed ? 1 : 0));
        $warnings = [];

        if ($headerClaimed && $records === 0) {
            $warnings[] = 'HEADER_CLAIMED_WITHOUT_RECORD';
        }
        if ($syntax['synthetic_irregular_record_count'] > 0) {
            $warnings[] = 'SAMPLE_HAS_IRREGULAR_RECORD_WIDTH';
        }
        if ($syntax['synthetic_expected_column_count']
            !== $preflight['declared_source_column_count']) {
            $warnings[] = 'SAMPLE_COLUMN_COUNT_DISAGREES_WITH_MAPPING';
        }
        if ($sampleRows !== $preflight['declared_row_count']) {
            $warnings[] = 'SAMPLE_ROW_COUNT_DISAGREES_WITH_MAPPING';
        }
        if ($syntax['synthetic_flagged_formula_cell_count'] > 0) {
            // Not comparable to full-file counts: the small sample may
            // contain the header and multiline/formatting warnings too.
            $warnings[] = 'SAMPLE_CELL_RISK_REQUIRES_SEPARATE_REVIEW';
        }
        foreach ($syntax['warning_codes'] as $code) {
            $warnings[] = 'CSV_'.$code;
        }
        foreach ($preflight['warning_codes'] as $code) {
            $warnings[] = 'IMPORT_'.$code;
        }

        return [
            'claimed_import_format' => 'import_csv',
            'declared_delimiter' => $syntax['declared_delimiter'],
            'header_record_claimed' => $headerClaimed,
            'synthetic_sample_record_count' => $records,
            'synthetic_sample_data_row_count' => $sampleRows,
            'synthetic_sample_column_count' => $syntax['synthetic_expected_column_count'],
            'synthetic_sample_risk_flagged_cells' => $syntax['synthetic_flagged_formula_cell_count'],
            'declared_mapping_row_count' => $preflight['declared_row_count'],
            'declared_mapping_column_count' => $preflight['declared_source_column_count'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'unfulfilled_gate_codes' => $preflight['unfulfilled_gate_codes'],
            'real_file_verified' => false,
            'full_file_counts_verified' => false,
            'mapping_verified' => false,
            'header_meaning_verified' => false,
            'formula_safety_verified' => false,
            'consent_evidence_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_import' => false,
            'can_convert_xlsx' => false,
            'can_merge' => false,
            'can_confirm_consent' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Revisao cruzada CSV sintetica invalida.');
    }
}
