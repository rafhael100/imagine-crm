<?php

declare(strict_types=1);

namespace App\Core\Sales;

use App\Core\Activities\SyntheticTaskLifecycleInspector;
use App\Core\Leads\SyntheticLeadRoundRobinPreview;
use App\Core\Leads\SyntheticLeadRuleScorePreview;
use InvalidArgumentException;

/**
 * VEN F3-137: an end-to-end INERT commercial journey combining five
 * independently validated, synthetic-only PHP Core modules.
 *
 * This is a diagnostic composition of invented facts, not CRM runtime,
 * identity, RLS, billing, authenticated acceptance or work execution.
 */
final class SyntheticCommercialJourneyInspector
{
    private const INPUT_KEYS = [
        'agents',
        'approval_claimed',
        'base_score',
        'discount_limit_bp',
        'discount_lines',
        'observed_at_utc',
        'opportunity_events',
        'proposal_events',
        'scoring_signals',
        'scoring_weights',
        'task_events',
    ];

    /**
     * @param mixed $scenario Closed map with exactly INPUT_KEYS (no identifiers)
     * @return array<string,mixed> Anonymous, deny-only journey projection
     */
    public function inspect(mixed $scenario): array
    {
        if (!is_array($scenario)
            || count($scenario) !== count(self::INPUT_KEYS)) {
            self::invalid();
        }
        $keys = array_keys($scenario);
        $expected = self::INPUT_KEYS;
        sort($keys);
        sort($expected);
        if ($keys !== $expected) {
            self::invalid();
        }

        try {
            // Evaluate all five validators independently before describing
            // any cross-module warnings. A hidden invalid section is fatal.
            $scoring = (new SyntheticLeadRuleScorePreview())->preview(
                $scenario['scoring_signals'],
                $scenario['scoring_weights'],
                $scenario['base_score'],
            );
            $assignment = (new SyntheticLeadRoundRobinPreview())->preview(
                $scenario['agents'],
                ['unassigned'],
            );
            $opportunity = (new SyntheticOpportunityTransitionLedger())
                ->inspect($scenario['opportunity_events']);
            $proposal = (new SyntheticProposalSendPreflight())->inspect(
                $scenario['proposal_events'],
                $scenario['discount_lines'],
                $scenario['discount_limit_bp'],
                $scenario['approval_claimed'],
            );
            $task = (new SyntheticTaskLifecycleInspector())->inspect(
                $scenario['task_events'],
                $scenario['observed_at_utc'],
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $warnings = [];
        $slot = $assignment['assignment_slots'][0];
        if ($slot === null) {
            $warnings[] = 'no_fictional_agent_capacity';
        }
        if ($opportunity['latest_claimed_status'] === 'won'
            && $proposal['latest_claimed_status'] !== 'accepted') {
            $warnings[] = 'won_claim_without_latest_proposal_acceptance_claim';
        }
        if ($proposal['requires_manager_review']) {
            $warnings[] = 'fictional_discount_requires_review';
        }
        if ($task['due_phase'] === 'overdue') {
            $warnings[] = 'fictional_task_overdue';
        }

        return [
            'scoring_band' => $scoring['band'],
            'scoring_score' => $scoring['score'],
            'lead_assignment_slot' => $slot,
            'opportunity_claimed_status' => $opportunity['latest_claimed_status'],
            'opportunity_claimed_win_count' => $opportunity['claimed_wins'],
            'proposal_claimed_status' => $proposal['latest_claimed_status'],
            'proposal_discounted_total_cents' => $proposal['discounted_total_cents'],
            'proposal_preflight_blockers' => $proposal['blocker_codes'],
            'task_claimed_status' => $task['latest_claimed_status'],
            'task_due_phase' => $task['due_phase'],
            'journey_warning_codes' => $warnings,
            'fictional_consistency_warnings' => count($warnings),
            'recognized_wins' => 0,
            'recognized_revenue_cents' => 0,
            'input_sources_verified' => false,
            'tenant_scope_verified' => false,
            'business_policy_verified' => false,
            'recipient_access_verified' => false,
            'workflow_identity_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_create_lead' => false,
            'can_assign' => false,
            'can_move_opportunity' => false,
            'can_send_proposal' => false,
            'can_mark_won' => false,
            'can_create_task' => false,
            'can_notify' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Jornada sintetica comercial invalida.');
    }
}
