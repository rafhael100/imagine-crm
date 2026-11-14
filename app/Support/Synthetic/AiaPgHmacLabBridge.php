<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * One isolated end-to-end laboratory proof: signed synthetic body then
 * PostgreSQL nonce insert. Not a service authorizer or an operational API.
 *
 * The HMAC key is intentionally public, fixture-only and forgeable.
 * The PostgreSQL ledger itself only works in the exact disposable CI DB.
 */
final class AiaPgHmacLabBridge
{
    /**
     * Purely a technical lab probe. A true result grants ZERO CRM rights.
     *
     * @param array<mixed> $proof
     */
    public static function matchesLabFixture(
        string $canonicalBody,
        array $proof,
        string $expectedTenant,
        int $now,
        string $publicTestKey,
        AiaPgNonceCandidateLedger $ledger,
    ): bool {
        try {
            // The regular mock HMAC verifier checks full canonical framing,
            // issuer/audience, tenant, nonce, TTL and exact raw-body digest.
            // Its local ledger is disposable and NOT the shared replay store.
            $localProofOnly = new AiaFixtureNonceLedger(1);
            if (!AiaHmacFixturePreflight::matchesFixture(
                $canonicalBody, $proof, $expectedTenant, $now,
                $publicTestKey, $localProofOnly,
            )) {
                return false;
            }

            // The only shared state is in an ephemeral PostgreSQL table,
            // reached after the complete signature check. No real IAM.
            return $ledger->claim($proof['nonce'], $expectedTenant, $now);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Uniform denial regardless of signed proof or nonce claim outcome.
     *
     * @param array<mixed> $proof
     * @return array{contract:string,state:string,projection:null,real_authorization:false,external_calls:0}
     */
    public static function inspectLabFixture(
        string $canonicalBody,
        array $proof,
        string $expectedTenant,
        int $now,
        string $publicTestKey,
        AiaPgNonceCandidateLedger $ledger,
    ): array {
        self::matchesLabFixture(
            $canonicalBody, $proof, $expectedTenant,
            $now, $publicTestKey, $ledger,
        );
        return AiaReadOnlyMockBoundary::inspectCanonicalJson($canonicalBody);
    }
}
