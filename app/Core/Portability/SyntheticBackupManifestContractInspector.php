<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-199 / IN-118: versioned, metadata-only backup manifest rehearsal.
 *
 * Never consumes archive bytes, hashes, filenames, tenant identifiers,
 * credentials, actual rows, or signatures. Every value is an untrusted
 * synthetic claim. Syntax checks must not become restoration authority.
 */
final class SyntheticBackupManifestContractInspector
{
    private const KEYS = [
        'claimed_archive_bytes', 'declared_digest_algorithm',
        'digest_present_claimed', 'encryption_claimed', 'format_version',
        'package_kind', 'parts', 'secret_fields_claimed',
        'target_reader_version',
    ];

    private const PART_KINDS = [
        'leads', 'contacts', 'opportunities', 'activities',
        'histories', 'attachments', 'settings',
    ];

    private const MAX_BYTES = 1000000000;
    private const MAX_RECORDS = 10000;

    /**
     * @param mixed $manifest Strict fictional 9-key manifest metadata only
     * @return array<string,mixed> Fixed-code warnings and denied capabilities
     */
    public function inspect(mixed $manifest): array
    {
        if (!is_array($manifest) || count($manifest) !== count(self::KEYS)) {
            self::invalid();
        }
        $keys = array_keys($manifest);
        $expected = self::KEYS;
        sort($keys);
        sort($expected);
        if ($keys !== $expected
            || !is_int($manifest['format_version'])
            || $manifest['format_version'] < 1 || $manifest['format_version'] > 3
            || !is_int($manifest['target_reader_version'])
            || $manifest['target_reader_version'] < 1
            || $manifest['target_reader_version'] > 3
            || !is_string($manifest['package_kind'])
            || !in_array($manifest['package_kind'], ['table', 'complete'], true)
            || !is_string($manifest['declared_digest_algorithm'])
            || !in_array($manifest['declared_digest_algorithm'], ['none', 'sha256'], true)
            || !is_bool($manifest['digest_present_claimed'])
            || !is_bool($manifest['encryption_claimed'])
            || !is_bool($manifest['secret_fields_claimed'])
            || !is_int($manifest['claimed_archive_bytes'])
            || $manifest['claimed_archive_bytes'] < 0
            || $manifest['claimed_archive_bytes'] > self::MAX_BYTES
            || !is_array($manifest['parts'])
            || !array_is_list($manifest['parts'])
            || count($manifest['parts']) > count(self::PART_KINDS)) {
            self::invalid();
        }

        $seen = [];
        $recordTotal = 0;
        $declaredBytes = 0;
        foreach ($manifest['parts'] as $part) {
            if (!is_array($part) || count($part) !== 3) {
                self::invalid();
            }
            $partKeys = array_keys($part);
            sort($partKeys);
            if ($partKeys !== ['declared_bytes', 'kind', 'record_count']
                || !is_string($part['kind'])
                || !in_array($part['kind'], self::PART_KINDS, true)
                || isset($seen[$part['kind']])
                || !is_int($part['record_count'])
                || $part['record_count'] < 0
                || $part['record_count'] > self::MAX_RECORDS
                || !is_int($part['declared_bytes'])
                || $part['declared_bytes'] < 0
                || $part['declared_bytes'] > self::MAX_BYTES) {
                self::invalid();
            }
            $seen[$part['kind']] = true;
            $recordTotal += $part['record_count'];
            $declaredBytes += $part['declared_bytes'];
        }

        $warnings = [];
        if ($manifest['package_kind'] === 'table') {
            $warnings[] = 'TABLE_EXPORT_NOT_FULL_BACKUP';
        } else {
            // A list of categories cannot establish links, histories,
            // attachment bytes, settings or consistent snapshot semantics.
            $warnings[] = 'COMPLETE_PACKAGE_SCOPE_UNVERIFIED';
        }
        if ($manifest['parts'] === []) {
            $warnings[] = 'NO_DECLARED_PARTS';
        }
        if ($declaredBytes > 0 && $manifest['claimed_archive_bytes'] === 0) {
            $warnings[] = 'ZERO_ARCHIVE_SIZE_CONTRADICTS_PARTS';
        }
        if ($manifest['format_version'] !== 1
            || $manifest['target_reader_version'] !== 1) {
            $warnings[] = 'SCHEMA_VERSION_REVIEW_REQUIRED';
        }
        if ($manifest['target_reader_version'] < $manifest['format_version']) {
            $warnings[] = 'TARGET_READER_OLDER_THAN_SOURCE';
        }
        if ($manifest['declared_digest_algorithm'] === 'none') {
            $warnings[] = $manifest['digest_present_claimed']
                ? 'DIGEST_CLAIM_CONTRADICTORY' : 'NO_INTEGRITY_EVIDENCE';
        } else {
            $warnings[] = $manifest['digest_present_claimed']
                ? 'DIGEST_BYTES_NOT_VERIFIED'
                : 'DIGEST_EVIDENCE_NOT_SUPPLIED';
        }
        $warnings[] = $manifest['encryption_claimed']
            ? 'ENCRYPTION_UNVERIFIED' : 'ENCRYPTION_NOT_CLAIMED';
        if ($manifest['secret_fields_claimed']) {
            $warnings[] = 'CROSS_ACCOUNT_SECRET_EXCLUSION_REQUIRED';
        }

        return [
            'declared_format_version' => $manifest['format_version'],
            'declared_reader_version' => $manifest['target_reader_version'],
            'declared_package_kind' => $manifest['package_kind'],
            'declared_part_count' => count($manifest['parts']),
            'declared_record_total' => $recordTotal,
            'declared_uncompressed_part_bytes' => $declaredBytes,
            'declared_archive_bytes' => $manifest['claimed_archive_bytes'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'archive_bytes_verified' => false,
            'content_integrity_verified' => false,
            'encryption_verified' => false,
            'backup_consistency_verified' => false,
            'format_compatibility_verified' => false,
            'secret_exclusion_verified' => false,
            'tenant_scope_verified' => false,
            'recipient_permissions_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_export' => false,
            'can_restore' => false,
            'can_migrate' => false,
            'can_merge' => false,
            'can_download' => false,
            'can_read_archive' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Manifesto sintetico de backup invalido.');
    }
}
