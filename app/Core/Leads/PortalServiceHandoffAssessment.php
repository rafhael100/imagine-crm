<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-46: pure, metadata-only interoperability assessment.
 *
 * A valid MAC with a caller-provided key/binding is NOT trusted service
 * authentication, membership authorization, replay protection, consent,
 * storage, an operational CRM receipt, or permission to acknowledge a lead.
 */
final readonly class PortalServiceHandoffAssessment
{
    public function __construct(public string $reasonCode) {}

    public function isSyntacticallyConsistent(): bool
    {
        return $this->reasonCode === 'LAB_REQUEST_MAC_MATCH_UNTRUSTED';
    }

    public function canAuthenticateService(): bool { return false; }
    public function canAcceptLead(): bool { return false; }
    public function canPersistLead(): bool { return false; }
    public function canAcknowledgeVisitor(): bool { return false; }
    public function canRetryUnknownOutcome(): bool { return false; }
    public function canEnableRoute(): bool { return false; }
    public function canDeliverToCrm(): bool { return false; }
}
