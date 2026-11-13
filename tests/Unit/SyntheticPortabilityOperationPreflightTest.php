<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticPortabilityOperationPreflight;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticPortabilityOperationPreflightTest extends TestCase
{
    /** @return array<string,mixed> */
    private static function request(string $operation = 'export_csv'): array
    {
        return [
            'plan' => 'basic',
            'operation' => $operation,
            'attachment_quota_percent' => 100,
            'confirmation_claimed' => false,
            'declared_package' => str_ends_with($operation, 'csv')
                || str_ends_with($operation, 'xlsx') ? 'table' : 'complete',
            'duplicate_warning_count' => 0,
            'formula_warning_count' => 0,
            'secret_fields_claimed' => false,
            'entity_kinds' => ['leads', 'contacts'],
            'target_account_state' => 'empty',
        ];
    }

    public function test_all_documented_plans_have_same_functional_portability_concept_with_no_real_permission(): void
    {
        $service = new SyntheticPortabilityOperationPreflight();
        foreach (['basic', 'essential', 'professional', 'enterprise'] as $plan) {
            foreach ([
                'export_csv', 'export_xlsx', 'import_csv', 'import_xlsx',
                'backup_complete', 'restore_complete', 'migrate_account',
            ] as $operation) {
                $request = self::request($operation);
                $request['plan'] = $plan;
                $result = $service->inspect($request);
                self::assertSame($operation, $result['operation']);
                self::assertSame($plan, $result['declared_plan']);
                self::assertTrue($result['functional_feature_documented_for_all_four_plans']);
                self::assertTrue($result['attachment_quota_at_limit_claimed']);
                self::assertFalse($result['portability_blocked_by_attachment_quota'],
                    'Full attachment quota must never categorically deny existing portability rights.');
                self::assertFalse($result['plan_entitlement_verified']);
                self::assertFalse($result['authorized']);
                self::assertFalse($result['can_persist']);
                self::assertFalse($result['can_merge']);
                self::assertFalse($result['can_erase']);
                self::assertSame(str_contains($operation, 'csv') || str_contains($operation, 'xlsx'),
                    $result['spreadsheet_requested']);
            }
        }
    }

    public function test_backup_is_not_single_csv_and_reports_missing_manifest_verification(): void
    {
        $service = new SyntheticPortabilityOperationPreflight();
        $backup = $service->inspect(self::request('backup_complete'));
        self::assertTrue($backup['complete_backup_requested']);
        self::assertFalse($backup['spreadsheet_requested']);
        self::assertContains('BACKUP_MANIFEST_NOT_VERIFIED', $backup['warnings']);
        self::assertFalse($backup['backup_integrity_verified']);

        $wrong = self::request('restore_complete');
        $wrong['declared_package'] = 'table';
        $out = $service->inspect($wrong);
        self::assertContains('PACKAGE_SCOPE_REQUIRES_REVIEW', $out['warnings']);
        self::assertContains('BACKUP_MANIFEST_NOT_VERIFIED', $out['warnings']);
        self::assertContains('EXPLICIT_CONFIRMATION_NOT_CLAIMED', $out['warnings']);
        self::assertFalse($out['can_restore']);
    }

    public function test_declared_confirmation_never_authorizes_restore_or_customer_merge(): void
    {
        $req = self::request('restore_complete');
        $req['confirmation_claimed'] = true;
        $req['target_account_state'] = 'populated';
        $req['duplicate_warning_count'] = 3;
        $req['formula_warning_count'] = 1;
        $req['secret_fields_claimed'] = true;
        $before = $req;
        $service = new SyntheticPortabilityOperationPreflight();
        $out = $service->inspect($req);
        self::assertSame($before, $req);
        self::assertSame($out, $service->inspect($req));
        self::assertSame([
            'BACKUP_MANIFEST_NOT_VERIFIED',
            'SECRET_FIELDS_MUST_BE_EXCLUDED',
            'SPREADSHEET_FORMULA_REVIEW_REQUIRED',
            'DUPLICATE_CONFLICT_REVIEW_REQUIRED',
            'TARGET_HAS_EXISTING_RECORDS',
        ], $out['warnings']);
        self::assertFalse($out['confirmation_verified']);
        self::assertFalse($out['can_restore']);
        self::assertFalse($out['can_merge']);
        self::assertFalse($out['can_upload']);
        self::assertFalse($out['can_write_file']);
        self::assertFalse($out['can_persist']);
    }

    public function test_unknown_target_has_warning_and_cannot_be_treated_as_empty(): void
    {
        $req = self::request('migrate_account');
        $req['target_account_state'] = 'unknown';
        $out = (new SyntheticPortabilityOperationPreflight())->inspect($req);
        self::assertContains('TARGET_STATE_UNVERIFIED', $out['warnings']);
        self::assertContains('EXPLICIT_CONFIRMATION_NOT_CLAIMED', $out['warnings']);
        self::assertFalse($out['can_migrate']);
        self::assertFalse($out['recipient_identity_verified']);
    }

    public function test_empty_export_does_not_invent_records_or_files(): void
    {
        $request = self::request();
        $request['entity_kinds'] = [];
        $request['attachment_quota_percent'] = 0;
        $out = (new SyntheticPortabilityOperationPreflight())->inspect($request);
        self::assertSame(0, $out['requested_entity_count']);
        self::assertContains('NO_FICTIONAL_ENTITIES_SELECTED', $out['warnings']);
        self::assertFalse($out['attachment_quota_at_limit_claimed']);
        self::assertFalse($out['can_export']);
        self::assertFalse($out['can_download']);
    }

    public function test_strict_shape_rejects_pii_and_malformed_entitlements_without_echo(): void
    {
        $ok = self::request();
        $cases = [
            null, [], array_merge($ok, ['email' => 'private@example.test']),
            array_replace($ok, ['plan' => 'trial']),
            array_replace($ok, ['plan' => 'BASIC']),
            array_replace($ok, ['plan' => null]),
            array_replace($ok, ['operation' => 'delete_all']),
            array_replace($ok, ['operation' => 'backup']),
            array_replace($ok, ['attachment_quota_percent' => 101]),
            array_replace($ok, ['attachment_quota_percent' => -1]),
            array_replace($ok, ['attachment_quota_percent' => '100']),
            array_replace($ok, ['confirmation_claimed' => 1]),
            array_replace($ok, ['declared_package' => 'zip']),
            array_replace($ok, ['duplicate_warning_count' => 21]),
            array_replace($ok, ['formula_warning_count' => 0.0]),
            array_replace($ok, ['secret_fields_claimed' => 0]),
            array_replace($ok, ['target_account_state' => 'other_account']),
            array_replace($ok, ['entity_kinds' => ['leads', 'leads']]),
            array_replace($ok, ['entity_kinds' => ['leads', 'person@email.test']]),
            array_replace($ok, ['entity_kinds' => [1 => 'leads']]),
            array_replace($ok, ['entity_kinds' => ['leads', 'contacts', 'activities', 'opportunities', 'files']]),
        ];
        foreach ($cases as $candidate) {
            try {
                (new SyntheticPortabilityOperationPreflight())->inspect($candidate);
                self::fail('Malformed portability request was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Previa sintetica de portabilidade invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_no_result_can_claim_permission_or_expose_entered_entity_values(): void
    {
        $out = (new SyntheticPortabilityOperationPreflight())->inspect(self::request('import_xlsx'));
        self::assertTrue($out['informational_only']);
        foreach ($out as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['"email"', '"phone"', '"tenant_id"', '"record_id"', '"path"',
            '"file_bytes"', '"token"', '"user_id"', '"secrets"', '"customer"'] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
    }
}
