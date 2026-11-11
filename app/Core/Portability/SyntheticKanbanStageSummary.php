<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Fixed-size, count-only view of invented Kanban fixtures (F01..F12).
 * Does not contain identifiers, labels, values, contacts or tenant references.
 */
final readonly class SyntheticKanbanStageSummary
{
    /**
     * @param array{0:int,1:int,2:int,3:int} $stageCounts
     * @param array{0:int,1:int,2:int,3:int} $highPriorityByStage
     * @param array{0:int,1:int,2:int,3:int} $pendingByStage
     */
    public function __construct(
        public array $stageCounts,
        public int $total,
        public int $highPriority,
        public int $pendingFollowups,
        public array $highPriorityByStage,
        public array $pendingByStage,
    ) {}

    public function canReadCustomerData(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }
}
