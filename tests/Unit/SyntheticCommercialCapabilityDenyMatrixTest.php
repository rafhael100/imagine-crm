<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpQueue;
use App\Core\Leads\SyntheticScoreTrendPreview;
use App\Core\Plans\SyntheticCancellationRetentionTimeline;
use App\Core\Plans\SyntheticDelinquencyReviewTimeline;
use App\Core\Plans\SyntheticTrialRetentionTimeline;
use App\Core\Plans\TrialWindowAdvisory;
use App\Core\Sales\SyntheticLineItemSubtotalPreview;
use App\Core\Sales\SyntheticOpportunityOutcomeInspector;
use App\Core\Sales\SyntheticProposalValidityPreview;
use App\Core\Sales\SyntheticProposalVersionReview;
use App\Core\Sales\SyntheticSalesEventUniquenessInspector;
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

    public function test_all_ten_business_laboratories_have_no_operational_capability_even_on_positive_claims(): void
    {
        $reports = self::reports();
        self::assertCount(10, $reports);
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
        ] as $class) {
            self::assertStringNotContainsString($class, $routes);
        }
    }
}
