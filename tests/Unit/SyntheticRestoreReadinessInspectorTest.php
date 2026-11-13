<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticRestoreReadinessInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticRestoreReadinessInspectorTest extends TestCase
{
    private static function request(string $operation = 'restore_complete'): array
    {
        return [
            'plan' => 'basic',
            'operation' => $operation,
            'attachment_quota_percent' => 100,
            'confirmation_claimed' => true,
            'declared_package' => 'complete',
            'duplicate_warning_count' => 0,
            'formula_warning_count' => 0,
            'secret_fields_claimed' => false,
            'entity_kinds' => ['leads', 'contacts'],
            'target_account_state' => 'empty',
        ];
    }

    private static function manifest(): array
    {
        return [
            'format_version' => 1,
            'target_reader_version' => 1,
            'package_kind' => 'complete',
            'parts' => [
                ['kind'=>'leads','record_count'=>10,'declared_bytes'=>200],
                ['kind'=>'contacts','record_count'=>3,'declared_bytes'=>50],
            ],
            'declared_digest_algorithm' => 'sha256',
            'digest_present_claimed' => true,
            'encryption_claimed' => true,
            'secret_fields_claimed' => false,
            'claimed_archive_bytes' => 180,
        ];
    }

    public function test_perfect_looking_backup_is_still_unverified_and_cannot_be_restored(): void
    {
        $input = self::request();
        $manifest = self::manifest();
        $copyInput = $input;
        $copyManifest = $manifest;
        $inspector = new SyntheticRestoreReadinessInspector();
        $out = $inspector->inspect($input, $manifest);
        self::assertSame($copyInput, $input);
        self::assertSame($copyManifest, $manifest);
        self::assertSame($out, $inspector->inspect($input, $manifest));
        self::assertSame('restore_complete', $out['claimed_operation']);
        self::assertSame('basic', $out['claimed_plan']);
        self::assertSame(2, $out['declared_part_count']);
        self::assertSame(13, $out['declared_record_total']);
        self::assertSame('complete', $out['declared_package_kind']);
        self::assertSame([
            'PORTABILITY_BACKUP_MANIFEST_NOT_VERIFIED',
            'MANIFEST_COMPLETE_PACKAGE_SCOPE_UNVERIFIED',
            'MANIFEST_DIGEST_BYTES_NOT_VERIFIED',
            'MANIFEST_ENCRYPTION_UNVERIFIED',
        ], $out['warning_codes']);
        self::assertCount(6, $out['unfulfilled_gate_codes']);
        self::assertTrue($out['informational_only']);
        foreach ($out as $key => $value) {
            if ($key === 'authorized'
                || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
    }

    public function test_claimed_existing_target_and_duplicates_generate_stronger_human_review_hints(): void
    {
        $request = self::request();
        $request['target_account_state'] = 'populated';
        $request['duplicate_warning_count'] = 2;
        $out = (new SyntheticRestoreReadinessInspector())->inspect(
            $request, self::manifest(),
        );
        self::assertSame([
            'EXISTING_TARGET_REQUIRES_CONFLICT_DECISIONS',
            'DUPLICATE_REVIEW_CANNOT_BE_AUTOMATED',
            'PORTABILITY_BACKUP_MANIFEST_NOT_VERIFIED',
            'PORTABILITY_DUPLICATE_CONFLICT_REVIEW_REQUIRED',
            'PORTABILITY_TARGET_HAS_EXISTING_RECORDS',
            'MANIFEST_COMPLETE_PACKAGE_SCOPE_UNVERIFIED',
            'MANIFEST_DIGEST_BYTES_NOT_VERIFIED',
            'MANIFEST_ENCRYPTION_UNVERIFIED',
        ], $out['warning_codes']);
        self::assertFalse($out['duplicate_decisions_verified']);
        self::assertFalse($out['can_merge']);
        self::assertFalse($out['can_erase']);
    }

    public function test_table_backup_cannot_pass_as_full_restore_even_with_claimed_confirmation(): void
    {
        $manifest = self::manifest();
        $manifest['package_kind'] = 'table';
        $out = (new SyntheticRestoreReadinessInspector())->inspect(
            self::request(), $manifest,
        );
        self::assertContains('RESTORE_REQUIRES_FULL_PACKAGE', $out['warning_codes']);
        self::assertContains('PACKAGE_CLAIMS_DISAGREE', $out['warning_codes']);
        self::assertContains('MANIFEST_TABLE_EXPORT_NOT_FULL_BACKUP', $out['warning_codes']);
        self::assertFalse($out['can_restore']);
        self::assertFalse($out['can_import']);
    }

    public function test_declared_request_package_mismatch_is_flagged_independently(): void
    {
        $req = self::request();
        $req['declared_package'] = 'table';
        $out = (new SyntheticRestoreReadinessInspector())->inspect(
            $req, self::manifest(),
        );
        self::assertContains('PACKAGE_CLAIMS_DISAGREE', $out['warning_codes']);
        self::assertContains('PORTABILITY_PACKAGE_SCOPE_REQUIRES_REVIEW', $out['warning_codes']);
        self::assertNotContains('RESTORE_REQUIRES_FULL_PACKAGE', $out['warning_codes']);
    }

    public function test_unknown_target_and_old_reader_never_become_safe_when_confirmation_is_claimed(): void
    {
        $req = self::request('migrate_account');
        $req['plan'] = 'enterprise';
        $req['target_account_state'] = 'unknown';
        $manifest = self::manifest();
        $manifest['format_version'] = 2;
        $manifest['target_reader_version'] = 1;
        $out = (new SyntheticRestoreReadinessInspector())->inspect($req, $manifest);
        self::assertSame('migrate_account', $out['claimed_operation']);
        self::assertContains('UNKNOWN_TARGET_REQUIRES_INDEPENDENT_CHECK', $out['warning_codes']);
        self::assertContains('MANIFEST_TARGET_READER_OLDER_THAN_SOURCE', $out['warning_codes']);
        self::assertFalse($out['target_state_verified']);
        self::assertFalse($out['can_migrate']);
        self::assertFalse($out['can_reassign']);
    }

    public function test_no_export_or_spreadsheet_import_is_accepted_as_restore_intent(): void
    {
        foreach (['export_csv','export_xlsx','import_csv','import_xlsx','backup_complete'] as $operation) {
            $req = self::request($operation);
            $req['declared_package'] = $operation === 'backup_complete' ? 'complete' : 'table';
            try {
                (new SyntheticRestoreReadinessInspector())->inspect($req, self::manifest());
                self::fail('Non-restore action accepted: '.$operation);
            } catch (InvalidArgumentException $e) {
                self::assertSame('Revisao sintetica de restauracao invalida.', $e->getMessage());
            }
        }
    }

    public function test_invalid_second_contract_is_checked_even_when_first_contract_claims_conflicts(): void
    {
        $req = self::request();
        $req['target_account_state'] = 'populated';
        $req['duplicate_warning_count'] = 20;
        $manifest = self::manifest();
        $manifest['parts'][0]['email'] = 'secret@example.test';
        try {
            (new SyntheticRestoreReadinessInspector())->inspect($req, $manifest);
            self::fail('Malformed manifest accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Revisao sintetica de restauracao invalida.', $e->getMessage());
            self::assertStringNotContainsString('secret@example.test', $e->getMessage());
        }
    }

    public function test_invalid_first_contract_rejected_without_echoing_pii(): void
    {
        $req = self::request();
        $req['contact_email'] = 'secret@example.test';
        try {
            (new SyntheticRestoreReadinessInspector())->inspect($req, self::manifest());
            self::fail('PII-bearing request accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Revisao sintetica de restauracao invalida.', $e->getMessage());
            self::assertStringNotContainsString('secret@example.test', $e->getMessage());
        }
    }

    public function test_there_are_only_fixed_review_codes_and_no_customer_fields_or_file_payloads(): void
    {
        $out = (new SyntheticRestoreReadinessInspector())->inspect(
            self::request(), self::manifest(),
        );
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([
            '"email"', '"phone"', '"filename"', '"file_path"', '"payload"',
            '"tenant_id"', '"person_id"', '"user_id"', '"authorization_token"',
            '"sections"', '"parts"', '"checksum"', '"secret"',
        ] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
        self::assertFalse($out['authorization_verified']);
        self::assertFalse($out['can_acknowledge_delivery']);
        self::assertFalse($out['can_write_file']);
    }
}
