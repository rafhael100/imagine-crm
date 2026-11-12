<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * F3-156: compare two purely synthetic workload observations.
 * No claim of verified event lineage, staff identity, stable cohort,
 * task completion or an authorized operational action.
 */
final class SyntheticCommercialWorkloadTrendReview
{
    public function inspect(mixed $earlier, mixed $later): array
    {
        try {
            $inspector = new SyntheticCommercialWorkloadInspector();
            $before = $inspector->inspect($earlier);
            $after = $inspector->inspect($later);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Tendencia sintetica comercial invalida.');
        }

        $capacityDelta = $after['fictional_leads_waiting_for_capacity']
            - $before['fictional_leads_waiting_for_capacity'];
        $overdueDelta = $after['fictional_overdue_tasks']
            - $before['fictional_overdue_tasks'];
        $dueNowDelta = $after['fictional_due_now_tasks']
            - $before['fictional_due_now_tasks'];
        $openDelta = $after['fictional_open_tasks']
            - $before['fictional_open_tasks'];

        $worse = $capacityDelta > 0 || $overdueDelta > 0;
        $better = $capacityDelta < 0 || $overdueDelta < 0;
        $trend = $worse && $better ? 'mixed'
            : ($worse ? 'increased_pressure'
                : ($better ? 'reduced_pressure' : 'no_pressure_change'));

        $codes = [];
        if ($capacityDelta > 0) {
            $codes[] = 'capacity_shortfall_increased_claim';
        } elseif ($capacityDelta < 0) {
            $codes[] = 'capacity_shortfall_reduced_claim';
        }
        if ($overdueDelta > 0) {
            $codes[] = 'overdue_backlog_increased_claim';
        } elseif ($overdueDelta < 0) {
            $codes[] = 'overdue_backlog_reduced_claim';
        }
        $codes[] = 'snapshot_cohort_and_identity_unverified';

        return [
            'fictional_capacity_shortfall_delta' => $capacityDelta,
            'fictional_overdue_task_delta' => $overdueDelta,
            'fictional_due_now_task_delta' => $dueNowDelta,
            'fictional_open_task_delta' => $openDelta,
            'pressure_trend' => $trend,
            'warning_codes' => $codes,
            'warning_count' => count($codes),
            'snapshot_cohort_verified' => false,
            'staff_identity_verified' => false,
            'task_identity_verified' => false,
            'tenant_scope_verified' => false,
            'business_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_assign' => false,
            'can_reschedule' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }
}
