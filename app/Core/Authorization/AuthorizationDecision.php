<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use Illuminate\Support\Str;

/**
 * Resultado seguro inicial. A politica efetiva de allow/deny ainda
 * depende de CORE02-D05/D06/D08, SEG-01/02 e proprietarios de modulo.
 */
final readonly class AuthorizationDecision
{
    private function __construct(
        public bool $allowed,
        public string $reasonCode,
        public ?string $correlationId,
    ) {}

    public static function deny(string $reasonCode = 'POLICY_NOT_CONFIGURED', ?string $correlationId = null): self
    {
        $codes = ['POLICY_NOT_CONFIGURED', 'CONTRACT_INVALID', 'SCOPE_NOT_VERIFIED', 'CHANNEL_AUTHORITY_MISSING'];

        return new self(false, in_array($reasonCode, $codes, true) ? $reasonCode : 'CONTRACT_INVALID',
            $correlationId !== null && Str::isUuid($correlationId) ? $correlationId : null);
    }

    /** Minimal internal audit signal; never include input, identities or field values. */
    public function signal(): array
    {
        return ['code' => $this->reasonCode, 'correlation_id' => $this->correlationId];
    }
}
