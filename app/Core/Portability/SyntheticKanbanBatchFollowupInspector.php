<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Pure, bounded preview of the fictitious Kanban "complete visible" action.
 * Full input and filters are validated before any selection, so malformed
 * rows outside the visible subset are never silently ignored.
 */
final class SyntheticKanbanBatchFollowupInspector
{
    public function inspect(
        mixed $records,
        mixed $priority = 'all',
        mixed $followup = 'all',
        mixed $stage = 'all',
    ): SyntheticKanbanBatchFollowupSummary {
        // The filtered inspector validates ALL rows against the strict CSV
        // contract, and rejects unknown filter types and values.
        (new SyntheticKanbanFilteredStageInspector())->inspect(
            $records, $priority, $followup, $stage,
        );

        $preview = $records;
        $completed = 0;
        foreach ($records as $index => $record) {
            if (($priority === 'all' || $record['priority'] === $priority)
                && ($followup === 'all' || $record['followup'] === $followup)
                && ($stage === 'all' || $record['stage'] === $stage)
                && $record['followup'] === 'pending') {
                $preview[$index]['followup'] = 'done';
                ++$completed;
            }
        }

        $inspector = new SyntheticKanbanStageInspector();

        return new SyntheticKanbanBatchFollowupSummary(
            $inspector->inspect($records),
            $inspector->inspect($preview),
            $completed,
        );
    }
}
