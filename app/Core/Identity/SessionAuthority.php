<?php

declare(strict_types=1);

namespace App\Core\Identity;

/** Trusted server-side proof, not a caller-supplied identity/session snapshot. */
interface SessionAuthority
{
    public function assertCurrent(string $identityId): void;
}
