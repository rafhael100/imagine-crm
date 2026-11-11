<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Position-only warnings about conflicting untrusted technical references.
 *
 * No identifiers, payloads, consent, contacts or hashes in the result.
 * A structurally consistent batch never proves authenticated delivery.
 */
final readonly class SyntheticSubmissionBindingBatchSummary
{
    /**
     * @param list<array{first: int, repeated: int}> $sameClaimPositions
     * @param list<array{first: int, conflicting: int}> $bindingConflictPositions
     * @param list<array{first: int, conflicting: int}> $organizationConflictPositions
     */
    public function __construct(
        public int $claimsInspected,
        public array $sameClaimPositions,
        public array $bindingConflictPositions,
        public array $organizationConflictPositions,
    ) {}

    public function hasAnyCollisionWarnings(): bool
    {
        return $this->sameClaimPositions !== []
            || $this->bindingConflictPositions !== []
            || $this->organizationConflictPositions !== [];
    }

    public function hasBindingConflicts(): bool
    {
        return $this->bindingConflictPositions !== [];
    }

    public function canTreatAsAuthorizedRetry(): bool
    {
        return false;
    }

    public function canMergeContacts(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }

    public function canAcknowledgeDelivery(): bool
    {
        return false;
    }
}
