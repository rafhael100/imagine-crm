<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Neutral lab diagnostic only: never a receipt, authorization or CRM delivery confirmation.
 * No customer identifiers or payload values are retained in this result.
 */
final readonly class SyntheticDeliveryEvidenceAssessment
{
    public function __construct(public string $reasonCode) {}

    public function canAcknowledgeVisitor(): bool
    {
        // A durable write in a trusted receiver has NOT been observed here.
        return false;
    }

    public function canMarkDeliveredToCrm(): bool
    {
        // A trusted, tenant-bound CRM persistence proof has NOT been checked here.
        return false;
    }

    public function canRetryAutomatically(): bool
    {
        // Requires a trusted idempotency ledger + concurrency protocol.
        return false;
    }
}
