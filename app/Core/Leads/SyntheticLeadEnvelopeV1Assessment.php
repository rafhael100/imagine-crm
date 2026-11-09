<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Metadata-only result. No IDs or lead fields leave the isolated check.
 *
 * A matching protocol version and valid UUID syntax NEVER authenticate the
 * site, its binding, the intended CRM tenant or legal processing purpose.
 */
final readonly class SyntheticLeadEnvelopeV1Assessment
{
    public function __construct(public string $reasonCode) {}

    public function isStructurallyCompatible(): bool
    {
        return $this->reasonCode === 'STRUCTURE_ONLY_UNVERIFIED';
    }

    public function canAcceptSubmission(): bool
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
}
