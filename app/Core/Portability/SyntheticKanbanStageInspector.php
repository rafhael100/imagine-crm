<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Read-only numerical projection of strictly fabricated Kanban snapshots.
 * Delegates all input validation to the existing synthetic CSV contract;
 * no raw identifier/cell/label is returned.
 */
final class SyntheticKanbanStageInspector
{
    public function inspect(mixed $records): SyntheticKanbanStageSummary
    {
        // Throws the existing generic exception before constructing a result
        // if a row is invalid, duplicated, oversized or contains extra fields.
        (new SyntheticKanbanCsvPreviewBuilder())->render($records);

        $byStage = [0, 0, 0, 0];
        $high = 0;
        $pending = 0;
        foreach ($records as $record) {
            ++$byStage[$record['stage']];
            if ($record['priority'] === 'high') {
                ++$high;
            }
            if ($record['followup'] === 'pending') {
                ++$pending;
            }
        }

        return new SyntheticKanbanStageSummary($byStage, count($records), $high, $pending);
    }
}
