<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Read-only count summary of fictional followup completion, never a write.
 * Never returns ticket IDs, labels, values or contact-like fields.
 */
final readonly class SyntheticKanbanBatchFollowupSummary
{
    public function __construct(
        public SyntheticKanbanStageSummary $before,
        public SyntheticKanbanStageSummary $after,
        public int $completed,
    ) {}

    public function canCompleteRealFollowups(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }
}
