<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-15 metadata-only HMAC laboratory. No signature result is an
 * authorization, trusted receiver receipt, or durable CRM write.
 */
final readonly class SyntheticLeadReceiptMacAssessment
{
    public function __construct(public string $reasonCode) {}

    public function isMacConsistentWithFixture(): bool
    {
        return $this->reasonCode === 'LAB_MAC_MATCH_UNTRUSTED';
    }

    public function canAcceptLead(): bool
    {
        return false;
    }

    public function canAcknowledgeDurableReceipt(): bool
    {
        return false;
    }

    public function canDeliverToCrm(): bool
    {
        return false;
    }

    public function canTreatAsReplaySafe(): bool
    {
        // No durable, atomic nonce ledger exists in this component.
        return false;
    }
}
