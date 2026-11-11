<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * Pure preview of the offline Kanban's visible bulk forward/backward action.
 * Validates the COMPLETE synthetic snapshot before filtering; calculates only
 * aggregate changes, without mutating the caller's records or granting writes.
 */
final class SyntheticKanbanBatchMoveInspector
{
    public function inspect(
        mixed $records,
        mixed $direction,
        mixed $priority = 'all',
        mixed $followup = 'all',
        mixed $stage = 'all',
    ): SyntheticKanbanBatchMoveSummary {
        // An invalid hidden row must never disappear via filtering.
        (new SyntheticKanbanCsvPreviewBuilder())->render($records);
        // Reuse the exact same filter validation as the count-only inspector.
        (new SyntheticKanbanFilteredStageInspector())->inspect($records, $priority, $followup, $stage);

        if ($direction !== -1 && $direction !== 1) {
            throw new InvalidArgumentException('Movimentacao Kanban ficticia invalida.');
        }

        $preview = $records;
        $moved = 0;
        foreach ($records as $index => $record) {
            $destination = $record['stage'] + $direction;
            if (($priority === 'all' || $record['priority'] === $priority)
                && ($followup === 'all' || $record['followup'] === $followup)
                && ($stage === 'all' || $record['stage'] === $stage)
                && $destination >= 0 && $destination <= 3) {
                $preview[$index]['stage'] = $destination;
                ++$moved;
            }
        }

        $inspector = new SyntheticKanbanStageInspector();

        return new SyntheticKanbanBatchMoveSummary(
            $inspector->inspect($records),
            $inspector->inspect($preview),
            $moved,
        );
    }
}
