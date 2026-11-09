<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Aggregate-only structural assessment for a synthetic Imagine Site form.
 *
 * This object deliberately never returns, logs, stores or authorizes
 * personal fields and is never proof of consent or delivery to CRM.
 */
final readonly class ImagineSiteFormMinimumAssessment
{
    public function __construct(
        public bool $namePresent,
        public bool $emailCandidateValid,
        public bool $phoneCandidateValid,
        public bool $invalidSuppliedContact,
    ) {}

    public function meetsStructuralMinimum(): bool
    {
        return $this->namePresent
            && ($this->emailCandidateValid || $this->phoneCandidateValid)
            && !$this->invalidSuppliedContact;
    }

    public function canDeliver(): bool
    {
        // Form completeness is not proof of lawful processing, durable
        // ingestion, binding, authorization, anti-spam or real delivery.
        return false;
    }
}
