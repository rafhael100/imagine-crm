<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * CRM F3-198 / IN-118 / DAT-R13-R15-R22: pure, DENY-ONLY review of
 * synthetic import/export/backup/restoration requests.
 *
 * Plans, quota percentages, confirmation and contents are CALLER CLAIMS.
 * No real customer data, file bytes, paths, secrets, tenant IDs or download
 * tokens are accepted or emitted. A favourable description never grants
 * operation rights or counts as a successful restore.
 */
final class SyntheticPortabilityOperationPreflight
{
    private const PLANS = ['basic', 'essential', 'professional', 'enterprise'];
    private const OPERATIONS = [
        'export_csv', 'export_xlsx', 'import_csv', 'import_xlsx',
        'backup_complete', 'restore_complete', 'migrate_account',
    ];
    private const ENTITIES = ['leads', 'contacts', 'opportunities', 'activities'];
    private const TARGET_STATES = ['empty', 'populated', 'unknown'];
    private const PACKAGES = ['table', 'complete', 'unspecified'];

    /**
     * @param mixed $request Strict 10-key synthetic descriptor; no payloads.
     * @return array<string,mixed> Closed warning codes and no capabilities.
     */
    public function inspect(mixed $request): array
    {
        if (!is_array($request) || count($request) !== 10) {
            self::invalid();
        }
        $keys = array_keys($request);
        sort($keys);
        if ($keys !== [
            'attachment_quota_percent', 'confirmation_claimed',
            'declared_package', 'duplicate_warning_count',
            'entity_kinds', 'formula_warning_count', 'operation',
            'plan', 'secret_fields_claimed', 'target_account_state',
        ]) {
            self::invalid();
        }
        if (!is_string($request['plan']) || !in_array($request['plan'], self::PLANS, true)
            || !is_string($request['operation']) || !in_array($request['operation'], self::OPERATIONS, true)
            || !is_int($request['attachment_quota_percent'])
            || $request['attachment_quota_percent'] < 0
            || $request['attachment_quota_percent'] > 100
            || !is_bool($request['confirmation_claimed'])
            || !is_bool($request['secret_fields_claimed'])
            || !is_int($request['duplicate_warning_count'])
            || $request['duplicate_warning_count'] < 0
            || $request['duplicate_warning_count'] > 20
            || !is_int($request['formula_warning_count'])
            || $request['formula_warning_count'] < 0
            || $request['formula_warning_count'] > 20
            || !is_string($request['declared_package'])
            || !in_array($request['declared_package'], self::PACKAGES, true)
            || !is_string($request['target_account_state'])
            || !in_array($request['target_account_state'], self::TARGET_STATES, true)
            || !is_array($request['entity_kinds'])
            || !array_is_list($request['entity_kinds'])
            || count($request['entity_kinds']) > count(self::ENTITIES)) {
            self::invalid();
        }

        $entitiesSeen = [];
        foreach ($request['entity_kinds'] as $kind) {
            if (!is_string($kind) || !in_array($kind, self::ENTITIES, true)
                || isset($entitiesSeen[$kind])) {
                self::invalid();
            }
            $entitiesSeen[$kind] = true;
        }

        $operation = $request['operation'];
        $spreadsheet = str_ends_with($operation, '_csv')
            || str_ends_with($operation, '_xlsx');
        $needsWrite = str_starts_with($operation, 'import_')
            || $operation === 'restore_complete' || $operation === 'migrate_account';
        $needsCompletePackage = !$spreadsheet;
        $warnings = [];

        if ($request['entity_kinds'] === []) {
            $warnings[] = 'NO_FICTIONAL_ENTITIES_SELECTED';
        }
        if (($spreadsheet && $request['declared_package'] !== 'table')
            || ($needsCompletePackage && $request['declared_package'] !== 'complete')) {
            $warnings[] = 'PACKAGE_SCOPE_REQUIRES_REVIEW';
        }
        if ($needsCompletePackage) {
            $warnings[] = 'BACKUP_MANIFEST_NOT_VERIFIED';
        }
        if ($request['secret_fields_claimed']) {
            $warnings[] = 'SECRET_FIELDS_MUST_BE_EXCLUDED';
        }
        if ($request['formula_warning_count'] > 0) {
            $warnings[] = 'SPREADSHEET_FORMULA_REVIEW_REQUIRED';
        }
        if ($request['duplicate_warning_count'] > 0) {
            $warnings[] = 'DUPLICATE_CONFLICT_REVIEW_REQUIRED';
        }
        if ($needsWrite && !$request['confirmation_claimed']) {
            $warnings[] = 'EXPLICIT_CONFIRMATION_NOT_CLAIMED';
        }
        if ($operation === 'restore_complete' || $operation === 'migrate_account') {
            if ($request['target_account_state'] === 'populated') {
                $warnings[] = 'TARGET_HAS_EXISTING_RECORDS';
            } elseif ($request['target_account_state'] === 'unknown') {
                $warnings[] = 'TARGET_STATE_UNVERIFIED';
            }
        }

        return [
            'operation' => $operation,
            'declared_plan' => $request['plan'],
            'requested_entity_count' => count($request['entity_kinds']),
            'spreadsheet_requested' => $spreadsheet,
            'complete_backup_requested' => $needsCompletePackage,
            'data_write_requested' => $needsWrite,
            'attachment_quota_at_limit_claimed' => $request['attachment_quota_percent'] === 100,
            // IN-118: even at 100% storage, existing export/backup rights
            // are not automatically blocked. This does not verify rights.
            'portability_blocked_by_attachment_quota' => false,
            'functional_feature_documented_for_all_four_plans' => true,
            'warnings' => $warnings,
            'warning_count' => count($warnings),
            'access_scope_verified' => false,
            'plan_entitlement_verified' => false,
            'tenant_scope_verified' => false,
            'source_package_verified' => false,
            'backup_integrity_verified' => false,
            'recipient_identity_verified' => false,
            'confirmation_verified' => false,
            'duplicate_decisions_verified' => false,
            'privacy_policy_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_export' => false,
            'can_import' => false,
            'can_backup' => false,
            'can_restore' => false,
            'can_migrate' => false,
            'can_merge' => false,
            'can_erase' => false,
            'can_download' => false,
            'can_upload' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Previa sintetica de portabilidade invalida.');
    }
}
