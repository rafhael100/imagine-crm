<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * F243 — diagnostic callback for the private CRM F248 HTTP *test* ingress.
 *
 * Not bound to the app/container or routes. It can never grant IAM, read
 * CRM records or emit a public ALLOW. Only exact disposable CI environment.
 * Calls the ORIGINAL pinned Agents Ed25519/scope classes and existing CRM PG.
 */
final readonly class AgentsPrivatePgDiagnostic
{
    /** @param array<string,mixed> $trustedPublicKeys
     *  @param array<string,mixed> $trustedScope */
    public function __construct(
        private AiaPgNonceCandidateLedger $ledger,
        private array $trustedPublicKeys,
        private array $trustedScope,
        private string $serverTenant,
        private int $now,
    ) {}

    /** True means a synthetic diagnostic consumed a PG nonce; NEVER access. */
    public function inspect(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
    ): bool {
        try {
            if (getenv('AIA_F243_PG_LAB') !== 'true'
                || getenv('CRM_AIA_F248_PARITY_LAB') !== 'true'
                || getenv('GITHUB_ACTIONS') !== 'true'
                || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
                || getenv('APP_ENV') !== 'testing'
                || getenv('DB_DATABASE') !== 'imagine_crm_ci'
                || getenv('DB_HOST') !== '127.0.0.1'
                || getenv('DB_USERNAME') !== 'postgres'
                || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
                || !is_string($this->trustedScope['tenant_ref'] ?? null)
                || $this->trustedScope['tenant_ref'] !== $this->serverTenant
                || !\ImagineAI\MockBridge\MockSyntheticScopePreflight::matches(
                    $canonicalRequest, $this->trustedScope
                )) {
                return false;
            }

            $proof = new \ImagineAI\MockBridge\MockServiceIdentityPreflight(1);
            if (!$proof->verifyOnce(
                $canonicalRequest, $canonicalClaims, $signatureBase64,
                $this->trustedPublicKeys, $this->serverTenant, $this->now
            )) {
                return false;
            }

            $claims = json_decode($canonicalClaims, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($claims) || !is_string($claims['nonce'] ?? null)) {
                return false;
            }
            // CRM's existing PG ledger enforces unique issuer/audience/nonce,
            // scoped to the exact disposable CI database before any query.
            return $this->ledger->claim(
                $claims['nonce'], $this->serverTenant, $this->now
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
