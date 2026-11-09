<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Closed diagnostic. No event ID, tenant, actor, field or content is returned.
 * This is never an authorized history or a business event confirmation.
 */
final readonly class SyntheticLeadHistoryAssessment
{
    public function __construct(public string $reasonCode) {}

    /** @return array{code: string} */
    public function signal(): array
    {
        return ['code' => $this->reasonCode];
    }

    public function canReadHistory(): bool
    {
        return false;
    }

    public function canExportHistory(): bool
    {
        return false;
    }

    public function canWriteHistory(): bool
    {
        return false;
    }

    public function canProveActorOrChronology(): bool
    {
        return false;
    }
}
