<?php

declare(strict_types=1);

namespace App\Core\Plans;

/**
 * Read-only description of a supplied synthetic quota snapshot.
 *
 * A band is not a permission, limit enforcement or billing entitlement.
 */
enum QuotaSnapshotBand: string
{
    case ZeroCapacity = 'zero_capacity';
    case InRange = 'in_range';
    case AtLimit = 'at_limit';
    case OverLimit = 'over_limit';
}
