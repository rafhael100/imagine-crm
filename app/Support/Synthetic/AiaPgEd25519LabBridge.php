<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use ImagineAI\MockBridge\MockServiceIdentityPreflight;

/**
 * F232: Ed25519 service proof followed by ONE disposable PostgreSQL nonce
 * claim. This is an isolated compatibility experiment, not CRM IAM or ACL.
 *
 * It never emits an ALLOW, reads business data, creates a token or registers
 * a service binding/route. The ledger itself checks exact ephemeral CI DB
 * and runtime identity before any DB access.
 */
final class AiaPgEd25519LabBridge
{
    /**
     * A true result means only that the synthetic proof and atomic replay
     * primitive worked. It is never a grant to read CRM records.
     *
     * @param array<string,array{public_key:string,enabled:bool}> $trustedKeys
     */
    public static function matchesLabFixture(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        array $trustedKeys,
        string $expectedTenant,
        int $now,
        AiaPgNonceCandidateLedger $ledger,
    ): bool {
        try {
            // Source of identity verification is the exact pinned Agents
            // F231 PHP implementation; no copied or weaker local verifier.
            $proof = new MockServiceIdentityPreflight(1);
            if (!$proof->verifyOnce(
                $canonicalRequest, $canonicalClaims, $signatureBase64,
                $trustedKeys, $expectedTenant, $now,
            )) {
                return false;
            }

            // No input field can substitute an independently checked nonce.
            // The above verifier accepts only canonical, signed claims.
            $claims = json_decode($canonicalClaims, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($claims) || !is_string($claims['nonce'] ?? null)) {
                return false;
            }

            // Real PostgreSQL UNIQUE index (issuer,audience,nonce) ensures
            // replay rejection across independent CI database connections.
            // Its own strict disposable-lab guard runs BEFORE database I/O.
            return $ledger->claim(
                $claims['nonce'], $expectedTenant, $now,
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * F232b: obtain the public verification key from the CRM-owned
     * ephemeral PostgreSQL registry, never from a request header/claim
     * or the caller. A successful proof still grants ZERO CRM access.
     */
    public static function matchesWithServerKeyring(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        string $expectedTenant,
        int $now,
        AiaPgServiceKeyringLab $keyring,
        AiaPgNonceCandidateLedger $ledger,
    ): bool {
        try {
            if ($canonicalClaims === '' || strlen($canonicalClaims) > 1024) {
                return false;
            }
            $claims = json_decode($canonicalClaims, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($claims) || !is_string($claims['key_id'] ?? null)) {
                return false;
            }
            $encoded = json_encode(
                $claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            );
            if (!hash_equals($encoded, $canonicalClaims)) {
                return false;
            }
            $trusted = $keyring->trustedPublicKey(
                $claims['key_id'], $expectedTenant,
            );
            if ($trusted === []) {
                return false;
            }
            return self::matchesLabFixture(
                $canonicalRequest, $canonicalClaims, $signatureBase64,
                $trusted, $expectedTenant, $now, $ledger,
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * F233: authenticated synthetic service + server-managed key + atomic
     * nonce + server-owned delegation/owner/field-grant rehearsal.
     *
     * A match is a diagnostic only. There is NO production ALLOW, no
     * business read, no IAM and no API route. Policy is inspected only
     * AFTER original Agents Ed25519 and PG nonce checks succeed.
     */
    public static function matchesWithServerScope(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        string $expectedTenant,
        int $now,
        AiaPgServiceKeyringLab $keyring,
        AiaPgNonceCandidateLedger $ledger,
        AiaPgServiceScopeLab $scope,
    ): bool {
        try {
            if (!self::matchesWithServerKeyring(
                $canonicalRequest, $canonicalClaims, $signatureBase64,
                $expectedTenant, $now, $keyring, $ledger,
            )) {
                return false;
            }

            $verifiedClaims = json_decode(
                $canonicalClaims, true, 4, JSON_THROW_ON_ERROR
            );
            if (!is_array($verifiedClaims)) {
                return false;
            }

            return $scope->matchesCandidate(
                $canonicalRequest, $verifiedClaims, $expectedTenant, $now,
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /** Public status is ALWAYS denied, regardless of matching test scope. */
    public static function inspectWithServerScope(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        string $expectedTenant,
        int $now,
        AiaPgServiceKeyringLab $keyring,
        AiaPgNonceCandidateLedger $ledger,
        AiaPgServiceScopeLab $scope,
    ): array {
        self::matchesWithServerScope(
            $canonicalRequest, $canonicalClaims, $signatureBase64,
            $expectedTenant, $now, $keyring, $ledger, $scope,
        );

        return AiaReadOnlyMockBoundary::inspectCanonicalJson($canonicalRequest);
    }

    /** Uniform denial even after passing PostgreSQL-backed public-key lookup. */
    public static function inspectWithServerKeyring(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        string $expectedTenant,
        int $now,
        AiaPgServiceKeyringLab $keyring,
        AiaPgNonceCandidateLedger $ledger,
    ): array {
        self::matchesWithServerKeyring(
            $canonicalRequest, $canonicalClaims, $signatureBase64,
            $expectedTenant, $now, $keyring, $ledger,
        );
        return AiaReadOnlyMockBoundary::inspectCanonicalJson($canonicalRequest);
    }

    /**
     * Public-facing lab result is always a uniform denial. The Ed25519
     * verification result must NEVER influence the exposed CRM read status.
     *
     * @param array<string,array{public_key:string,enabled:bool}> $trustedKeys
     * @return array{contract:string,state:string,projection:null,real_authorization:false,external_calls:0}
     */
    public static function inspectLabFixture(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        array $trustedKeys,
        string $expectedTenant,
        int $now,
        AiaPgNonceCandidateLedger $ledger,
    ): array {
        self::matchesLabFixture(
            $canonicalRequest, $canonicalClaims, $signatureBase64,
            $trustedKeys, $expectedTenant, $now, $ledger,
        );

        return AiaReadOnlyMockBoundary::inspectCanonicalJson($canonicalRequest);
    }
}
