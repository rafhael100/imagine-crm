<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Pure description of two untrusted technical submission-reference CLAIMS.
 *
 * It contains no reference values, names, tenant identity proof, contact
 * data, fingerprints or payload. Classification is never authorization.
 */
final readonly class SyntheticSubmissionClaimAssessment
{
    public function __construct(public string $reasonCode) {}

    public function canTreatAsAuthorizedRetry(): bool
    {
        return false;
    }

    public function canInferContactDuplicate(): bool
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
