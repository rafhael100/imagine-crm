<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * A nonce claim is only anti-replay evidence, NEVER CRM authorization.
 * Implementations are restricted to a disposable, explicit CI laboratory.
 */
interface AiaNonceClaimSource
{
    public function claim(string $nonce, string $tenantRef, int $claimedAt): bool;
}
