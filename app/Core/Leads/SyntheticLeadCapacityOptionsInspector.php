<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-166: bounded comparison of up to five caller-invented capacity options.
 * The lowest-extra-capacity option is chosen ONLY from supplied candidates;
 * no staffing need, entitlement, budget, optimization proof or action is inferred.
 */
final class SyntheticLeadCapacityOptionsInspector
{
    private const MAX_OPTIONS = 5;

    public function inspect(mixed $batch, mixed $options): array
    {
        if (!is_array($options) || !array_is_list($options)
            || count($options) < 1 || count($options) > self::MAX_OPTIONS) {
            self::invalid();
        }

        $rows = [];
        $lowest = null;
        $fewestSlots = null;
        $hasReroute = false;
        try {
            $comparator = new SyntheticLeadCapacityScenarioCompare();

            foreach ($options as $position => $extraSlots) {
                $result = $comparator->compare($batch, $extraSlots);
                $clears = $result['scenario_waiting_count'] === 0;
                $extra = $result['fictional_extra_capacity_claimed'];
                $reroutes = count($result['changed_slot_lead_positions']);

                $rows[] = [
                    'option_position' => $position,
                    'fictional_extra_slots' => $extra,
                    'fictional_planned_count' => $result['scenario_planned_count'],
                    'fictional_waiting_count' => $result['scenario_waiting_count'],
                    'fictional_changed_slot_count' => $reroutes,
                    'fictional_clears_backlog' => $clears,
                ];

                $hasReroute = $hasReroute || $reroutes > 0;
                if ($clears && ($fewestSlots === null || $extra < $fewestSlots)) {
                    $lowest = $position;
                    $fewestSlots = $extra;
                }
            }
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $warnings = [];
        if ($lowest === null) {
            $warnings[] = 'no_supplied_option_clears_fictional_backlog';
        }
        if ($hasReroute) {
            $warnings[] = 'fictional_existing_slot_reorder';
        }
        $warnings[] = 'candidate_set_not_global_optimization';
        $warnings[] = 'capacity_staff_scope_unverified';

        return [
            'candidate_count' => count($rows),
            'fictional_option_summaries' => $rows,
            'lowest_extra_slots_clearing_candidate_position' => $lowest,
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'candidate_set_completeness_verified' => false,
            'staff_identity_verified' => false,
            'capacity_policy_verified' => false,
            'tenant_scope_verified' => false,
            'concurrency_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_assign' => false,
            'can_reassign' => false,
            'can_hire' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Comparacao sintetica de cenarios invalida.');
    }
}
