<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-213 / IN-118: content-aware, aggregate-only *local* CSV import preview.
 *
 * Uses working F3-210/211/212 core; never reads an actual file, returns a
 * row/field/customer value, infers tenant authority, or enables persistence.
 * It reports whether the strict canonical CSV writer would refuse selected
 * sample values, NOT whether a spreadsheet client is necessarily safe.
 */
final class PortableCsvImportPreview
{
    /**
     * @param mixed $csv Untrusted bounded in-memory string
     * @param mixed $delimiter 'comma' or 'semicolon'
     * @param mixed $bindings Explicit source indices and candidate targets
     * @param mixed $hasHeader Boolean caller claim
     * @return array<string,mixed> Counts and fixed warning codes, no cell data
     */
    public function inspect(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
    ): array {
        try {
            $codec = new PortableCsvTableCodec();
            $source = $codec->decode($csv, $delimiter);
            $mapped = (new PortableCsvFieldMapping())->map(
                $source, $bindings, $hasHeader,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $rawRows = $hasHeader ? array_slice($source, 1) : $source;
        $sourceRefused = 0;
        $mappedRefused = 0;
        $emptyMapped = 0;

        // Apply the *same* strict export-cell acceptance rule as the real
        // in-memory codec; don't invent a second imperfect regex policy.
        foreach ($rawRows as $row) {
            foreach ($row as $value) {
                if (self::wouldRefuseCanonicalOutput($codec, $value)) {
                    ++$sourceRefused;
                }
            }
        }
        foreach ($mapped['untrusted_mapped_rows'] as $row) {
            foreach ($row as $value) {
                if ($value === '') {
                    ++$emptyMapped;
                }
                if (self::wouldRefuseCanonicalOutput($codec, $value)) {
                    ++$mappedRefused;
                }
            }
        }

        $omitted = $mapped['source_column_count'] - $mapped['mapped_field_count'];
        $warnings = ['EXPORT_AND_IMPORT_AUTHORITY_NOT_VERIFIED'];
        if ($mapped['data_row_count'] === 0) {
            $warnings[] = 'NO_DATA_RECORDS_IN_LOCAL_SAMPLE';
        }
        if ($hasHeader) {
            $warnings[] = 'HEADER_MEANING_NOT_VERIFIED';
        }
        if ($omitted > 0) {
            $warnings[] = 'SOURCE_COLUMNS_OMITTED_BY_EXPLICIT_MAPPING';
        }
        if ($sourceRefused > 0) {
            $warnings[] = 'SOURCE_CONTAINS_CELLS_REFUSED_BY_STRICT_CSV_WRITER';
        }
        if ($mappedRefused > 0) {
            $warnings[] = 'MAPPED_VALUES_CONTAIN_CELLS_REFUSED_BY_STRICT_CSV_WRITER';
        }
        if ($emptyMapped > 0) {
            $warnings[] = 'MAPPED_FIELDS_HAVE_EMPTY_VALUES';
        }
        if ($mapped['consent_field_present_claimed']) {
            $warnings[] = 'CONSENT_COLUMN_IS_NOT_LEGAL_CONSENT';
        }

        return [
            'declared_delimiter' => $delimiter,
            'header_claimed' => $hasHeader,
            'local_source_column_count' => $mapped['source_column_count'],
            'local_selected_field_count' => $mapped['mapped_field_count'],
            'local_omitted_column_count' => $omitted,
            'local_data_row_count' => $mapped['data_row_count'],
            'local_empty_mapped_value_count' => $emptyMapped,
            'local_source_formula_refused_cell_count' => $sourceRefused,
            'local_mapped_formula_refused_cell_count' => $mappedRefused,
            'consent_column_present_claimed' => $mapped['consent_field_present_claimed'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'unfulfilled_gate_codes' => [
                'VERIFY_REAL_TENANT_AND_FIELD_PERMISSIONS',
                'VERIFY_FILE_FORMAT_ENCODING_AND_ORIGIN',
                'VERIFY_LGPD_CONSENT_EVIDENCE',
                'RESOLVE_DUPLICATE_AND_MAPPING_CONFLICTS',
                'REVIEW_SPREADSHEET_CLIENT_FORMULA_POLICY',
                'REQUIRE_AUDITED_AUTHORIZED_IMPORT',
            ],
            'real_file_verified' => false,
            'schema_verified' => false,
            'spreadsheet_client_behavior_verified' => false,
            'consent_evidence_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_import' => false,
            'can_export' => false,
            'can_merge' => false,
            'can_confirm_consent' => false,
            'can_download' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function wouldRefuseCanonicalOutput(
        PortableCsvTableCodec $codec,
        string $value,
    ): bool {
        try {
            // A one-cell string conversion, exclusively in memory. Inputs
            // have already passed decode UTF-8, NUL and size validation.
            $codec->encode([[$value]]);
            return false;
        } catch (InvalidArgumentException) {
            return true;
        }
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Previa CSV local invalida.');
    }
}
