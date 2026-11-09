<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Identity\PrincipalReference;
use Closure;

/** Trusted repository port. A snapshot supplied by a caller is never accepted. */
interface ActiveMembershipStore
{
    /** Re-read current membership and keep the operation inside its transaction. */
    public function within(PrincipalReference $principal, string $tenantId, Closure $operation): mixed;
}
