<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/**
 * Resultado seguro inicial. A politica efetiva de allow/deny ainda
 * depende de CORE02-D05/D06/D08, SEG-01/02 e proprietarios de modulo.
 */
final readonly class AuthorizationDecision
{
    private function __construct(
        public bool $allowed,
        public string $reasonCode,
    ) {}

    public static function deny(string $reasonCode = 'POLICY_NOT_CONFIGURED'): self
    {
        return new self(false, $reasonCode);
    }
}
