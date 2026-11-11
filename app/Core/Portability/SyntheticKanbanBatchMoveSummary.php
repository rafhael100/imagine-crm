<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Count-only, non-operational forecast of a fictitious Kanban stage movement.
 * Neither the input records nor identifiers are ever exposed by this summary.
 */
final readonly class SyntheticKanbanBatchMoveSummary
{
    public function __construct(
        public SyntheticKanbanStageSummary $before,
        public SyntheticKanbanStageSummary $after,
        public int $moved,
    ) {}

    public function canChangeRealStages(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }
}
