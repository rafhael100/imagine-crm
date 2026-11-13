<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-203 / IN-118: consistency-only composition for fictional imports.
 *
 * Both complete prior contracts must validate before any result is returned.
 * Reported counts, plan, field mappings, entity and destination are untrusted
 * claims. No CSV/XLSX file or row is read, no tenant is resolved, no write.
 */
final class SyntheticImportReadinessInspector
{
    /**
     * @param mixed $operationRequest Strict F3-198 synthetic import request
     * @param mixed $mappingRequest Strict F3-202 positional mapping metadata
     * @return array<string,mixed> Fixed-code mismatch signals; no authority
     */
    public function inspect(mixed $operationRequest, mixed $mappingRequest): array
    {
        try {
            $operation = (new SyntheticPortabilityOperationPreflight())
                ->inspect($operationRequest);
            $mapping = (new SyntheticImportColumnMappingInspector())
                ->inspect($mappingRequest);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        if (!in_array($operation['operation'], ['import_csv', 'import_xlsx'], true)) {
            self::invalid();
        }

        // Checked inputs above are still claims; none are authority.
        $warnings = [];
        if ($operationRequest['declared_package'] !== 'table') {
            $warnings[] = 'IMPORT_REQUIRES_TABLE_PACKAGE';
        }
        if (!in_array($mapping['entity_kind'], $operationRequest['entity_kinds'], true)) {
            $warnings[] = 'TARGET_ENTITY_NOT_DECLARED_IN_OPERATION';
        }
        if ($operationRequest['target_account_state'] !== $mappingRequest['target_state']) {
            $warnings[] = 'TARGET_STATE_CLAIMS_DISAGREE';
        }
        if ($operationRequest['duplicate_warning_count']
            !== $mapping['declared_duplicate_candidate_count']) {
            $warnings[] = 'DUPLICATE_COUNTS_DISAGREE';
        }
        if ($operationRequest['formula_warning_count']
            !== $mapping['declared_formula_warning_count']) {
            $warnings[] = 'FORMULA_WARNING_COUNTS_DISAGREE';
        }
        // Even matching source and destination claims are not verified.
        foreach ($operation['warnings'] as $code) {
            $warnings[] = 'OPERATION_'.$code;
        }
        foreach ($mapping['warning_codes'] as $code) {
            $warnings[] = 'MAPPING_'.$code;
        }

        return [
            'claimed_import_format' => $operation['operation'],
            'claimed_plan' => $operation['declared_plan'],
            'declared_entity_kind' => $mapping['entity_kind'],
            'declared_row_count' => $mapping['declared_row_count'],
            'declared_source_column_count' => $mapping['declared_source_column_count'],
            'declared_mapped_column_count' => $mapping['mapped_column_count'],
            'declared_target_collision_count' => $mapping['target_collision_count'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'unfulfilled_gate_codes' => [
                'VERIFY_TENANT_AND_IMPORT_PERMISSIONS',
                'VALIDATE_REAL_FILE_CONTENT_AND_ENCODING',
                'VERIFY_IMPORT_SCHEMA_AND_FIELD_CONSENT',
                'RESOLVE_DUPLICATES_WITH_AUTHORIZED_REVIEW',
                'REQUIRE_TRANSACTION_BACKUP_AND_AUDIT',
                'REQUIRE_EXPLICIT_CONFIRMED_WRITE_APPROVAL',
            ],
            'import_file_verified' => false,
            'mapping_verified' => false,
            'consent_evidence_verified' => false,
            'tenant_scope_verified' => false,
            'target_state_verified' => false,
            'duplicate_decisions_verified' => false,
            'formula_safety_verified' => false,
            'confirmation_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_file' => false,
            'can_import' => false,
            'can_merge' => false,
            'can_update_lead' => false,
            'can_confirm_consent' => false,
            'can_erase' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Revisao sintetica de importacao invalida.');
    }
}
