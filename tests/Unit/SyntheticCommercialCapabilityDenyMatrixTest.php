<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpQueue;
use App\Core\Activities\SyntheticRecurringTaskSchedule;
use App\Core\Activities\SyntheticTaskLifecycleInspector;
use App\Core\Activities\SyntheticTaskPortfolioInspector;
use App\Core\Leads\SyntheticLeadRoundRobinPreview;
use App\Core\Leads\SyntheticLeadRuleScorePreview;
use App\Core\Leads\SyntheticScoreTrendPreview;
use App\Core\Plans\SyntheticCancellationRetentionTimeline;
use App\Core\Plans\SyntheticDelinquencyReviewTimeline;
use App\Core\Plans\SyntheticTrialRetentionTimeline;
use App\Core\Plans\TrialWindowAdvisory;
use App\Core\Sales\SyntheticCommercialDiscountReview;
use App\Core\Sales\SyntheticCommercialJourneyInspector;
use App\Core\Sales\SyntheticCommercialWorkloadInspector;
use App\Core\Sales\SyntheticSalesAggregatePrivacyPreview;
use App\Core\Sales\SyntheticStageAggregateCsvPreview;
use App\Core\Sales\SyntheticReportDifferencingRiskInspector;
use App\Core\Sales\SyntheticReportHistoryPairwiseRiskInspector;
use App\Core\Sales\SyntheticPrivateStageDistributionPreview;
use App\Core\Sales\SyntheticStageDashboardSnapshotReview;
use App\Core\Sales\SyntheticReportDeliveryCompositeInspector;
use App\Core\Sales\SyntheticLineItemSubtotalPreview;
use App\Core\Sales\SyntheticOpportunityOutcomeInspector;
use App\Core\Sales\SyntheticOpportunityTransitionLedger;
use App\Core\Sales\SyntheticProposalLifecycleInspector;
use App\Core\Sales\SyntheticProposalSendPreflight;
use App\Core\Sales\SyntheticProposalValidityPreview;
use App\Core\Sales\SyntheticProposalVersionReview;
use App\Core\Sales\SyntheticSalesEventUniquenessInspector;
use App\Core\Sales\SyntheticSalesGoalProgressPreview;
use App\Core\Sales\SyntheticScheduledReportDeliveryPreflight;
use App\Core\Sales\SyntheticStageFieldRequirementsInspector;
use App\Core\Sales\SyntheticWeightedSalesForecast;
use PHPUnit\Framework\TestCase;

/**
 * Integrated fail-closed matrix of independently reviewed synthetic slices.
 *
 * A "won"/"accepted"/"100%"/"within validity"/"unique event" claim cannot
 * accidentally make a real business side effect eligible when composed.
 */
final class SyntheticCommercialCapabilityDenyMatrixTest extends TestCase
{
    private const EVENT = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    /** @return array<string,array<string,mixed>> */
    private static function reports(): array
    {
        return [
            'line_subtotal' => (new SyntheticLineItemSubtotalPreview())->preview([
                ['quantity' => 1, 'unit_price_cents' => 12345],
            ]),
            'proposal_accepted_claim' => (new SyntheticProposalVersionReview())->inspect([
                ['version' => 1, 'status' => 'accepted', 'viewed' => true],
            ]),
            'forecast_full_probability' => (new SyntheticWeightedSalesForecast())->preview([
                ['amount_cents' => 15000, 'win_probability_bp' => 10000],
            ]),
            'opportunity_won_claim' => (new SyntheticOpportunityOutcomeInspector())->inspect([
                ['outcome' => 'won', 'loss_reason_code' => null],
            ]),
            'sales_event_unique_claim' => (new SyntheticSalesEventUniquenessInspector())->inspect([
                ['event_reference' => self::EVENT, 'amount_cents' => 15000],
            ]),
            'proposal_within_validity' => (new SyntheticProposalValidityPreview(
                '2026-11-01T12:00:00Z',
                '2026-11-08T12:00:00Z',
                '2026-11-03T12:00:00Z',
            ))->readout(),
            'lead_hot_score' => (new SyntheticScoreTrendPreview())->preview([
                ['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => 100],
            ]),
            'proposal_lifecycle_accepted' => (new SyntheticProposalLifecycleInspector())->inspect([
                [
                    'version' => 1, 'action' => 'create',
                    'occurred_at_utc' => '2026-11-01T10:00:00Z',
                    'expires_at_utc' => null,
                ],
                [
                    'version' => 1, 'action' => 'send',
                    'occurred_at_utc' => '2026-11-01T11:00:00Z',
                    'expires_at_utc' => '2026-11-08T11:00:00Z',
                ],
                [
                    'version' => 1, 'action' => 'accept',
                    'occurred_at_utc' => '2026-11-01T12:00:00Z',
                    'expires_at_utc' => null,
                ],
            ]),
            'opportunity_transition_won' => (new SyntheticOpportunityTransitionLedger())->inspect([
                [
                    'revision' => 1, 'action' => 'create', 'stage' => 0,
                    'confirmed' => false, 'loss_reason_code' => null,
                    'reopen_reason_code' => null,
                    'occurred_at_utc' => '2026-11-01T10:00:00Z',
                ],
                [
                    'revision' => 2, 'action' => 'win', 'stage' => 0,
                    'confirmed' => true, 'loss_reason_code' => null,
                    'reopen_reason_code' => null,
                    'occurred_at_utc' => '2026-11-01T11:00:00Z',
                ],
            ]),
            'stage_requirements_satisfied' => (new SyntheticStageFieldRequirementsInspector())->inspect(
                0,
                2,
                [
                    'amount' => true, 'assignee' => true, 'contact' => true,
                    'expected_close' => true, 'source' => true,
                ],
                [[], [], ['contact', 'assignee', 'amount'], []],
            ),
            'task_recurrence_upcoming' => (new SyntheticRecurringTaskSchedule())->preview(
                '2026-11-01T12:00:00Z', 'daily', 2, '2026-10-31T12:00:00Z',
            ),
            'discount_manager_claimed_approval' => (new SyntheticCommercialDiscountReview())->inspect([
                ['quantity' => 1, 'unit_price_cents' => 10000, 'discount_bp' => 4000],
            ], 1000, true),
            'proposal_send_preflight_blocked' => (new SyntheticProposalSendPreflight())->inspect(
                [[
                    'version' => 1, 'action' => 'create',
                    'occurred_at_utc' => '2026-11-01T10:00:00Z',
                    'expires_at_utc' => null,
                ]],
                [[
                    'quantity' => 1, 'unit_price_cents' => 10000, 'discount_bp' => 4000,
                ]],
                1000,
                true,
            ),
            'lead_distribution_planned' => (new SyntheticLeadRoundRobinPreview())->preview(
                [
                    [
                        'enabled' => true, 'has_required_skill' => true,
                        'open_leads' => 0, 'capacity' => 3,
                    ],
                ],
                ['unassigned', 'engaged'],
            ),
            'scoring_rule_hot' => (new SyntheticLeadRuleScorePreview())->preview(
                [
                    'profile_complete' => true, 'recent_engagement' => false,
                    'requested_quote' => false, 'scheduled_demo' => false,
                    'stale_response' => false,
                ],
                [
                    'profile_complete' => 20, 'recent_engagement' => 0,
                    'requested_quote' => 0, 'scheduled_demo' => 0,
                    'stale_response' => -20,
                ],
                80,
            ),
            'goal_claimed_complete' => (new SyntheticSalesGoalProgressPreview())->preview(
                [['event_reference' => self::EVENT, 'amount_cents' => 10000]],
                10000,
            ),
            'task_completed_claim' => (new SyntheticTaskLifecycleInspector())->inspect(
                [
                    [
                        'revision' => 1, 'action' => 'create',
                        'occurred_at_utc' => '2026-11-01T10:00:00Z',
                        'due_at_utc' => '2026-11-02T10:00:00Z',
                        'priority' => 'normal', 'reason_code' => null,
                    ],
                    [
                        'revision' => 2, 'action' => 'start',
                        'occurred_at_utc' => '2026-11-01T11:00:00Z',
                        'due_at_utc' => null, 'priority' => null,
                        'reason_code' => null,
                    ],
                    [
                        'revision' => 3, 'action' => 'complete',
                        'occurred_at_utc' => '2026-11-01T12:00:00Z',
                        'due_at_utc' => null, 'priority' => null,
                        'reason_code' => null,
                    ],
                ],
                '2026-11-04T12:00:00Z',
            ),
            'report_small_bucket_suppressed' => (new SyntheticSalesAggregatePrivacyPreview())
                ->summarize([['stage' => 'won', 'claimed_value_cents' => 60000]], 3),
            'report_delivery_access_revoked' => (new SyntheticScheduledReportDeliveryPreflight())
                ->inspect(
                    [
                        'at_utc' => '2026-11-01T10:00:00Z',
                        'recipient_access' => true,
                        'policy_revision' => 3,
                        'requested_fields' => ['stage', 'score'],
                    ],
                    [
                        'at_utc' => '2026-11-02T10:00:00Z',
                        'recipient_access' => false,
                        'policy_revision' => 3,
                        'report_state' => 'ready',
                    ],
                ),
            'full_sales_journey_claimed_won' => self::journeyFromOfflineFixture(),
            'stage_csv_preview_unexportable' => (new SyntheticStageAggregateCsvPreview())->preview([
                ['stage' => 'new', 'claimed_value_cents' => 100],
                ['stage' => 'new', 'claimed_value_cents' => 200],
                ['stage' => 'new', 'claimed_value_cents' => 300],
                ['stage' => 'won', 'claimed_value_cents' => 987654],
            ], 3),
            'report_hand_off_bytes_withheld' => (new SyntheticReportDeliveryCompositeInspector())->inspect(
                [
                    ['stage' => 'new', 'claimed_value_cents' => 100],
                    ['stage' => 'new', 'claimed_value_cents' => 200],
                    ['stage' => 'new', 'claimed_value_cents' => 300],
                    ['stage' => 'won', 'claimed_value_cents' => 987654],
                ],
                3,
                [
                    'at_utc' => '2026-11-01T10:00:00Z',
                    'recipient_access' => true,
                    'policy_revision' => 1,
                    'requested_fields' => ['stage', 'claimed_value_cents'],
                ],
                [
                    'at_utc' => '2026-11-02T10:00:00Z',
                    'recipient_access' => true,
                    'policy_revision' => 1,
                    'report_state' => 'ready',
                ],
            ),
            'report_differencing_singleton_warning' => (new SyntheticReportDifferencingRiskInspector())->inspect(
                ['new' => 3, 'qualified' => 0, 'proposal' => 0, 'won' => 2, 'lost' => 0],
                ['new' => 3, 'qualified' => 0, 'proposal' => 0, 'won' => 3, 'lost' => 0],
                3,
            ),
            'private_stage_distribution_withheld' => (new SyntheticPrivateStageDistributionPreview())->preview([
                ['stage' => 'new', 'claimed_value_cents' => 100],
                ['stage' => 'new', 'claimed_value_cents' => 200],
                ['stage' => 'new', 'claimed_value_cents' => 300],
                ['stage' => 'won', 'claimed_value_cents' => 987654],
            ], 3),
            'cross_snapshot_dashboard_withheld' => (new SyntheticStageDashboardSnapshotReview())->inspect(
                [
                    ['stage' => 'new', 'claimed_value_cents' => 100],
                    ['stage' => 'new', 'claimed_value_cents' => 200],
                    ['stage' => 'new', 'claimed_value_cents' => 300],
                ],
                [
                    ['stage' => 'new', 'claimed_value_cents' => 100],
                    ['stage' => 'new', 'claimed_value_cents' => 200],
                    ['stage' => 'new', 'claimed_value_cents' => 300],
                    ['stage' => 'won', 'claimed_value_cents' => 987654],
                ],
                3,
            ),
            'historical_report_pairwise_differencing_denied' =>
                (new SyntheticReportHistoryPairwiseRiskInspector())->inspect([
                    ['new' => 3, 'qualified' => 0, 'proposal' => 0, 'won' => 0, 'lost' => 0],
                    ['new' => 8, 'qualified' => 0, 'proposal' => 0, 'won' => 0, 'lost' => 0],
                    ['new' => 4, 'qualified' => 0, 'proposal' => 0, 'won' => 0, 'lost' => 0],
                ], 3),
            'task_portfolio_overdue_inert' => (new SyntheticTaskPortfolioInspector())->inspect([
                [[
                'revision' => 1,
                'action' => 'create',
                'occurred_at_utc' => '2026-11-01T10:00:00Z',
                'due_at_utc' => '2026-11-02T10:00:00Z',
                'priority' => 'urgent',
                'reason_code' => null,
            ]],
            ], '2026-11-10T12:00:00Z'),
            'commercial_capacity_and_overdue_inert' => (new SyntheticCommercialWorkloadInspector())->inspect([
                'agents' => [[
                    'enabled' => true,
                    'has_required_skill' => true,
                    'open_leads' => 0,
                    'capacity' => 0,
                ]],
                'lead_states' => ['unassigned'],
                'start_cursor' => 0,
                'task_histories' => [[[
                'revision' => 1,
                'action' => 'create',
                'occurred_at_utc' => '2026-11-01T10:00:00Z',
                'due_at_utc' => '2026-11-02T10:00:00Z',
                'priority' => 'urgent',
                'reason_code' => null,
            ]]],
                'observed_at_utc' => '2026-11-10T12:00:00Z',
            ]),
            'trial_review_window' => (new SyntheticTrialRetentionTimeline(
                new TrialWindowAdvisory(2000000, 2000000 + 14 * 86400),
            ))->readout(),
            'cancellation_review_window' => (new SyntheticCancellationRetentionTimeline(
                2000000, 2000000,
            ))->readout(),
            'delinquency_day_thirty' => (new SyntheticDelinquencyReviewTimeline(
                2000000, 2000000 + 30 * 86400,
            ))->readout(),
        ];
    }

    /**
     * Read the very fixture shipped in the offline browser prototype, so the
     * transversal no-authorization matrix also checks the composed journey.
     *
     * @return array<string,mixed>
     */
    private static function journeyFromOfflineFixture(): array
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-138-commercial-journey/index.html',
        );
        if (!is_string($html)
            || preg_match(
                '/const SCENARIOS=Object\.freeze\((.*?)\);\s*const LABELS=/s',
                $html,
                $matches,
            ) !== 1) {
            throw new \RuntimeException('Offline journey fixture unavailable.');
        }
        $cases = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($cases) || count($cases) !== 2
            || ($cases[0]['id'] ?? null) !== 'consistent'
            || !isset($cases[0]['input'])
            || !is_array($cases[0]['input'])) {
            throw new \RuntimeException('Offline journey fixture contract changed.');
        }

        return (new SyntheticCommercialJourneyInspector())
            ->inspect($cases[0]['input']);
    }

    public function test_all_thirty_one_business_laboratories_have_no_operational_capability_even_on_positive_claims(): void
    {
        $reports = self::reports();
        self::assertCount(31, $reports);
        foreach ($reports as $name => $report) {
            self::assertTrue($report['informational_only'], $name.' must be informative only');
            foreach ($report as $key => $value) {
                if ($key === 'authorized'
                    || $key === 'link_authorized'
                    || str_starts_with($key, 'can_')
                    || str_ends_with($key, '_verified')) {
                    self::assertFalse($value, $name.'.'.$key.' must remain false');
                }
                if (str_starts_with($key, 'recognized_')
                    || $key === 'verified_event_count') {
                    self::assertSame(0, $value, $name.'.'.$key.' must never be recognized revenue');
                }
            }
            $json = json_encode($report, JSON_THROW_ON_ERROR);
            self::assertStringNotContainsString(self::EVENT, $json, $name.' must not disclose event reference');
            foreach ([
                'tenant', 'email', 'phone', 'customer', 'proposal_id',
                'lead_id', 'event_reference', 'opportunity_id', 'actor', 'payment',
            ] as $forbidden) {
                self::assertStringNotContainsString('"'.$forbidden.'"', $json, $name.' must not disclose private field');
            }
        }
    }

    public function test_claimed_success_across_multiple_modules_cannot_count_verified_sales_or_money(): void
    {
        $reports = self::reports();
        self::assertSame(15000, $reports['forecast_full_probability']['weighted_total_cents']);
        self::assertFalse($reports['forecast_full_probability']['forecast_verified']);
        self::assertSame('accepted', $reports['proposal_accepted_claim']['latest_claimed_status']);
        self::assertFalse($reports['proposal_accepted_claim']['authority_verified']);
        self::assertSame(1, $reports['opportunity_won_claim']['claimed_outcome_counts']['won']);
        self::assertSame(0, $reports['opportunity_won_claim']['recognized_wins']);
        self::assertSame(15000, $reports['sales_event_unique_claim']['claimed_sum_cents']);
        self::assertSame(0, $reports['sales_event_unique_claim']['recognized_revenue_cents']);
        self::assertSame('within_validity', $reports['proposal_within_validity']['phase']);
        self::assertFalse($reports['proposal_within_validity']['can_accept']);
        self::assertSame('quente', $reports['lead_hot_score']['last_band']);
        self::assertFalse($reports['lead_hot_score']['can_trigger_automation']);
        self::assertSame('accepted', $reports['proposal_lifecycle_accepted']['latest_claimed_status']);
        self::assertFalse($reports['proposal_lifecycle_accepted']['can_accept']);
        self::assertSame('won', $reports['opportunity_transition_won']['latest_claimed_status']);
        self::assertSame(0, $reports['opportunity_transition_won']['recognized_wins']);
        self::assertTrue($reports['stage_requirements_satisfied']['requirements_met']);
        self::assertFalse($reports['stage_requirements_satisfied']['can_move']);
        self::assertSame(2, $reports['task_recurrence_upcoming']['upcoming_count']);
        self::assertFalse($reports['task_recurrence_upcoming']['can_schedule_job']);
        self::assertTrue($reports['discount_manager_claimed_approval']['manager_approval_claimed']);
        self::assertFalse($reports['discount_manager_claimed_approval']['manager_approval_verified']);
        self::assertFalse($reports['discount_manager_claimed_approval']['can_issue_proposal']);
        self::assertTrue($reports['proposal_send_preflight_blocked']['requires_manager_review']);
        self::assertContains(
            'manager_approval_unverified',
            $reports['proposal_send_preflight_blocked']['blocker_codes'],
        );
        self::assertFalse($reports['proposal_send_preflight_blocked']['can_send']);
        self::assertSame(1, $reports['lead_distribution_planned']['planned_assignments']);
        self::assertFalse($reports['lead_distribution_planned']['can_assign']);
        self::assertSame(100, $reports['scoring_rule_hot']['score']);
        self::assertSame('quente', $reports['scoring_rule_hot']['band']);
        self::assertFalse($reports['scoring_rule_hot']['can_trigger_automation']);
        self::assertSame(10000, $reports['goal_claimed_complete']['claimed_progress_bp_capped']);
        self::assertSame(0, $reports['goal_claimed_complete']['recognized_progress_bp']);
        self::assertFalse($reports['goal_claimed_complete']['can_pay_commissions']);
        self::assertSame('completed', $reports['task_completed_claim']['latest_claimed_status']);
        self::assertFalse($reports['task_completed_claim']['can_complete_task']);
        self::assertSame(1, $reports['report_small_bucket_suppressed']['suppressed_bucket_count']);
        self::assertSame(0, $reports['report_small_bucket_suppressed']['visible_claimed_sum_cents']);
        self::assertNull($reports['report_small_bucket_suppressed']['cells'][3]['claimed_sum_cents']);
        self::assertFalse($reports['report_small_bucket_suppressed']['can_export']);
        self::assertTrue($reports['report_delivery_access_revoked']['access_revoked_since_schedule']);
        self::assertContains('access_not_claimed_at_delivery', $reports['report_delivery_access_revoked']['blocker_codes']);
        self::assertFalse($reports['report_delivery_access_revoked']['can_deliver']);
        self::assertSame('won', $reports['full_sales_journey_claimed_won']['opportunity_claimed_status']);
        self::assertSame('accepted', $reports['full_sales_journey_claimed_won']['proposal_claimed_status']);
        self::assertSame(0, $reports['full_sales_journey_claimed_won']['recognized_wins']);
        self::assertSame(0, $reports['full_sales_journey_claimed_won']['recognized_revenue_cents']);
        self::assertFalse($reports['full_sales_journey_claimed_won']['can_send_proposal']);
        self::assertFalse($reports['full_sales_journey_claimed_won']['can_charge']);
        self::assertSame(1, $reports['stage_csv_preview_unexportable']['visible_line_count']);
        self::assertSame(1, $reports['stage_csv_preview_unexportable']['suppressed_bucket_count']);
        self::assertFalse($reports['stage_csv_preview_unexportable']['can_download']);
        self::assertFalse($reports['stage_csv_preview_unexportable']['can_export']);
        self::assertSame(1, $reports['report_hand_off_bytes_withheld']['visible_aggregate_lines']);
        self::assertFalse($reports['report_hand_off_bytes_withheld']['csv_text_returned']);
        self::assertFalse($reports['report_hand_off_bytes_withheld']['can_deliver']);
        self::assertSame(0, $reports['report_hand_off_bytes_withheld']['recognized_revenue_cents']);
        self::assertContains(
            'small_cross_snapshot_delta',
            $reports['report_differencing_singleton_warning']['risk_codes'],
        );
        self::assertFalse($reports['report_differencing_singleton_warning']['can_publish']);
        self::assertTrue($reports['private_stage_distribution_withheld']['ratios_withheld']);
        self::assertSame(
            [null, null, null, null, null],
            array_column($reports['private_stage_distribution_withheld']['stages'], 'share_basis_points'),
        );
        self::assertTrue($reports['cross_snapshot_dashboard_withheld']['all_ratios_withheld']);
        self::assertFalse($reports['cross_snapshot_dashboard_withheld']['can_deliver']);
        self::assertSame(0, $reports['cross_snapshot_dashboard_withheld']['recognized_revenue_cents']);
        self::assertSame(
            [[0, 2]],
            $reports['historical_report_pairwise_differencing_denied']['risk_pair_positions'],
        );
        self::assertContains(
            'small_delta_across_history',
            $reports['historical_report_pairwise_differencing_denied']['risk_codes'],
        );
        self::assertFalse($reports['historical_report_pairwise_differencing_denied']['can_publish']);
        self::assertFalse($reports['historical_report_pairwise_differencing_denied']['can_deliver']);
        self::assertSame(1, $reports['task_portfolio_overdue_inert']['overdue_count']);
        self::assertSame([0], $reports['task_portfolio_overdue_inert']['ordered_open_positions']);
        self::assertFalse($reports['task_portfolio_overdue_inert']['can_update_task']);
        self::assertContains(
            'fictional_lead_capacity_shortfall',
            $reports['commercial_capacity_and_overdue_inert']['workload_warning_codes'],
        );
        self::assertContains(
            'fictional_overdue_tasks',
            $reports['commercial_capacity_and_overdue_inert']['workload_warning_codes'],
        );
        self::assertFalse($reports['commercial_capacity_and_overdue_inert']['can_assign']);
        self::assertFalse($reports['commercial_capacity_and_overdue_inert']['can_notify']);
        self::assertSame('second_marker_elapsed', $reports['delinquency_day_thirty']['phase']);
        self::assertFalse($reports['delinquency_day_thirty']['can_suspend_service']);
    }

    public function test_separate_commercial_mock_controllers_are_not_public_endpoints(): void
    {
        // This suite imports only pure PHP Core classes. As a second guard,
        // prohibit accidental registration of unreviewed sales endpoints.
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        self::assertIsString($routes);
        foreach ([
            'SyntheticLineItemSubtotalPreview',
            'SyntheticProposalVersionReview',
            'SyntheticWeightedSalesForecast',
            'SyntheticOpportunityOutcomeInspector',
            'SyntheticSalesEventUniquenessInspector',
            'SyntheticProposalValidityPreview',
            'SyntheticProposalLifecycleInspector',
            'SyntheticOpportunityTransitionLedger',
            'SyntheticStageFieldRequirementsInspector',
            'SyntheticRecurringTaskSchedule',
            'SyntheticCommercialDiscountReview',
            'SyntheticProposalSendPreflight',
            'SyntheticLeadRoundRobinPreview',
            'SyntheticLeadRuleScorePreview',
            'SyntheticSalesGoalProgressPreview',
            'SyntheticTaskLifecycleInspector',
            'SyntheticSalesAggregatePrivacyPreview',
            'SyntheticScheduledReportDeliveryPreflight',
            'SyntheticCommercialJourneyInspector',
            'SyntheticStageAggregateCsvPreview',
            'SyntheticReportDeliveryCompositeInspector',
            'SyntheticReportDifferencingRiskInspector',
            'SyntheticPrivateStageDistributionPreview',
            'SyntheticStageDashboardSnapshotReview',
            'SyntheticReportHistoryPairwiseRiskInspector',
            'SyntheticTaskPortfolioInspector',
            'SyntheticCommercialWorkloadInspector',
        ] as $class) {
            self::assertStringNotContainsString($class, $routes);
        }
    }
}
