<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Unverified in-memory example of a ledger record, not a DB transaction.
 */
final readonly class SyntheticIdempotencySnapshot
{
    public function __construct(
        public SyntheticIdempotencyClaim $original,
        public SyntheticIdempotencyPhase $phase,
        public int $version,
    ) {
        if ($version < 0) {
            throw new InvalidArgumentException('Synthetic ledger version invalid.');
        }
    }
}
