<?php

declare(strict_types=1);

namespace App\Core\Sales;

use App\Core\Activities\SyntheticTaskPortfolioInspector;
use App\Core\Leads\SyntheticLeadRoundRobinPreview;
use InvalidArgumentException;

/**
 * F3-153: compositional workload inspection across fictional lead capacity
 * and anonymous task histories. Agent slots are not task assignees.
 * No correlation, tenant facts, permissions, or side effects are inferred.
 */
final class SyntheticCommercialWorkloadInspector
{
    private const FIELDS = [
        'agents', 'lead_states', 'start_cursor',
        'task_histories', 'observed_at_utc',
    ];

    public function inspect(mixed $scenario): array
    {
        if (!is_array($scenario) || count($scenario) !== count(self::FIELDS)) {
            self::invalid();
        }
        $fields = array_keys($scenario);
        $expected = self::FIELDS;
        sort($fields);
        sort($expected);
        if ($fields !== $expected) {
            self::invalid();
        }

        try {
            $leads = (new SyntheticLeadRoundRobinPreview())->preview(
                $scenario['agents'],
                $scenario['lead_states'],
                $scenario['start_cursor'],
            );
            $tasks = (new SyntheticTaskPortfolioInspector())->inspect(
                $scenario['task_histories'],
                $scenario['observed_at_utc'],
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $codes = [];
        if ($leads['unassigned_due_to_capacity'] > 0) {
            $codes[] = 'fictional_lead_capacity_shortfall';
        }
        if ($tasks['overdue_count'] > 0) {
            $codes[] = 'fictional_overdue_tasks';
        }
        if ($tasks['due_now_count'] > 0) {
            $codes[] = 'fictional_tasks_due_now';
        }
        // Even when both sides appear healthy, authorization and the
        // relationship between anonymous agent slots and tasks are unknown.
        $codes[] = 'cross_module_identity_unverified';

        return [
            'fictional_agent_slots' => $leads['agent_count'],
            'fictional_leads_reviewed' => $leads['lead_count'],
            'fictional_assignments_planned' => $leads['planned_assignments'],
            'fictional_leads_waiting_for_capacity' => $leads['unassigned_due_to_capacity'],
            'fictional_tasks_reviewed' => $tasks['task_count'],
            'fictional_open_tasks' => $tasks['open_count'],
            'fictional_overdue_tasks' => $tasks['overdue_count'],
            'fictional_due_now_tasks' => $tasks['due_now_count'],
            'fictional_closed_tasks' => $tasks['closed_count'],
            'workload_warning_codes' => $codes,
            'workload_warning_count' => count($codes),
            'staff_identity_verified' => false,
            'task_identity_verified' => false,
            'tenant_scope_verified' => false,
            'capacity_policy_verified' => false,
            'sla_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_assign' => false,
            'can_create_task' => false,
            'can_update_task' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Carga comercial sintetica invalida.');
    }
}
