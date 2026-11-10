<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticBackupIntegrityInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticBackupIntegrityInspectorTest extends TestCase
{
    private const CAPTURED = '2026-11-10T03:00:00Z';

    /**
     * @return array{format:string,declared_bytes:int,recorded_at_utc:string,sha256:string}
     */
    private function manifest(string $bytes): array
    {
        return [
            'format' => 'synthetic-blob-v1',
            'declared_bytes' => strlen($bytes),
            'recorded_at_utc' => self::CAPTURED,
            'sha256' => hash('sha256', $bytes),
        ];
    }

    public function test_consistent_hash_is_only_a_self_claim_and_never_authorizes_restoration(): void
    {
        $bytes = "invented,example\n1,2\n";
        $assessment = (new SyntheticBackupIntegrityInspector())->inspect($this->manifest($bytes), $bytes);

        self::assertSame('SELF_CLAIM_MATCH_UNTRUSTED', $assessment->reasonCode);
        self::assertTrue($assessment->matchesSelfDeclaredDigest());
        self::assertFalse($assessment->canRestore());
        self::assertFalse($assessment->canAcceptAsVerifiedBackup());
        self::assertFalse($assessment->canDeleteOriginal());
        self::assertSame(['reasonCode'], array_keys(get_object_vars($assessment)));
        self::assertStringNotContainsString('invented', json_encode($assessment, JSON_THROW_ON_ERROR));
    }

    public function test_even_forged_bytes_and_a_recomputed_matching_claim_never_prove_origin(): void
    {
        $inspector = new SyntheticBackupIntegrityInspector();
        $original = 'invented fixture bytes';
        $modified = 'forged fixture bytes';
        $mismatch = $inspector->inspect($this->manifest($original), $modified);
        self::assertSame('SELF_CLAIM_MISMATCH', $mismatch->reasonCode);
        self::assertFalse($mismatch->matchesSelfDeclaredDigest());

        // An adversary controlling both the manifest and the content can make
        // the digest match again; this is precisely why it proves no origin.
        $forgedClaim = $inspector->inspect($this->manifest($modified), $modified);
        self::assertTrue($forgedClaim->matchesSelfDeclaredDigest());
        self::assertFalse($forgedClaim->canRestore());
        self::assertFalse($forgedClaim->canAcceptAsVerifiedBackup());
        self::assertFalse($forgedClaim->canDeleteOriginal());
    }

    public function test_tampered_length_or_digest_are_detected_without_echoing_bytes(): void
    {
        $inspector = new SyntheticBackupIntegrityInspector();
        $bytes = 'fake sample, no real records';
        foreach ([
            ['declared_bytes' => strlen($bytes) + 1],
            ['sha256' => str_repeat('0', 64)],
        ] as $change) {
            $assessment = $inspector->inspect(array_replace($this->manifest($bytes), $change), $bytes);
            self::assertSame('SELF_CLAIM_MISMATCH', $assessment->reasonCode);
            self::assertFalse($assessment->canRestore());
            self::assertStringNotContainsString($bytes, json_encode($assessment, JSON_THROW_ON_ERROR));
        }
    }

    public function test_binary_fixture_at_exact_size_boundary_is_not_returned_or_persisted(): void
    {
        $bytes = str_repeat("\0\xff", 16384); // 32 KiB precisely.
        $assessment = (new SyntheticBackupIntegrityInspector())->inspect($this->manifest($bytes), $bytes);
        self::assertTrue($assessment->matchesSelfDeclaredDigest());
        self::assertFalse($assessment->canRestore());
        self::assertSame(['reasonCode'], array_keys(get_object_vars($assessment)));
    }

    public function test_malformed_claims_are_rejected_with_one_generic_non_disclosing_error(): void
    {
        $bytes = 'fake';
        $valid = $this->manifest($bytes);
        $invalid = [
            [[], $bytes],
            [array_replace($valid, ['format' => 'pg_basebackup']), $bytes],
            [array_replace($valid, ['format' => 'synthetic-blob-v2']), $bytes],
            [array_replace($valid, ['declared_bytes' => 0]), $bytes],
            [array_replace($valid, ['declared_bytes' => -1]), $bytes],
            [array_replace($valid, ['declared_bytes' => '4']), $bytes],
            [array_replace($valid, ['declared_bytes' => 32769]), $bytes],
            [array_replace($valid, ['sha256' => strtoupper($valid['sha256'])]), $bytes],
            [array_replace($valid, ['sha256' => 'not-a-digest']), $bytes],
            [array_replace($valid, ['recorded_at_utc' => '2026-02-30T03:00:00Z']), $bytes],
            [array_replace($valid, ['recorded_at_utc' => '2026-11-10T03:00:00+00:00']), $bytes],
            [array_replace($valid, ['recorded_at_utc' => '2026-11-10 03:00:00']), $bytes],
            [array_replace($valid, ['recorded_at_utc' => null]), $bytes],
            [array_replace($valid, ['tenant_id' => 'forged']), $bytes],
            [array_replace($valid, ['owner_id' => 'forged']), $bytes],
            [array_replace($valid, ['source_proof' => 'forged']), $bytes],
            [array_replace($valid, ['extra' => true]), $bytes],
            [$valid, ''],
            [$valid, str_repeat('x', 32769)],
        ];
        $inspector = new SyntheticBackupIntegrityInspector();
        foreach ($invalid as [$manifest, $content]) {
            try {
                $inspector->inspect($manifest, $content);
                self::fail('Unexpected accepted synthetic manifest.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Manifesto sintetico de integridade invalido.', $e->getMessage());
                self::assertStringNotContainsString('forged', $e->getMessage());
                self::assertStringNotContainsString($bytes, $e->getMessage());
            }
        }
    }

    public function test_key_order_does_not_change_a_valid_claim_but_unknown_keys_are_forbidden(): void
    {
        $bytes = 'fake';
        $manifest = array_reverse($this->manifest($bytes), true);
        $assessment = (new SyntheticBackupIntegrityInspector())->inspect($manifest, $bytes);
        self::assertTrue($assessment->matchesSelfDeclaredDigest());
        self::assertFalse($assessment->canAcceptAsVerifiedBackup());
    }
}
