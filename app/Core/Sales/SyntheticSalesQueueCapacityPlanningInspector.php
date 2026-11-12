<?php

declare(strict_types=1);

namespace App\Core\Sales;

use App\Core\Leads\SyntheticLeadCapacityScenarioCompare;
use InvalidArgumentException;

/**
 * F3-165: an inert work-queue planning view combining fictional lead capacity
 * what-if projections with a separately validated task backlog.
 *
 * Planning slots are not identities. A "better" what-if never implies that
 * assignments, actions or SLA claims are legally/technically authorized.
 */
final class SyntheticSalesQueueCapacityPlanningInspector
{
    /** @return array<string,mixed> Bounded, anonymous, deny-only diagnostic. */
    public function inspect(mixed $scenario, mixed $extraCapacity): array
    {
        try {
            $queue = (new SyntheticSalesTriageWorkQueueInspector())->inspect($scenario);
            $compare = (new SyntheticLeadCapacityScenarioCompare())
                ->compare($scenario['lead_batch'], $extraCapacity);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $warnings = [];
        if ($compare['scenario_waiting_count'] > 0) {
            $warnings[] = 'fictional_lead_backlog_remains';
        }
        if ($compare['changed_slot_lead_positions'] !== []) {
            $warnings[] = 'fictional_assignment_slot_reorder';
        }
        if ($queue['fictional_overdue_tasks'] > 0) {
            $warnings[] = 'fictional_overdue_task_backlog_remains';
        }
        if ($queue['fictional_due_now_tasks'] > 0) {
            $warnings[] = 'fictional_task_due_now';
        }
        // Claiming spare capacity does not validate a real policy, identity
        // or binding between any lead and any task.
        $warnings[] = 'cross_module_scope_and_identity_unverified';

        return [
            'fictional_leads_reviewed' => $queue['fictional_leads_reviewed'],
            'fictional_tasks_reviewed' => $queue['fictional_tasks_reviewed'],
            'fictional_extra_capacity_claimed' => $compare['fictional_extra_capacity_claimed'],
            'baseline_leads_planned' => $compare['baseline_planned_count'],
            'scenario_leads_planned' => $compare['scenario_planned_count'],
            'scenario_leads_waiting' => $compare['scenario_waiting_count'],
            'newly_planned_lead_positions' => $compare['newly_planned_lead_positions'],
            'changed_slot_lead_positions' => $compare['changed_slot_lead_positions'],
            'remaining_waiting_lead_positions' => $compare['remaining_waiting_lead_positions'],
            'fictional_open_tasks' => $queue['fictional_open_tasks'],
            'fictional_overdue_tasks' => $queue['fictional_overdue_tasks'],
            'fictional_due_now_tasks' => $queue['fictional_due_now_tasks'],
            'task_priority_positions' => $queue['task_priority_positions'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'tenant_scope_verified' => false,
            'staff_identity_verified' => false,
            'task_identity_verified' => false,
            'scoring_policy_verified' => false,
            'capacity_policy_verified' => false,
            'concurrency_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_read_customers' => false,
            'can_assign' => false,
            'can_reassign' => false,
            'can_reschedule_task' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Planejamento sintetico de fila comercial invalido.');
    }
}
