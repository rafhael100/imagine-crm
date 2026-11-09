<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Pure hypothetical CAS result. A proposed next version is NOT a write.
 */
final readonly class SyntheticIdempotencyCasAssessment
{
    public function __construct(
        public string $reasonCode,
        public ?int $proposedNextVersion = null,
    ) {}

    public function canCommitReservation(): bool
    {
        return false;
    }

    public function canRetryDelivery(): bool
    {
        return false;
    }

    public function canCreateLead(): bool
    {
        return false;
    }
}
