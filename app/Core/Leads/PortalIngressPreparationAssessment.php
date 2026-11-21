<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-48: single result from the fail-closed, pure technical ingress pipeline.
 *
 * NO caller can interpret this result as authenticated service identity,
 * tenant authorization, consent, accepted lead, database commitment, trusted
 * CRM receipt, replay-safe state or production release.
 */
final readonly class PortalIngressPreparationAssessment
{
    public function __construct(public string $reasonCode) {}

    public function isMetadataSyntaxAndMacConsistent(): bool
    {
        return $this->reasonCode === 'LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED';
    }

    public function canAuthenticateService(): bool { return false; }
    public function canAcceptLead(): bool { return false; }
    public function canPersistLead(): bool { return false; }
    public function canAcknowledgeVisitor(): bool { return false; }
    public function canQueueDelivery(): bool { return false; }
    public function canRetryUnknownOutcome(): bool { return false; }
    public function canOpenPublicRoute(): bool { return false; }
}
