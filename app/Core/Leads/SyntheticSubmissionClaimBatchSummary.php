<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-78: position-only warning summary for untrusted technical submissions.
 *
 * No organization identifier, submission reference, hash, contact data,
 * or payload is retained. This is NOT a durable idempotency ledger.
 */
final readonly class SyntheticSubmissionClaimBatchSummary
{
    /**
     * @param list<array{first: int, repeated: int}> $sameOrganizationClaimPositions
     * @param list<array{first: int, conflicting: int}> $crossOrganizationClaimPositions
     */
    public function __construct(
        public int $claimsInspected,
        public array $sameOrganizationClaimPositions,
        public array $crossOrganizationClaimPositions,
    ) {}

    public function hasRepeatedSubmissionClaims(): bool
    {
        return $this->sameOrganizationClaimPositions !== [];
    }

    public function hasCrossOrganizationConflicts(): bool
    {
        return $this->crossOrganizationClaimPositions !== [];
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
