<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * EXPERIMENTAL F3-AIA: prove one signed *synthetic* JSON byte string.
 *
 * The prefix of the key is a deliberate, public anti-production guard.
 * Successful verification is NOT CRM authorization, service IAM, owner ACL
 * or permission to read a lead. No route or service binds this class.
 */
final class AiaHmacFixturePreflight
{
    private const KEYS = [
        'issuer', 'audience', 'tenant_ref', 'nonce',
        'issued_at', 'expires_at', 'body_sha256', 'signature',
    ];
    private const ISSUER = 'SYNTHETIC_AGENT_SERVICE';
    private const AUDIENCE = 'SYNTHETIC_CRM_API';
    private const DOMAIN = 'SYNTHETIC_AIA_HMAC_PROOF_V1';
    private const KEY_PREFIX = 'SYNTHETIC_PUBLIC_TEST_ONLY_';

    /**
     * @param array<mixed> $proof Source data, never a security principal.
     */
    public static function matchesFixture(
        string $json,
        array $proof,
        string $expectedTenant,
        int $now,
        string $fixtureKey,
        AiaFixtureNonceLedger $ledger,
    ): bool {
        try {
            if ($now < 0 || $now > 4102444800
                || strlen($fixtureKey) < 48
                || !str_starts_with($fixtureKey, self::KEY_PREFIX)
                || array_keys($proof) !== self::KEYS
                || !AiaReadOnlyMockBoundary::hasCanonicalFixtureShape($json)) {
                return false;
            }
            $wire = json_decode($json, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($wire)
                || !is_string($expectedTenant)
                || !preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D', $expectedTenant)
                || $wire['tenant_ref'] !== $expectedTenant
                || $proof['tenant_ref'] !== $expectedTenant
                || $proof['issuer'] !== self::ISSUER
                || $proof['audience'] !== self::AUDIENCE
                || !is_string($proof['nonce'])
                || strlen($proof['nonce']) > 90
                || preg_match('/\ASYNTHETIC_[A-Z0-9_]{4,80}\z/D', $proof['nonce']) !== 1
                || !is_int($proof['issued_at'])
                || !is_int($proof['expires_at'])
                || $proof['issued_at'] < 0
                || $proof['expires_at'] <= $proof['issued_at']
                || $proof['expires_at'] > 4102444800
                || $proof['expires_at'] - $proof['issued_at'] > 30
                || $now < $proof['issued_at']
                || $now >= $proof['expires_at']
                || !is_string($proof['body_sha256'])
                || preg_match('/\A[0-9a-f]{64}\z/D', $proof['body_sha256']) !== 1
                || !is_string($proof['signature'])
                || preg_match('/\A[0-9a-f]{64}\z/D', $proof['signature']) !== 1) {
                return false;
            }
            $digest = hash('sha256', $json);
            if (!hash_equals($digest, $proof['body_sha256'])) {
                return false;
            }
            $message = implode("\n", [
                self::DOMAIN,
                $proof['issuer'],
                $proof['audience'],
                $proof['tenant_ref'],
                $proof['nonce'],
                (string) $proof['issued_at'],
                (string) $proof['expires_at'],
                $digest,
            ]);
            $expectedSignature = hash_hmac('sha256', $message, $fixtureKey);
            if (!hash_equals($expectedSignature, $proof['signature'])) {
                return false;
            }

            // Local/in-process only. This is deliberately NOT a distributed,
            // atomic, durable or production-grade replay store.
            return $ledger->claim($proof['nonce']);
        } catch (\Throwable) {
            // Any decoder, storage or unexpected error must fail closed.
            return false;
        }
    }

    /**
     * Identical public result irrespective of verification outcome.
     * No caller may treat this method's invocation as real authorization.
     *
     * @param array<mixed> $proof
     * @return array{contract:string,state:string,projection:null,real_authorization:false,external_calls:0}
     */
    public static function inspectFixture(
        string $json,
        array $proof,
        string $expectedTenant,
        int $now,
        string $fixtureKey,
        AiaFixtureNonceLedger $ledger,
    ): array {
        self::matchesFixture(
            $json, $proof, $expectedTenant, $now, $fixtureKey, $ledger
        );
        return AiaReadOnlyMockBoundary::inspectCanonicalJson($json);
    }
}
