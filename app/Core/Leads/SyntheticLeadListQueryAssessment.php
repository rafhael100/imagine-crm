<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Minimal, non-disclosing diagnostic for an untrusted list-query proposal.
 *
 * No tenant, subject, cursor, field, row or search string is included.
 * Even a structurally coherent proposal is never authorization to list leads.
 */
final readonly class SyntheticLeadListQueryAssessment
{
    public function __construct(public string $reasonCode) {}

    /** @return array{code: string} */
    public function signal(): array
    {
        return ['code' => $this->reasonCode];
    }

    public function canListLeads(): bool
    {
        return false;
    }

    public function canReadLead(): bool
    {
        return false;
    }

    public function canExportLeads(): bool
    {
        return false;
    }

    public function canRevealFields(): bool
    {
        return false;
    }
}
