<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-218 / IN-118: caller-declared illustrative required fields, in memory.
 * A requirement claim is not an approved CRM schema, consent, or policy.
 */
final class PortableCsvRequiredFieldsReview
{
    private const CANDIDATE_FIELDS = [
        'name', 'email', 'phone', 'origin', 'consent', 'notes', 'status',
    ];

    /**
     * @param mixed $csv Local bounded CSV text, no file I/O
     * @param mixed $delimiter 'comma'|'semicolon'
     * @param mixed $bindings Explicit positional field bindings
     * @param mixed $hasHeader Boolean claimed header
     * @param mixed $requiredFields Nonempty unique list of candidate field codes
     * @return array<string,mixed> Counts and fixed warnings, not row/field values
     */
    public function inspect(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
        mixed $requiredFields,
    ): array {
        if (!is_array($requiredFields) || !array_is_list($requiredFields)
            || $requiredFields === [] || count($requiredFields) > count(self::CANDIDATE_FIELDS)) {
            self::invalid();
        }

        $seen = [];
        foreach ($requiredFields as $field) {
            if (!is_string($field) || !in_array($field, self::CANDIDATE_FIELDS, true)
                || isset($seen[$field])) {
                self::invalid();
            }
            $seen[$field] = true;
        }

        try {
            $rows = (new PortableCsvTableCodec())->decode($csv, $delimiter);
            $mapped = (new PortableCsvFieldMapping())->map($rows, $bindings, $hasHeader);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $selected = array_fill_keys($mapped['target_field_codes'], true);
        $missingBindings = 0;
        foreach ($requiredFields as $field) {
            if (!isset($selected[$field])) {
                ++$missingBindings;
            }
        }

        $emptyRequiredCells = 0;
        $rowsWithEmptyRequiredValues = 0;
        foreach ($mapped['untrusted_mapped_rows'] as $row) {
            $incomplete = false;
            foreach ($requiredFields as $field) {
                if (isset($selected[$field]) && $row[$field] === '') {
                    ++$emptyRequiredCells;
                    $incomplete = true;
                }
            }
            if ($incomplete) {
                ++$rowsWithEmptyRequiredValues;
            }
        }

        $warnings = ['CLAIMED_REQUIRED_FIELD_POLICY_NOT_VERIFIED'];
        if ($missingBindings > 0) {
            $warnings[] = 'CLAIMED_REQUIRED_FIELDS_NOT_MAPPED';
        }
        if ($emptyRequiredCells > 0) {
            $warnings[] = 'EMPTY_VALUES_IN_CLAIMED_REQUIRED_FIELDS';
        }

        return [
            'header_claimed' => $hasHeader,
            'local_source_column_count' => $mapped['source_column_count'],
            'local_selected_field_count' => $mapped['mapped_field_count'],
            'local_data_row_count' => $mapped['data_row_count'],
            'local_claimed_required_field_count' => count($requiredFields),
            'local_unmapped_required_field_count' => $missingBindings,
            'local_empty_required_value_count' => $emptyRequiredCells,
            'local_rows_with_empty_required_value_count' => $rowsWithEmptyRequiredValues,
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'informational_only' => true,
            'required_policy_verified' => false,
            'schema_verified' => false,
            'consent_evidence_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'can_import' => false,
            'can_merge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Revisao local de campos obrigatorios CSV invalida.');
    }
}
