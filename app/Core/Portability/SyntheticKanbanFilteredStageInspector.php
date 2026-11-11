<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-94: count-only view of the SAME three filters as the offline Kanban UI.
 * All original rows are strictly validated BEFORE filtering; a hidden invalid
 * record cannot disappear through the filter and silently bypass the gate.
 *
 * No persistence, PII, customer read authority, contact operations or I/O.
 */
final class SyntheticKanbanFilteredStageInspector
{
    public function inspect(
        mixed $records,
        mixed $priorityFilter = 'all',
        mixed $followupFilter = 'all',
        mixed $stageFilter = 'all',
    ): SyntheticKanbanStageSummary {
        (new SyntheticKanbanCsvPreviewBuilder())->render($records);

        if (!in_array($priorityFilter, ['all', 'high', 'normal'], true)
            || !in_array($followupFilter, ['all', 'pending', 'done'], true)
            || ($stageFilter !== 'all' && (!is_int($stageFilter) || $stageFilter < 0 || $stageFilter > 3))) {
            throw new InvalidArgumentException('Filtro Kanban ficticio invalido.');
        }

        $selected = [];
        foreach ($records as $record) {
            if (($priorityFilter === 'all' || $record['priority'] === $priorityFilter)
                && ($followupFilter === 'all' || $record['followup'] === $followupFilter)
                && ($stageFilter === 'all' || $record['stage'] === $stageFilter)) {
                $selected[] = $record;
            }
        }

        return (new SyntheticKanbanStageInspector())->inspect($selected);
    }
}
