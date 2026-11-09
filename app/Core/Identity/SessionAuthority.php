<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Closure;

/** Trusted server-side proof, not a caller-supplied identity/session snapshot. */
interface SessionAuthority
{
    public function assertCurrent(string $identityId): void;

    /** Revalidate and hold credential/session locks until the operation returns. */
    public function withinCurrent(string $identityId, Closure $operation, bool $recent = false): mixed;
}
