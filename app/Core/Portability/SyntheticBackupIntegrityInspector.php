<?php

declare(strict_types=1);

namespace App\Core\Portability;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * REC-LAB-04: bounded, side-effect-free checksum consistency laboratory.
 *
 * Both bytes and manifest are UNTRUSTED artificial inputs. A matching
 * caller-controlled SHA-256 is not proof of origin, authenticity, backup
 * completeness, restorability, retention, tenant, custody or authorization.
 *
 * Do not wire this demonstration to real uploads, backups or recovery jobs.
 */
final class SyntheticBackupIntegrityInspector
{
    private const MAX_FIXTURE_BYTES = 32768;
    private const FIXTURE_FORMAT = 'synthetic-blob-v1';

    /**
     * @param array<mixed> $manifest
     */
    public function inspect(array $manifest, string $syntheticBytes): SyntheticBackupIntegrityAssessment
    {
        $keys = array_keys($manifest);
        sort($keys);
        if ($keys !== ['declared_bytes', 'format', 'recorded_at_utc', 'sha256']
            || $manifest['format'] !== self::FIXTURE_FORMAT
            || !is_int($manifest['declared_bytes'])
            || $manifest['declared_bytes'] < 1
            || $manifest['declared_bytes'] > self::MAX_FIXTURE_BYTES
            || !is_string($manifest['sha256'])
            || preg_match('/\A[0-9a-f]{64}\z/D', $manifest['sha256']) !== 1
            || !is_string($manifest['recorded_at_utc'])
            || !$this->isCanonicalUtc($manifest['recorded_at_utc'])
            || strlen($syntheticBytes) < 1
            || strlen($syntheticBytes) > self::MAX_FIXTURE_BYTES) {
            self::invalid();
        }

        if (strlen($syntheticBytes) !== $manifest['declared_bytes']
            || !hash_equals(hash('sha256', $syntheticBytes), $manifest['sha256'])) {
            return new SyntheticBackupIntegrityAssessment('SELF_CLAIM_MISMATCH');
        }

        // This only says a digest and length supplied by the caller match
        // the same caller's bytes. It NEVER means backup or restore success.
        return new SyntheticBackupIntegrityAssessment('SELF_CLAIM_MATCH_UNTRUSTED');
    }

    private function isCanonicalUtc(string $value): bool
    {
        if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D', $value) !== 1) {
            return false;
        }

        $utc = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));

        return $utc !== false && $utc->format('Y-m-d\TH:i:s\Z') === $value;
    }

    private static function invalid(): never
    {
        // Do not reflect manifest fields, content, digests or any user data.
        throw new InvalidArgumentException('Manifesto sintetico de integridade invalido.');
    }
}
