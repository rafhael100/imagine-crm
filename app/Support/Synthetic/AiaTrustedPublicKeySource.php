<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * F240: only a public Ed25519 trust anchor; never a proof of permission.
 *
 * Implementations are test-only. A successful lookup cannot authorize
 * a CRM user, create an Agent identity or release customer records.
 */
interface AiaTrustedPublicKeySource
{
    /** @return array<string,array{public_key:string,enabled:bool}> */
    public function trustedPublicKey(string $keyId, string $expectedTenant): array;
}
