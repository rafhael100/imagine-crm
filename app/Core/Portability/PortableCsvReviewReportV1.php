<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;
use JsonException;

/**
 * F3-221 / IN-118: immutable-on-the-wire, aggregate-only portable CSV review.
 *
 * Consumes the working F3-219 in-memory inspectors but exposes only an explicit
 * versioned whitelist of count/flag/code fields. No file, data, identity, row,
 * header, tenant, hash, timestamp, or user-supplied field name is serialized.
 * No permission to import/export/deduplicate is inferred.
 */
final class PortableCsvReviewReportV1
{
    public const SCHEMA_VERSION = 1;
    public const MAX_JSON_BYTES = 8192;

    private const WARNINGS = [
        'EXPORT_AND_IMPORT_AUTHORITY_NOT_VERIFIED',
        'NO_DATA_RECORDS_IN_LOCAL_SAMPLE',
        'HEADER_MEANING_NOT_VERIFIED',
        'SOURCE_COLUMNS_OMITTED_BY_EXPLICIT_MAPPING',
        'SOURCE_CONTAINS_CELLS_REFUSED_BY_STRICT_CSV_WRITER',
        'MAPPED_VALUES_CONTAIN_CELLS_REFUSED_BY_STRICT_CSV_WRITER',
        'MAPPED_FIELDS_HAVE_EMPTY_VALUES',
        'CONSENT_COLUMN_IS_NOT_LEGAL_CONSENT',
        'HEADER_AUTHORITY_AND_SCHEMA_NOT_VERIFIED',
        'NO_HEADER_DECLARED',
        'EMPTY_CLAIMED_HEADER',
        'DUPLICATE_EXACT_CLAIMED_HEADER',
        'CLAIMED_HEADER_REFUSED_BY_STRICT_CSV_WRITER',
        'ROW_IDENTITY_AND_DUPLICATE_POLICY_NOT_VERIFIED',
        'EXACT_SOURCE_RECORDS_REPEATED_IN_LOCAL_SAMPLE',
        'DISTINCT_SOURCE_RECORDS_COLLAPSE_AFTER_COLUMN_MAPPING',
        'CLAIMED_REQUIRED_FIELD_POLICY_NOT_VERIFIED',
        'CLAIMED_REQUIRED_FIELDS_NOT_MAPPED',
        'EMPTY_VALUES_IN_CLAIMED_REQUIRED_FIELDS',
    ];

    private const GATES = [
        'VERIFY_REAL_TENANT_AND_FIELD_PERMISSIONS',
        'VERIFY_FILE_FORMAT_ENCODING_AND_ORIGIN',
        'VERIFY_LGPD_CONSENT_EVIDENCE',
        'RESOLVE_DUPLICATE_AND_MAPPING_CONFLICTS',
        'REVIEW_SPREADSHEET_CLIENT_FORMULA_POLICY',
        'REQUIRE_AUDITED_AUTHORIZED_IMPORT',
    ];

    /**
     * @return array<string,mixed> Fixed V1 contract for offline rendering only
     */
    public function build(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
        mixed $requiredFields = null,
    ): array {
        try {
            $review = (new PortableCsvImportReadinessSummary())->inspect(
                $csv, $delimiter, $bindings, $hasHeader, $requiredFields,
            );

            $rows = self::integer($review, 'local_data_row_count', 100);
            $columns = self::integer($review, 'local_source_column_count', 32);
            $selected = self::integer($review, 'local_selected_field_count', 7);
            $omitted = self::integer($review, 'local_omitted_column_count', 32);
            if ($columns < 1 || $selected < 1 || $selected > $columns
                || $omitted !== $columns - $selected
                || !is_bool($hasHeader) || $review['header_claimed'] !== $hasHeader
                || !in_array($delimiter, ['comma', 'semicolon'], true)) {
                self::invalid();
            }

            $counts = [
                'source_columns' => $columns,
                'selected_fields' => $selected,
                'omitted_columns' => $omitted,
                'data_rows' => $rows,
                'empty_mapped_values' => self::integer($review, 'local_empty_mapped_value_count', $rows * $selected),
                'source_formula_refusals' => self::integer($review, 'local_source_formula_refused_cell_count', $rows * $columns),
                'mapped_formula_refusals' => self::integer($review, 'local_mapped_formula_refused_cell_count', $rows * $selected),
                'empty_headers' => self::integer($review, 'local_empty_header_count', $columns),
                'repeated_headers_exact' => self::integer($review, 'local_duplicate_exact_header_count', $columns - 1),
                'header_formula_refusals' => self::integer($review, 'local_header_refused_cell_count', $columns),
                'repeated_source_rows_exact' => self::integer($review, 'local_exact_source_repeated_row_count', max(0, $rows - 1)),
                'repeated_mapped_rows_exact' => self::integer($review, 'local_exact_mapped_repeated_row_count', max(0, $rows - 1)),
                'projection_collision_extra_rows' => self::integer($review, 'local_projection_collision_extra_row_count', max(0, $rows - 1)),
            ];
            if ($counts['repeated_mapped_rows_exact'] !== $counts['repeated_source_rows_exact']
                + $counts['projection_collision_extra_rows']
                || $counts['mapped_formula_refusals'] > $counts['source_formula_refusals']) {
                self::invalid();
            }

            $requiredPresent = $requiredFields !== null;
            if ($requiredPresent) {
                $requiredCount = self::integer($review, 'local_claimed_required_field_count', 7);
                $unmappedRequired = self::integer($review, 'local_unmapped_required_field_count', 7);
                $requiredEmpty = self::integer($review, 'local_empty_required_value_count', $rows * $selected);
                $requiredRows = self::integer($review, 'local_rows_with_empty_required_value_count', $rows);
                if ($requiredCount < 1 || $unmappedRequired > $requiredCount
                    || $requiredEmpty > $rows * ($requiredCount - $unmappedRequired)
                    || $requiredRows > $requiredEmpty) {
                    self::invalid();
                }
                $counts['claimed_required_fields'] = $requiredCount;
                $counts['unmapped_claimed_required_fields'] = $unmappedRequired;
                $counts['empty_claimed_required_values'] = $requiredEmpty;
                $counts['rows_with_empty_claimed_required_values'] = $requiredRows;
            }

            $warnings = self::codes($review['warning_codes'] ?? null, self::WARNINGS);
            $gates = self::codes($review['unfulfilled_gate_codes'] ?? null, self::GATES);
            if (self::integer($review, 'warning_count', count(self::WARNINGS)) !== count($warnings)
                || $gates !== self::GATES
                || ($requiredPresent && ($review['required_policy_verified'] ?? null) !== false)
                || (!$requiredPresent && array_key_exists('required_policy_verified', $review))) {
                self::invalid();
            }

            foreach ([
                'informational_only' => true,
                'header_meaning_verified' => false,
                'tenant_scope_verified' => false,
                'authorization_verified' => false,
                'consent_evidence_verified' => false,
                'duplicate_person_identity_verified' => false,
                'can_import' => false,
                'can_export' => false,
                'can_merge' => false,
                'can_deduplicate' => false,
                'can_persist' => false,
            ] as $key => $expected) {
                if (($review[$key] ?? null) !== $expected) {
                    self::invalid();
                }
            }

            return [
                'protocol' => 'imagine.crm.portable_csv_review',
                'schema_version' => self::SCHEMA_VERSION,
                'status' => 'REVIEW_ONLY_DENIED',
                'declared_delimiter' => $delimiter,
                'header_claimed' => $hasHeader,
                'required_field_check_performed' => $requiredPresent,
                'counts' => $counts,
                'warning_codes' => $warnings,
                'unfulfilled_gate_codes' => $gates,
                'authorized' => false,
                'can_import' => false,
                'can_export' => false,
                'can_merge' => false,
                'can_deduplicate' => false,
                'can_persist' => false,
            ];
        } catch (InvalidArgumentException) {
            self::invalid();
        }
    }

    /** Deterministic, size-bounded JSON without untrusted source text. */
    public function encode(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
        mixed $requiredFields = null,
    ): string {
        try {
            $json = json_encode(
                $this->build($csv, $delimiter, $bindings, $hasHeader, $requiredFields),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException|InvalidArgumentException) {
            self::invalid();
        }
        if (strlen($json) > self::MAX_JSON_BYTES) {
            self::invalid();
        }
        return $json;
    }

    /** @param array<string,mixed> $values */
    private static function integer(array $values, string $key, int $max): int
    {
        $number = $values[$key] ?? null;
        if (!is_int($number) || $number < 0 || $number > $max) {
            self::invalid();
        }
        return $number;
    }

    /** @param mixed $values @param list<string> $allowed @return list<string> */
    private static function codes(mixed $values, array $allowed): array
    {
        if (!is_array($values) || !array_is_list($values) || count($values) > count($allowed)) {
            self::invalid();
        }
        $seen = [];
        foreach ($values as $value) {
            if (!is_string($value) || !in_array($value, $allowed, true)
                || isset($seen[$value])) {
                self::invalid();
            }
            $seen[$value] = true;
        }
        return $values;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Relatorio portatil CSV V1 invalido.');
    }
}
