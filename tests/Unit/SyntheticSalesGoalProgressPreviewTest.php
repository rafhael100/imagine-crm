<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticSalesEventUniquenessInspector;
use App\Core\Sales\SyntheticSalesGoalProgressPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSalesGoalProgressPreviewTest extends TestCase
{
    private const A = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const B = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    private const C = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    /** @return array{event_reference:string,amount_cents:int} */
    private static function claim(string $id, int $amount): array
    {
        return ['event_reference' => $id, 'amount_cents' => $amount];
    }

    public function test_two_distinct_fictional_claims_show_rounded_ratio_but_no_recognized_sales(): void
    {
        $claims = [
            self::claim(self::A, 10000),
            self::claim(self::B, 30000),
        ];
        $original = $claims;
        $report = (new SyntheticSalesGoalProgressPreview())->preview($claims, 60000);
        self::assertSame('BRL', $report['currency_code']);
        self::assertSame(2, $report['claimed_event_count']);
        self::assertSame(2, $report['unique_reference_count']);
        self::assertSame(60000, $report['illustrative_goal_cents']);
        self::assertSame(40000, $report['claimed_event_total_cents']);
        self::assertSame(6667, $report['claimed_progress_bp_capped']);
        self::assertSame(20000, $report['claimed_shortfall_cents']);
        self::assertSame(0, $report['claimed_over_goal_cents']);
        self::assertSame(0, $report['recognized_sales_count']);
        self::assertSame(0, $report['recognized_revenue_cents']);
        self::assertSame(0, $report['recognized_progress_bp']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'goal_policy_verified', 'event_provenance_verified', 'period_verified',
            'authorized', 'can_update_targets', 'can_pay_commissions',
            'can_notify', 'can_export', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag]);
        }
        self::assertSame($original, $claims);
        self::assertSame($report, (new SyntheticSalesGoalProgressPreview())->preview($claims, 60000));
    }

    public function test_zero_exact_and_over_goal_are_claimed_only_and_never_pay_commissions(): void
    {
        $preview = new SyntheticSalesGoalProgressPreview();
        $empty = $preview->preview([], 60000);
        self::assertSame(0, $empty['claimed_event_total_cents']);
        self::assertSame(0, $empty['claimed_progress_bp_capped']);
        self::assertSame(60000, $empty['claimed_shortfall_cents']);
        self::assertSame(0, $empty['claimed_over_goal_cents']);

        $exact = $preview->preview([self::claim(self::A, 60000)], 60000);
        self::assertSame(10000, $exact['claimed_progress_bp_capped']);
        self::assertSame(0, $exact['claimed_shortfall_cents']);
        self::assertSame(0, $exact['claimed_over_goal_cents']);
        self::assertSame(0, $exact['recognized_progress_bp']);

        $beyond = $preview->preview([
            self::claim(self::A, 60000), self::claim(self::B, 20000),
        ], 60000);
        self::assertSame(10000, $beyond['claimed_progress_bp_capped']);
        self::assertSame(20000, $beyond['claimed_over_goal_cents']);
        self::assertSame(0, $beyond['claimed_shortfall_cents']);
        self::assertSame(0, $beyond['recognized_sales_count']);
        self::assertFalse($beyond['can_pay_commissions']);
        self::assertFalse($beyond['can_update_targets']);
    }

    public function test_ratio_near_two_to_the_fifty_third_boundary_remains_exact_and_safe(): void
    {
        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $preview = new SyntheticSalesGoalProgressPreview();
        $large = $preview->preview([self::claim(self::A, $max)], $max);
        self::assertSame($max, $large['claimed_event_total_cents']);
        self::assertSame(10000, $large['claimed_progress_bp_capped']);
        self::assertSame(0, $large['claimed_over_goal_cents']);

        $mid = $preview->preview([
            self::claim(self::A, intdiv($max, 2)),
        ], $max);
        self::assertSame(5000, $mid['claimed_progress_bp_capped']);
        self::assertSame($max - intdiv($max, 2), $mid['claimed_shortfall_cents']);

        $tiny = $preview->preview([self::claim(self::A, 1)], $max);
        self::assertSame(0, $tiny['claimed_progress_bp_capped']);
        self::assertSame($max - 1, $tiny['claimed_shortfall_cents']);

        $oneCent = $preview->preview([self::claim(self::A, 1)], 3);
        self::assertSame(3333, $oneCent['claimed_progress_bp_capped']);
        self::assertSame(2, $oneCent['claimed_shortfall_cents']);
        self::assertSame(0, $oneCent['recognized_revenue_cents']);
    }

    public function test_duplicated_or_case_variant_sale_references_fail_instead_of_counting_twice(): void
    {
        $preview = new SyntheticSalesGoalProgressPreview();
        $bad = [
            [self::claim(self::A, 20000), self::claim(self::A, 20000)],
            [self::claim(self::A, 20000), self::claim(strtoupper(self::A), 20000)],
            [self::claim(self::A, 20000), ['event_reference' => 'not-a-uuid', 'amount_cents' => 20000]],
            [self::claim(self::A, 20000), ['event_reference' => self::B, 'amount_cents' => '20000']],
            [array_merge(self::claim(self::A, 20000), ['tenant' => 'private@example.test'])],
            [self::claim(self::A, $preview::class === 'x' ? 1 : PHP_INT_MAX)],
        ];
        foreach ($bad as $claims) {
            try {
                $preview->preview($claims, 60000);
                self::fail('Duplicate or invalid fictional sales event counted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Meta sintetica de vendas invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_invalid_target_shape_and_aggregate_overflow_are_denied(): void
    {
        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $claims = [self::claim(self::A, 1)];
        $bad = [
            [$claims, null], [$claims, '60000'], [$claims, 60000.0],
            [$claims, -1], [$claims, 0], [$claims, $max + 1],
            [null, 60000], ['claims', 60000],
            [[self::claim(self::A, $max), self::claim(self::B, 1)], 60000],
        ];
        foreach ($bad as [$records, $goal]) {
            try {
                (new SyntheticSalesGoalProgressPreview())->preview($records, $goal);
                self::fail('Invalid goal or overflowing sales events accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Meta sintetica de vendas invalida.', $error->getMessage());
            }
        }
    }

    public function test_contract_has_only_aggregates_and_closed_denial_flags(): void
    {
        $report = (new SyntheticSalesGoalProgressPreview())->preview([
            self::claim(self::A, 12345),
        ], 50000);
        self::assertSame([
            'currency_code', 'claimed_event_count', 'unique_reference_count',
            'illustrative_goal_cents', 'claimed_event_total_cents',
            'claimed_progress_bp_capped', 'claimed_shortfall_cents',
            'claimed_over_goal_cents', 'recognized_sales_count',
            'recognized_revenue_cents', 'recognized_progress_bp',
            'goal_policy_verified', 'event_provenance_verified',
            'period_verified', 'informational_only', 'authorized',
            'can_update_targets', 'can_pay_commissions', 'can_notify',
            'can_export', 'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::A, $json);
        foreach ([
            'tenant', 'customer', 'lead_id', 'opportunity_id', 'event_reference',
            'actor', 'email', 'phone', 'payment', 'invoice_id',
        ] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
        self::assertFalse($report['authorized']);
    }
}
