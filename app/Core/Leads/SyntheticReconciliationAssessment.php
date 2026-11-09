<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Minimal diagnostic only; does not expose customer payloads or grant authority.
 */
final readonly class SyntheticReconciliationAssessment
{
    public function __construct(public string $reasonCode) {}

    public function canMarkDelivered(): bool
    {
        return false;
    }

    public function canScheduleRetry(): bool
    {
        return false;
    }

    public function canDeleteTemporaryPayload(): bool
    {
        return false;
    }

    public function canAcknowledgeVisitor(): bool
    {
        return false;
    }
}
