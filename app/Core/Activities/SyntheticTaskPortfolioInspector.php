<?php

declare(strict_types=1);

namespace App\Core\Activities;

use InvalidArgumentException;

/**
 * F3-152 — Pure synthetic worklist composition: inspect multiple fictional
 * lifecycle histories and rank pending tasks without any task identifiers,
 * users, contact data, persistence, outbound effects or actual SLA promises.
 */
final class SyntheticTaskPortfolioInspector
{
    private const MAX_TASKS = 10;
    private const PHASE_ORDER = ['overdue' => 0, 'due_now' => 1, 'upcoming' => 2];
    private const PRIORITY_ORDER = ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];

    /**
     * @param mixed $tasks List of 1..10 nonempty event histories validated
     *                     by SyntheticTaskLifecycleInspector.
     * @param mixed $observedAtUtc Canonical UTC inspection instant.
     * @return array<string,mixed> Closed metadata-only, deny-all projection.
     */
    public function inspect(mixed $tasks, mixed $observedAtUtc): array
    {
        if (!is_array($tasks) || !array_is_list($tasks)
            || count($tasks) < 1 || count($tasks) > self::MAX_TASKS) {
            self::invalid();
        }

        $summaries = [];
        $phaseCounts = [
            'overdue' => 0, 'due_now' => 0,
            'upcoming' => 0, 'closed' => 0,
        ];
        $openPositions = [];
        $priorityCounts = ['urgent' => 0, 'high' => 0, 'normal' => 0, 'low' => 0];

        try {
            $inspector = new SyntheticTaskLifecycleInspector();
            foreach ($tasks as $position => $history) {
                if (!is_array($history) || !array_is_list($history) || $history === []) {
                    self::invalid();
                }
                $result = $inspector->inspect($history, $observedAtUtc);
                $phase = $result['due_phase'];
                $priority = $result['claimed_priority'];
                if (!array_key_exists($phase, $phaseCounts)) {
                    self::invalid();
                }
                ++$phaseCounts[$phase];
                if ($phase === 'closed') {
                    continue;
                }
                if (!is_string($priority) || !array_key_exists($priority, $priorityCounts)) {
                    self::invalid();
                }
                ++$priorityCounts[$priority];
                $summaries[$position] = [
                    'phase_order' => self::PHASE_ORDER[$phase],
                    'priority_order' => self::PRIORITY_ORDER[$priority],
                ];
                $openPositions[] = $position;
            }
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        usort($openPositions, static function (int $left, int $right) use ($summaries): int {
            return ($summaries[$left]['phase_order'] <=> $summaries[$right]['phase_order'])
                ?: ($summaries[$left]['priority_order'] <=> $summaries[$right]['priority_order'])
                ?: ($left <=> $right);
        });

        return [
            'task_count' => count($tasks),
            'open_count' => count($openPositions),
            'closed_count' => $phaseCounts['closed'],
            'overdue_count' => $phaseCounts['overdue'],
            'due_now_count' => $phaseCounts['due_now'],
            'upcoming_count' => $phaseCounts['upcoming'],
            'open_priority_counts' => $priorityCounts,
            'ordered_open_positions' => $openPositions,
            'source_history_verified' => false,
            'task_identity_verified' => false,
            'tenant_scope_verified' => false,
            'sla_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_tasks' => false,
            'can_assign' => false,
            'can_update_task' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Carteira sintetica de atividades invalida.');
    }
}
