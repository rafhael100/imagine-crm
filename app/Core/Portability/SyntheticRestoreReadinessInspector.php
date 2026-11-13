<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-200 / IN-118: pure synthetic restore readiness composition.
 *
 * Reuses F3-198 operation review AND F3-199 versioned backup manifest
 * validation before deriving any result. All declared facts remain caller
 * claims: there is no file read, tenant identity, permission, consent,
 * checksum, transaction, lock, restore journal or durable acknowledgement.
 */
final class SyntheticRestoreReadinessInspector
{
    /**
     * @param mixed $operationRequest Strict F3-198 fictional request
     * @param mixed $backupManifest Strict F3-199 fictional manifest
     * @return array<string,mixed> Deny-only procedural readiness hints
     */
    public function inspect(mixed $operationRequest, mixed $backupManifest): array
    {
        try {
            // Both complete contracts must be validated. Never short-circuit
            // manifest validation on an alleged preflight refusal.
            $operation = (new SyntheticPortabilityOperationPreflight())
                ->inspect($operationRequest);
            $manifest = (new SyntheticBackupManifestContractInspector())
                ->inspect($backupManifest);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        if (!in_array($operation['operation'], [
            'restore_complete', 'migrate_account',
        ], true)) {
            self::invalid();
        }

        $warnings = [];
        if ($operation['complete_backup_requested']
            && $manifest['declared_package_kind'] !== 'complete') {
            $warnings[] = 'RESTORE_REQUIRES_FULL_PACKAGE';
        }
        if (is_array($operationRequest)
            && $operationRequest['declared_package'] !== $manifest['declared_package_kind']) {
            $warnings[] = 'PACKAGE_CLAIMS_DISAGREE';
        }
        if (in_array('TARGET_HAS_EXISTING_RECORDS', $operation['warnings'], true)) {
            $warnings[] = 'EXISTING_TARGET_REQUIRES_CONFLICT_DECISIONS';
        }
        if (in_array('TARGET_STATE_UNVERIFIED', $operation['warnings'], true)) {
            $warnings[] = 'UNKNOWN_TARGET_REQUIRES_INDEPENDENT_CHECK';
        }
        if (in_array('DUPLICATE_CONFLICT_REVIEW_REQUIRED', $operation['warnings'], true)) {
            $warnings[] = 'DUPLICATE_REVIEW_CANNOT_BE_AUTOMATED';
        }

        // Fixed, bounded codes only. No arbitrary input field is echoed.
        foreach ($operation['warnings'] as $code) {
            $warnings[] = 'PORTABILITY_'.$code;
        }
        foreach ($manifest['warning_codes'] as $code) {
            $warnings[] = 'MANIFEST_'.$code;
        }

        return [
            'claimed_operation' => $operation['operation'],
            'claimed_plan' => $operation['declared_plan'],
            'declared_part_count' => $manifest['declared_part_count'],
            'declared_record_total' => $manifest['declared_record_total'],
            'declared_package_kind' => $manifest['declared_package_kind'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'unfulfilled_gate_codes' => [
                'VERIFY_REAL_TENANT_AND_PERMISSIONS',
                'VERIFY_REAL_ARCHIVE_ORIGIN_AND_INTEGRITY',
                'VERIFY_ENCRYPTION_AND_SECRET_EXCLUSION',
                'REVIEW_FIELD_MAPPING_DUPLICATES_AND_CONSENTS',
                'PREPARE_NONDESTRUCTIVE_ROLLBACK_AND_AUDIT',
                'REQUIRE_EXPLICIT_REAUTHORIZATION_AND_APPROVAL',
            ],
            'tenant_scope_verified' => false,
            'source_package_verified' => false,
            'archive_integrity_verified' => false,
            'secret_exclusion_verified' => false,
            'target_state_verified' => false,
            'duplicate_decisions_verified' => false,
            'user_confirmation_verified' => false,
            'rollback_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_restore' => false,
            'can_import' => false,
            'can_migrate' => false,
            'can_merge' => false,
            'can_erase' => false,
            'can_reassign' => false,
            'can_acknowledge_delivery' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Revisao sintetica de restauracao invalida.');
    }
}
