<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticBackupManifestContractInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticBackupManifestContractInspectorTest extends TestCase
{
    private static function part(
        string $kind = 'leads', int $records = 3, int $bytes = 100,
    ): array {
        return [
            'kind' => $kind,
            'record_count' => $records,
            'declared_bytes' => $bytes,
        ];
    }

    private static function manifest(): array
    {
        return [
            'format_version' => 1,
            'target_reader_version' => 1,
            'package_kind' => 'complete',
            'parts' => [
                self::part('leads', 3, 100),
                self::part('contacts', 2, 40),
                self::part('histories', 1, 50),
                self::part('attachments', 0, 0),
            ],
            'declared_digest_algorithm' => 'sha256',
            'digest_present_claimed' => true,
            'encryption_claimed' => true,
            'secret_fields_claimed' => false,
            'claimed_archive_bytes' => 120,
        ];
    }

    public function test_consistent_syntax_never_proves_backup_integrity_or_restore_authorization(): void
    {
        $original = self::manifest();
        $service = new SyntheticBackupManifestContractInspector();
        $out = $service->inspect($original);
        self::assertSame($original, self::manifest());
        self::assertSame($out, $service->inspect($original));
        self::assertSame(4, $out['declared_part_count']);
        self::assertSame(6, $out['declared_record_total']);
        self::assertSame(190, $out['declared_uncompressed_part_bytes']);
        self::assertSame(120, $out['declared_archive_bytes']);
        self::assertSame([
            'COMPLETE_PACKAGE_SCOPE_UNVERIFIED',
            'DIGEST_BYTES_NOT_VERIFIED',
            'ENCRYPTION_UNVERIFIED',
        ], $out['warning_codes']);
        foreach ($out as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value, $key);
            }
        }
        self::assertTrue($out['informational_only']);
    }

    public function test_table_export_is_never_a_full_backup(): void
    {
        $input = self::manifest();
        $input['package_kind'] = 'table';
        $input['parts'] = [self::part()];
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertContains('TABLE_EXPORT_NOT_FULL_BACKUP', $out['warning_codes']);
        self::assertNotContains('COMPLETE_PACKAGE_SCOPE_UNVERIFIED', $out['warning_codes']);
        self::assertFalse($out['can_restore']);
    }

    public function test_unsupported_versions_and_backwards_reader_generate_distinct_warnings(): void
    {
        $input = self::manifest();
        $input['format_version'] = 3;
        $input['target_reader_version'] = 1;
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertContains('SCHEMA_VERSION_REVIEW_REQUIRED', $out['warning_codes']);
        self::assertContains('TARGET_READER_OLDER_THAN_SOURCE', $out['warning_codes']);
        self::assertFalse($out['format_compatibility_verified']);
        self::assertFalse($out['can_restore']);

        $input['format_version'] = 1;
        $input['target_reader_version'] = 3;
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertContains('SCHEMA_VERSION_REVIEW_REQUIRED', $out['warning_codes']);
        self::assertNotContains('TARGET_READER_OLDER_THAN_SOURCE', $out['warning_codes']);
        self::assertFalse($out['format_compatibility_verified']);
    }

    public function test_missing_digest_and_unclaimed_encryption_are_not_green_lights(): void
    {
        $input = self::manifest();
        $input['declared_digest_algorithm'] = 'none';
        $input['digest_present_claimed'] = false;
        $input['encryption_claimed'] = false;
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertContains('NO_INTEGRITY_EVIDENCE', $out['warning_codes']);
        self::assertContains('ENCRYPTION_NOT_CLAIMED', $out['warning_codes']);
        self::assertFalse($out['can_export']);
        self::assertFalse($out['can_download']);
        $input['digest_present_claimed'] = true;
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertContains('DIGEST_CLAIM_CONTRADICTORY', $out['warning_codes']);
    }

    public function test_secret_claim_and_zero_archive_size_are_not_silently_accepted_as_ready(): void
    {
        $input = self::manifest();
        $input['claimed_archive_bytes'] = 0;
        $input['secret_fields_claimed'] = true;
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertContains('ZERO_ARCHIVE_SIZE_CONTRADICTS_PARTS', $out['warning_codes']);
        self::assertContains('CROSS_ACCOUNT_SECRET_EXCLUSION_REQUIRED', $out['warning_codes']);
        self::assertFalse($out['secret_exclusion_verified']);
        self::assertFalse($out['can_migrate']);
    }

    public function test_empty_and_seven_part_boundaries_remain_metadata_only(): void
    {
        $input = self::manifest();
        $input['parts'] = [];
        $input['claimed_archive_bytes'] = 0;
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertContains('NO_DECLARED_PARTS', $out['warning_codes']);
        self::assertSame(0, $out['declared_record_total']);

        $input['parts'] = [
            self::part('leads', 10000, 1000000000),
            self::part('contacts', 10000, 1000000000),
            self::part('opportunities', 10000, 1000000000),
            self::part('activities', 10000, 1000000000),
            self::part('histories', 10000, 1000000000),
            self::part('attachments', 10000, 1000000000),
            self::part('settings', 10000, 1000000000),
        ];
        $input['claimed_archive_bytes'] = 1000000000;
        $out = (new SyntheticBackupManifestContractInspector())->inspect($input);
        self::assertSame(70000, $out['declared_record_total']);
        self::assertSame(7000000000, $out['declared_uncompressed_part_bytes']);
        self::assertSame(7, $out['declared_part_count']);
        self::assertFalse($out['can_read_archive']);
        self::assertFalse($out['can_persist']);
    }

    public function test_rejects_pii_additional_keys_malformed_parts_duplicates_and_type_confusion(): void
    {
        $valid = self::manifest();
        $broken = [
            null, [], 'manifest',
            array_merge($valid, ['tenant_id' => 'secret@example.test']),
            array_replace($valid, ['format_version' => 0]),
            array_replace($valid, ['format_version' => 4]),
            array_replace($valid, ['format_version' => '1']),
            array_replace($valid, ['target_reader_version' => 0]),
            array_replace($valid, ['target_reader_version' => 4]),
            array_replace($valid, ['package_kind' => 'full']),
            array_replace($valid, ['declared_digest_algorithm' => 'md5']),
            array_replace($valid, ['declared_digest_algorithm' => 'SHA256']),
            array_replace($valid, ['digest_present_claimed' => 1]),
            array_replace($valid, ['encryption_claimed' => 'yes']),
            array_replace($valid, ['secret_fields_claimed' => null]),
            array_replace($valid, ['claimed_archive_bytes' => -1]),
            array_replace($valid, ['claimed_archive_bytes' => 1000000001]),
            array_replace($valid, ['claimed_archive_bytes' => '120']),
            array_replace($valid, ['parts' => 'leads']),
            array_replace($valid, ['parts' => [1 => self::part()]]),
            array_replace($valid, ['parts' => [self::part(), self::part()]]),
            array_replace($valid, ['parts' => [self::part('credentials')]]),
            array_replace($valid, ['parts' => [[...self::part(), 'phone' => 'secret@example.test']]]),
            array_replace($valid, ['parts' => [array_replace(self::part(), ['record_count' => -1])]]),
            array_replace($valid, ['parts' => [array_replace(self::part(), ['record_count' => '3'])]]),
            array_replace($valid, ['parts' => [array_replace(self::part(), ['record_count' => 10001])]]),
            array_replace($valid, ['parts' => [array_replace(self::part(), ['declared_bytes' => -1])]]),
            array_replace($valid, ['parts' => [array_replace(self::part(), ['declared_bytes' => 1000000001])]]),
            array_replace($valid, ['parts' => [array_replace(self::part(), ['kind' => 3])]]),
            array_replace($valid, ['parts' => array_fill(0, 8, self::part())]),
        ];
        foreach ($broken as $request) {
            try {
                (new SyntheticBackupManifestContractInspector())->inspect($request);
                self::fail('Untrusted backup manifest was incorrectly accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Manifesto sintetico de backup invalido.', $e->getMessage());
                self::assertStringNotContainsString('secret@example.test', $e->getMessage());
            }
        }
    }

    public function test_response_never_exposes_sections_contact_identifiers_or_digest_bytes(): void
    {
        $out = (new SyntheticBackupManifestContractInspector())->inspect(self::manifest());
        $data = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([
            '"parts"', '"kind"', '"email"', '"phone"', '"tenant"',
            '"user_id"', '"file_path"', '"token"', '"hash"', '"digest"',
            '"filename"', '"customer"', '"password"', '"payload"',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $data);
        }
    }
}
