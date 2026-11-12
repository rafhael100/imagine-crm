<?php

declare(strict_types=1);

namespace App\Core\Sales;

use App\Core\Activities\SyntheticTaskLifecycleInspector;
use App\Core\Activities\SyntheticTaskPortfolioInspector;
use App\Core\Leads\SyntheticLeadPriorityCapacityBatchPreview;
use InvalidArgumentException;

/**
 * F3-160: one fictional planning surface for score-first lead triage and
 * task follow-ups. All indices are local fixture positions, never IDs.
 *
 * This does not correlate leads with tasks, verify staff membership, or
 * authorize operational CRM behavior. No storage, dispatch or network calls.
 */
final class SyntheticSalesTriageWorkQueueInspector
{
    private const KEYS = ['lead_batch', 'task_histories', 'observed_at_utc'];
    private const MAX_TASKS = 10;

    public function inspect(mixed $scenario): array
    {
        if (!is_array($scenario) || count($scenario) !== count(self::KEYS)) {
            self::invalid();
        }
        $keys = array_keys($scenario);
        $expected = self::KEYS;
        sort($keys);
        sort($expected);
        if ($keys !== $expected || !is_array($scenario['task_histories'])
            || !array_is_list($scenario['task_histories'])
            || count($scenario['task_histories']) > self::MAX_TASKS) {
            self::invalid();
        }

        try {
            $lead = (new SyntheticLeadPriorityCapacityBatchPreview())
                ->preview($scenario['lead_batch']);

            if ($scenario['task_histories'] === []) {
                // Validate the observation timestamp even when there is
                // no task. An empty portfolio is a legitimate workflow.
                (new SyntheticTaskLifecycleInspector())
                    ->inspect([], $scenario['observed_at_utc']);
                $tasks = [
                    'task_count' => 0, 'open_count' => 0,
                    'overdue_count' => 0, 'due_now_count' => 0,
                    'ordered_open_positions' => [],
                ];
            } else {
                $tasks = (new SyntheticTaskPortfolioInspector())->inspect(
                    $scenario['task_histories'],
                    $scenario['observed_at_utc'],
                );
            }
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $warnings = [];
        if ($lead['waiting_count'] > 0) {
            $warnings[] = 'fictional_lead_capacity_shortfall';
        }
        if ($tasks['overdue_count'] > 0) {
            $warnings[] = 'fictional_task_backlog_overdue';
        }
        if ($tasks['due_now_count'] > 0) {
            $warnings[] = 'fictional_task_due_now';
        }
        $warnings[] = 'cross_module_authorization_unverified';

        return [
            'fictional_leads_reviewed' => $lead['leads_reviewed'],
            'fictional_unassigned_leads' => $lead['unassigned_reviewed'],
            'fictional_planned_assignments' => $lead['planned_count'],
            'fictional_waiting_leads' => $lead['waiting_count'],
            'lead_priority_positions' => $lead['ranked_unassigned_positions'],
            'lead_planned_slots' => $lead['planned_slot_pairs'],
            'lead_waiting_positions' => $lead['waiting_lead_positions'],
            'fictional_tasks_reviewed' => $tasks['task_count'],
            'fictional_open_tasks' => $tasks['open_count'],
            'fictional_overdue_tasks' => $tasks['overdue_count'],
            'fictional_due_now_tasks' => $tasks['due_now_count'],
            'task_priority_positions' => $tasks['ordered_open_positions'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'source_history_verified' => false,
            'task_identity_verified' => false,
            'agent_identity_verified' => false,
            'tenant_scope_verified' => false,
            'scoring_policy_verified' => false,
            'assignment_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_customers' => false,
            'can_assign' => false,
            'can_update_task' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Fila sintetica comercial invalida.');
    }
}
