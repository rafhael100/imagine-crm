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
