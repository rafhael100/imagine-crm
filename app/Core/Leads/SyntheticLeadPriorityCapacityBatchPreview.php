<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-159: deterministic fictional lead triage, with stable score priority
 * and capacity-limited round robin. Input has no identities, PII or tenant.
 *
 * This is NOT a CRM assignment, a staff permission or a deduplication
 * decision. The supplied weights and slots are unverified assertions.
 */
final class SyntheticLeadPriorityCapacityBatchPreview
{
    private const KEYS = ['agents', 'leads', 'weights', 'base_score', 'start_cursor'];
    private const LEAD_STATES = ['unassigned', 'assigned', 'engaged', 'closed'];
    private const MAX_LEADS = 20;

    public function preview(mixed $batch): array
    {
        if (!is_array($batch) || count($batch) !== count(self::KEYS)) {
            self::invalid();
        }
        $keys = array_keys($batch);
        $expected = self::KEYS;
        sort($keys);
        sort($expected);
        if ($keys !== $expected
            || !is_array($batch['leads']) || !array_is_list($batch['leads'])
            || count($batch['leads']) > self::MAX_LEADS) {
            self::invalid();
        }

        $ranked = [];
        $handled = 0;
        try {
            $scoring = new SyntheticLeadRuleScorePreview();
            foreach ($batch['leads'] as $index => $lead) {
                if (!is_array($lead) || count($lead) !== 2) {
                    self::invalid();
                }
                $leadKeys = array_keys($lead);
                sort($leadKeys);
                if ($leadKeys !== ['signals', 'state']
                    || !is_string($lead['state'])
                    || !in_array($lead['state'], self::LEAD_STATES, true)) {
                    self::invalid();
                }

                // Every row, including already-owned/closed rows, must pass
                // the exact signal and weight contract.
                $score = $scoring->preview(
                    $lead['signals'], $batch['weights'], $batch['base_score'],
                );
                if ($lead['state'] !== 'unassigned') {
                    ++$handled;
                    continue;
                }
                $ranked[] = [
                    'position' => $index,
                    'score' => $score['score'],
                    'band' => $score['band'],
                ];
            }

            // DESC score, ASC original position: stable ordering is explicit
            // and reproducible even with equal scores.
            usort($ranked, static fn (array $a, array $b): int =>
                ($b['score'] <=> $a['score']) ?: ($a['position'] <=> $b['position']));

            $allocation = (new SyntheticLeadRoundRobinPreview())->preview(
                $batch['agents'],
                array_fill(0, count($ranked), 'unassigned'),
                $batch['start_cursor'],
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $planned = [];
        $waiting = [];
        foreach ($ranked as $index => $lead) {
            $slot = $allocation['assignment_slots'][$index];
            if ($slot === null) {
                $waiting[] = $lead['position'];
                continue;
            }
            $planned[] = [
                'lead_position' => $lead['position'],
                'agent_slot' => $slot,
            ];
        }

        return [
            'leads_reviewed' => count($batch['leads']),
            'unassigned_reviewed' => count($ranked),
            'already_owned_or_handled' => $handled,
            'ranked_unassigned_positions' => array_column($ranked, 'position'),
            'ranked_score_bands' => array_column($ranked, 'band'),
            'planned_slot_pairs' => $planned,
            'waiting_lead_positions' => $waiting,
            'planned_count' => count($planned),
            'waiting_count' => count($waiting),
            'projected_slot_loads' => $allocation['projected_loads'],
            'next_cursor' => $allocation['next_cursor'],
            'score_policy_verified' => false,
            'agent_identity_verified' => false,
            'tenant_scope_verified' => false,
            'capacity_policy_verified' => false,
            'concurrency_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_assign' => false,
            'can_redistribute' => false,
            'can_update_lead' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Triagem sintetica de leads invalida.');
    }
}
