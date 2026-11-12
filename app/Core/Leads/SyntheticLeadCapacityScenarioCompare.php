<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-164: compare fictional lead triage with optional additional agent slots.
 * Existing planned slots may move during a fresh round-robin projection:
 * a simulation is never a migration or authorization to reassign a lead.
 */
final class SyntheticLeadCapacityScenarioCompare
{
    /** @return array<string,mixed> Anonymous, bounded scenario comparison. */
    public function compare(mixed $batch, mixed $extraSlots): array
    {
        try {
            $preview = new SyntheticLeadPriorityCapacityBatchPreview();
            $before = $preview->preview($batch);
            $agents = $batch['agents'];

            if (!is_array($extraSlots) || !array_is_list($extraSlots)
                || count($extraSlots) !== count($agents)) {
                self::invalid();
            }

            $scenario = $batch;
            $totalAdditional = 0;
            foreach ($extraSlots as $i => $extra) {
                if (!is_int($extra) || $extra < 0 || $extra > 20
                    || $agents[$i]['capacity'] + $extra > 20) {
                    self::invalid();
                }
                $totalAdditional += $extra;
                $scenario['agents'][$i]['capacity'] = $agents[$i]['capacity'] + $extra;
            }

            $after = $preview->preview($scenario);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $beforeSlots = [];
        foreach ($before['planned_slot_pairs'] as $pair) {
            $beforeSlots[$pair['lead_position']] = $pair['agent_slot'];
        }

        $newlyPlanned = [];
        $changedSlot = [];
        foreach ($after['planned_slot_pairs'] as $pair) {
            $position = $pair['lead_position'];
            if (!array_key_exists($position, $beforeSlots)) {
                $newlyPlanned[] = $position;
            } elseif ($beforeSlots[$position] !== $pair['agent_slot']) {
                $changedSlot[] = $position;
            }
        }

        $warnings = [];
        if ($after['waiting_count'] > 0) {
            $warnings[] = 'fictional_backlog_remains';
        }
        if ($changedSlot !== []) {
            $warnings[] = 'fictional_slot_changes_between_scenarios';
        }
        $warnings[] = 'capacity_and_staff_identity_unverified';

        return [
            'fictional_leads_reviewed' => $before['leads_reviewed'],
            'fictional_unassigned_leads' => $before['unassigned_reviewed'],
            'fictional_extra_capacity_claimed' => $totalAdditional,
            'baseline_planned_count' => $before['planned_count'],
            'scenario_planned_count' => $after['planned_count'],
            'baseline_waiting_count' => $before['waiting_count'],
            'scenario_waiting_count' => $after['waiting_count'],
            'newly_planned_lead_positions' => $newlyPlanned,
            'changed_slot_lead_positions' => $changedSlot,
            'remaining_waiting_lead_positions' => $after['waiting_lead_positions'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'staff_identity_verified' => false,
            'capacity_policy_verified' => false,
            'tenant_scope_verified' => false,
            'concurrency_verified' => false,
            'source_history_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_assign' => false,
            'can_reassign' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Cenario sintetico de capacidade invalido.');
    }
}
