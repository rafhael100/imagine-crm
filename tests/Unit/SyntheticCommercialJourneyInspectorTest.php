<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticCommercialJourneyInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCommercialJourneyInspectorTest extends TestCase
{
    /** @return array<string,mixed> */
    private static function scenario(): array
    {
        return [
            'agents' => [
                [
                    'enabled' => true, 'has_required_skill' => true,
                    'open_leads' => 0, 'capacity' => 2,
                ],
            ],
            'approval_claimed' => false,
            'base_score' => 20,
            'discount_limit_bp' => 1000,
            'discount_lines' => [
                ['quantity' => 2, 'unit_price_cents' => 10000, 'discount_bp' => 500],
            ],
            'observed_at_utc' => '2026-11-05T10:00:00Z',
            'opportunity_events' => [
                [
                    'revision' => 1, 'action' => 'create', 'stage' => 0,
                    'confirmed' => false, 'loss_reason_code' => null,
                    'reopen_reason_code' => null,
                    'occurred_at_utc' => '2026-11-01T10:00:00Z',
                ],
                [
                    'revision' => 2, 'action' => 'move', 'stage' => 1,
                    'confirmed' => false, 'loss_reason_code' => null,
                    'reopen_reason_code' => null,
                    'occurred_at_utc' => '2026-11-01T10:01:00Z',
                ],
                [
                    'revision' => 3, 'action' => 'win', 'stage' => 1,
                    'confirmed' => true, 'loss_reason_code' => null,
                    'reopen_reason_code' => null,
                    'occurred_at_utc' => '2026-11-01T10:02:00Z',
                ],
            ],
            'proposal_events' => [
                [
                    'version' => 1, 'action' => 'create',
                    'occurred_at_utc' => '2026-11-01T10:00:00Z',
                    'expires_at_utc' => null,
                ],
                [
                    'version' => 1, 'action' => 'send',
                    'occurred_at_utc' => '2026-11-01T10:10:00Z',
                    'expires_at_utc' => '2026-11-08T10:10:00Z',
                ],
                [
                    'version' => 1, 'action' => 'accept',
                    'occurred_at_utc' => '2026-11-02T11:00:00Z',
                    'expires_at_utc' => null,
                ],
            ],
            'scoring_signals' => [
                'profile_complete' => true,
                'recent_engagement' => true,
                'requested_quote' => true,
                'scheduled_demo' => false,
                'stale_response' => false,
            ],
            'scoring_weights' => [
                'profile_complete' => 10,
                'recent_engagement' => 20,
                'requested_quote' => 40,
                'scheduled_demo' => 10,
                'stale_response' => -20,
            ],
            'task_events' => [
                [
                    'revision' => 1, 'action' => 'create',
                    'occurred_at_utc' => '2026-11-01T09:00:00Z',
                    'due_at_utc' => '2026-11-03T09:00:00Z',
                    'priority' => 'high', 'reason_code' => null,
                ],
                [
                    'revision' => 2, 'action' => 'start',
                    'occurred_at_utc' => '2026-11-01T09:01:00Z',
                    'due_at_utc' => null, 'priority' => null,
                    'reason_code' => null,
                ],
                [
                    'revision' => 3, 'action' => 'complete',
                    'occurred_at_utc' => '2026-11-02T09:00:00Z',
                    'due_at_utc' => null, 'priority' => null,
                    'reason_code' => null,
                ],
            ],
        ];
    }

    public function test_entire_fictional_journey_joins_five_existing_cores_without_granting_real_powers(): void
    {
        $scenario = self::scenario();
        $initial = $scenario;
        $inspector = new SyntheticCommercialJourneyInspector();
        $result = $inspector->inspect($scenario);

        self::assertSame('quente', $result['scoring_band']);
        self::assertSame(90, $result['scoring_score']);
        self::assertSame(0, $result['lead_assignment_slot']);
        self::assertSame('won', $result['opportunity_claimed_status']);
        self::assertSame(1, $result['opportunity_claimed_win_count']);
        self::assertSame('accepted', $result['proposal_claimed_status']);
        self::assertSame(19000, $result['proposal_discounted_total_cents']);
        self::assertContains('current_draft_required', $result['proposal_preflight_blockers']);
        self::assertContains('sender_identity_unverified', $result['proposal_preflight_blockers']);
        self::assertSame('completed', $result['task_claimed_status']);
        self::assertSame('closed', $result['task_due_phase']);
        self::assertSame([], $result['journey_warning_codes']);
        self::assertSame(0, $result['fictional_consistency_warnings']);
        self::assertSame(0, $result['recognized_wins']);
        self::assertSame(0, $result['recognized_revenue_cents']);
        self::assertTrue($result['informational_only']);
        foreach ([
            'input_sources_verified', 'tenant_scope_verified',
            'business_policy_verified', 'recipient_access_verified',
            'workflow_identity_verified', 'authorized', 'can_create_lead',
            'can_assign', 'can_move_opportunity', 'can_send_proposal',
            'can_mark_won', 'can_create_task', 'can_notify',
            'can_charge', 'can_persist',
        ] as $flag) {
            self::assertFalse($result[$flag], $flag);
        }

        self::assertSame($initial, $scenario);
        self::assertSame($result, $inspector->inspect($scenario));
    }

    public function test_inconsistent_claims_produce_diagnostic_warnings_without_side_effects(): void
    {
        $scenario = self::scenario();
        $scenario['agents'][0]['enabled'] = false;
        $scenario['proposal_events'] = [$scenario['proposal_events'][0]];
        $scenario['discount_lines'][0]['discount_bp'] = 4000;
        $scenario['task_events'] = [$scenario['task_events'][0]];
        $result = (new SyntheticCommercialJourneyInspector())->inspect($scenario);

        self::assertNull($result['lead_assignment_slot']);
        self::assertSame('won', $result['opportunity_claimed_status']);
        self::assertSame('draft', $result['proposal_claimed_status']);
        self::assertSame(12000, $result['proposal_discounted_total_cents']);
        self::assertContains('manager_review_required', $result['proposal_preflight_blockers']);
        self::assertSame('pending', $result['task_claimed_status']);
        self::assertSame('overdue', $result['task_due_phase']);
        self::assertSame([
            'no_fictional_agent_capacity',
            'won_claim_without_latest_proposal_acceptance_claim',
            'fictional_discount_requires_review',
            'fictional_task_overdue',
        ], $result['journey_warning_codes']);
        self::assertSame(4, $result['fictional_consistency_warnings']);
        self::assertSame(0, $result['recognized_wins']);
        self::assertFalse($result['can_send_proposal']);
        self::assertFalse($result['can_charge']);
        self::assertFalse($result['can_notify']);
    }

    public function test_invalid_hidden_section_is_rejected_even_when_another_section_blocks(): void
    {
        $baseline = self::scenario();
        $bad = [
            null, 'scenario', [],
            array_merge($baseline, ['tenant' => 'private@example.test']),
            array_diff_key($baseline, ['agents' => true]),
            array_merge($baseline, ['base_score' => 101]),
            array_merge($baseline, ['scoring_signals' => ['email' => 'private@example.test']]),
            array_merge($baseline, ['agents' => [['enabled' => false, 'email' => 'private@example.test']]]),
            array_merge($baseline, ['opportunity_events' => [
                ['revision' => 1, 'action' => 'win', 'stage' => 0],
            ]]),
            array_merge($baseline, ['proposal_events' => [[
                'version' => 1, 'action' => 'accept',
                'occurred_at_utc' => '2026-11-01T12:00:00Z',
                'expires_at_utc' => null,
            ]]]),
            array_merge($baseline, ['discount_lines' => [
                ['quantity' => 1, 'unit_price_cents' => 100, 'discount_bp' => '500'],
            ]]),
            array_merge($baseline, ['approval_claimed' => 'true']),
            array_merge($baseline, ['observed_at_utc' => '2026-02-30T00:00:00Z']),
            array_merge($baseline, ['task_events' => [[
                'revision' => 1, 'action' => 'complete',
                'occurred_at_utc' => '2026-11-01T09:00:00Z',
                'due_at_utc' => null,
                'priority' => null,
                'reason_code' => null,
            ]]]),
        ];
        foreach ($bad as $scenario) {
            try {
                (new SyntheticCommercialJourneyInspector())->inspect($scenario);
                self::fail('Unsafe or malformed synthetic scenario was accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Jornada sintetica comercial invalida.', $e->getMessage());
                self::assertStringNotContainsString('private@example.test', $e->getMessage());
            }
        }
    }

    public function test_output_is_closed_and_never_leaks_events_identifiers_or_credentials(): void
    {
        $report = (new SyntheticCommercialJourneyInspector())->inspect(self::scenario());
        self::assertSame([
            'scoring_band', 'scoring_score', 'lead_assignment_slot',
            'opportunity_claimed_status', 'opportunity_claimed_win_count',
            'proposal_claimed_status', 'proposal_discounted_total_cents',
            'proposal_preflight_blockers', 'task_claimed_status',
            'task_due_phase', 'journey_warning_codes',
            'fictional_consistency_warnings', 'recognized_wins',
            'recognized_revenue_cents', 'input_sources_verified',
            'tenant_scope_verified', 'business_policy_verified',
            'recipient_access_verified', 'workflow_identity_verified',
            'informational_only', 'authorized', 'can_create_lead',
            'can_assign', 'can_move_opportunity', 'can_send_proposal',
            'can_mark_won', 'can_create_task', 'can_notify', 'can_charge',
            'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach ([
            'tenant', 'user_id', 'lead_id', 'opportunity_id', 'proposal_id',
            'task_id', 'email', 'phone', 'customer', 'event_reference',
            'note', 'history', 'token', 'date', 'occurred_at_utc',
        ] as $key) {
            self::assertStringNotContainsString('"'.$key.'"', $json);
        }
        self::assertFalse($report['authorized']);
        self::assertFalse($report['can_persist']);
    }
}
