<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-50: an OP-01 PUBLIC response is a fictional fixture, never a CRM grant.
 * The reason code contains no tenant, principal, lead, policy or secret.
 */
final readonly class AgentsReadResponseAssessment
{
    public function __construct(public string $reasonCode) {}

    public function isSyntheticFormatConsistent(): bool
    {
        return in_array($this->reasonCode, [
            'SYNTHETIC_DENIAL_ONLY',
            'SYNTHETIC_PROJECTION_UNTRUSTED',
        ], true);
    }

    public function canReadRealLead(): bool { return false; }
    public function canUseCustomerData(): bool { return false; }
    public function canGrantAgentAccess(): bool { return false; }
    public function canWriteLead(): bool { return false; }
    public function canExportLead(): bool { return false; }
    public function canEnableRoute(): bool { return false; }
}
