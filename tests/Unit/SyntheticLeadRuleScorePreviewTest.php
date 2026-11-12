<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadScoringBands;
use App\Core\Leads\SyntheticLeadRuleScorePreview;
use App\Core\Leads\SyntheticScoreTrendPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadRuleScorePreviewTest extends TestCase
{
    /** @return array<string,bool> */
    private static function signals(): array
    {
        return [
            'profile_complete' => true,
            'recent_engagement' => true,
            'requested_quote' => true,
            'scheduled_demo' => false,
            'stale_response' => true,
        ];
    }

    /** @return array<string,int> */
    private static function weights(): array
    {
        return [
            'profile_complete' => 10,
            'recent_engagement' => 20,
            'requested_quote' => 35,
            'scheduled_demo' => 15,
            'stale_response' => -20,
        ];
    }

    public function test_five_fabricated_signals_produce_exact_rule_scoring_and_temperature(): void
    {
        $signals = self::signals();
        $weights = self::weights();
        $originalSignals = $signals;
        $originalWeights = $weights;
        $report = (new SyntheticLeadRuleScorePreview())->preview($signals, $weights, 20);

        self::assertSame(5, $report['signal_count']);
        self::assertSame(4, $report['active_signal_count']);
        self::assertSame(20, $report['base_score']);
        self::assertSame(65, $report['positive_points']);
        self::assertSame(-20, $report['negative_points']);
        self::assertSame(65, $report['raw_score']);
        self::assertSame(65, $report['score']);
        self::assertSame('morno', $report['band']);
        self::assertFalse($report['clamped']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'signals_verified', 'weight_policy_verified', 'band_policy_verified',
            'authorized', 'can_update_lead', 'can_trigger_automation',
            'can_redistribute', 'can_notify', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag]);
        }
        self::assertSame($originalSignals, $signals);
        self::assertSame($originalWeights, $weights);
        self::assertSame($report, (new SyntheticLeadRuleScorePreview())->preview($signals, $weights, 20));
    }

    public function test_policy_can_be_illustratively_customized_without_becoming_verified(): void
    {
        $preview = new SyntheticLeadRuleScorePreview();
        $standard = $preview->preview(self::signals(), self::weights(), 20);
        $custom = $preview->preview(
            self::signals(), self::weights(), 20, new LeadScoringBands(30, 60),
        );

        self::assertSame(65, $standard['score']);
        self::assertSame($standard['score'], $custom['score']);
        self::assertSame('morno', $standard['band']);
        self::assertSame('quente', $custom['band']);
        self::assertFalse($custom['band_policy_verified']);
        self::assertFalse($custom['can_update_lead']);
    }

    public function test_clamping_at_zero_and_hundred_is_deterministic_and_not_authorized(): void
    {
        $preview = new SyntheticLeadRuleScorePreview();
        $all = array_fill_keys(array_keys(self::signals()), true);
        $maximum = array_fill_keys(array_keys(self::weights()), 100);
        $minimum = array_fill_keys(array_keys(self::weights()), -100);

        $high = $preview->preview($all, $maximum, 50);
        self::assertSame(550, $high['raw_score']);
        self::assertSame(100, $high['score']);
        self::assertSame('quente', $high['band']);
        self::assertTrue($high['clamped']);
        self::assertSame(500, $high['positive_points']);
        self::assertSame(0, $high['negative_points']);

        $low = $preview->preview($all, $minimum, 50);
        self::assertSame(-450, $low['raw_score']);
        self::assertSame(0, $low['score']);
        self::assertSame('frio', $low['band']);
        self::assertTrue($low['clamped']);
        self::assertSame(-500, $low['negative_points']);

        $none = array_fill_keys(array_keys(self::signals()), false);
        $neutral = $preview->preview($none, $maximum, 39);
        self::assertSame(0, $neutral['active_signal_count']);
        self::assertSame(39, $neutral['score']);
        self::assertSame('frio', $neutral['band']);
        self::assertFalse($neutral['can_trigger_automation']);
    }

    public function test_score_can_be_passed_to_existing_synthetic_trend_without_running_automation(): void
    {
        $preview = new SyntheticLeadRuleScorePreview();
        $before = $preview->preview(self::signals(), self::weights(), 20);
        $afterSignals = self::signals();
        $afterSignals['scheduled_demo'] = true;
        $after = $preview->preview($afterSignals, self::weights(), 20);
        self::assertSame(65, $before['score']);
        self::assertSame(80, $after['score']);

        $trend = (new SyntheticScoreTrendPreview())->preview([
            ['observed_at_utc' => '2026-11-01T10:00:00Z', 'score' => $before['score']],
            ['observed_at_utc' => '2026-11-02T10:00:00Z', 'score' => $after['score']],
        ]);
        self::assertSame(15, $trend['score_delta']);
        self::assertSame('morno', $trend['first_band']);
        self::assertSame('quente', $trend['last_band']);
        self::assertSame(1, $trend['band_changes']);
        self::assertFalse($trend['can_trigger_automation']);
        self::assertFalse($before['can_update_lead']);
        self::assertFalse($after['can_notify']);
    }

    public function test_invalid_signals_weights_scores_and_private_fields_fail_closed(): void
    {
        $signals = self::signals();
        $weights = self::weights();
        $bad = [
            [null, $weights, 20],
            [$signals, null, 20],
            ['signals', $weights, 20],
            [$signals, 'weights', 20],
            [$signals, $weights, '20'],
            [$signals, $weights, 20.0],
            [$signals, $weights, -1],
            [$signals, $weights, 101],
            [array_merge($signals, ['email' => 'private@example.test']), $weights, 20],
            [$signals, array_merge($weights, ['tenant' => 1]), 20],
            [array_merge($signals, ['profile_complete' => 1]), $weights, 20],
            [array_merge($signals, ['profile_complete' => 'yes']), $weights, 20],
            [$signals, array_merge($weights, ['requested_quote' => 100.0]), 20],
            [$signals, array_merge($weights, ['requested_quote' => '35']), 20],
            [$signals, array_merge($weights, ['requested_quote' => 101]), 20],
            [$signals, array_merge($weights, ['requested_quote' => -101]), 20],
            [array_diff_key($signals, ['recent_engagement' => true]), $weights, 20],
            [$signals, array_diff_key($weights, ['recent_engagement' => 1]), 20],
            [[true, true, true, true, true], $weights, 20],
            [$signals, [10, 20, 35, 15, -20], 20],
        ];

        foreach ($bad as [$s, $w, $base]) {
            try {
                (new SyntheticLeadRuleScorePreview())->preview($s, $w, $base);
                self::fail('Unsafe or malformed lead rule scoring input was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Pontuacao sintetica por regras invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_closed_report_contains_no_evidence_payload_or_identity(): void
    {
        $report = (new SyntheticLeadRuleScorePreview())
            ->preview(self::signals(), self::weights(), 20);
        self::assertSame([
            'signal_count', 'active_signal_count', 'base_score',
            'positive_points', 'negative_points', 'raw_score', 'score',
            'band', 'clamped', 'signals_verified', 'weight_policy_verified',
            'band_policy_verified', 'informational_only', 'authorized',
            'can_update_lead', 'can_trigger_automation',
            'can_redistribute', 'can_notify', 'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach ([
            'tenant', 'lead_id', 'customer', 'email', 'phone', 'signal_data',
            'actor', 'event_reference', 'ip_address', 'source_data',
        ] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
        self::assertFalse($report['can_trigger_automation']);
    }
}
