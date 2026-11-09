<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * A route's UNVERIFIED assertion about one submission, not trusted evidence.
 * Only synthetic identifiers may be used in this experimental contract.
 */
final readonly class SyntheticReceiverObservation
{
    public function __construct(
        public string $route,
        public LeadSubmissionIdentity $identity,
        public SyntheticReceiverStatus $status,
    ) {
        if (!in_array($route, ['primary', 'contingency'], true)) {
            throw new InvalidArgumentException('Synthetic receiver route invalid.');
        }
    }
}
