<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticWeightedSalesForecast;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticWeightedSalesForecastTest extends TestCase
{
    public function test_example_pipeline_has_exact_line_rounding_and_totals(): void
    {
        $items = [
            ['amount_cents' => 12990, 'win_probability_bp' => 5000],
            ['amount_cents' => 7999, 'win_probability_bp' => 2500],
            ['amount_cents' => 60000, 'win_probability_bp' => 7500],
        ];
        $original = $items;
        $report = (new SyntheticWeightedSalesForecast())->preview($items);
        self::assertSame('BRL', $report['currency_code']);
        self::assertSame(3, $report['opportunity_count']);
        self::assertSame(80989, $report['unweighted_total_cents']);
        self::assertSame(53495, $report['weighted_total_cents']);
        self::assertSame([6495, 2000, 45000], $report['weighted_line_cents']);
        self::assertSame('half_up_per_line', $report['rounding']);
        self::assertSame(array_sum($report['weighted_line_cents']), $report['weighted_total_cents']);
        self::assertSame($original, $items);
        self::assertSame($report, (new SyntheticWeightedSalesForecast())->preview($items));
        self::assertTrue($report['informational_only']);
        foreach ([
            'probabilities_verified', 'forecast_verified', 'authorized',
            'can_mark_won', 'can_invoice', 'can_charge', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag]);
        }
    }

    public function test_zero_and_full_probabilities_and_individual_half_cent_rounding(): void
    {
        $forecast = new SyntheticWeightedSalesForecast();
        $report = $forecast->preview([
            ['amount_cents' => 99999, 'win_probability_bp' => 0],
            ['amount_cents' => 12000, 'win_probability_bp' => 10000],
            ['amount_cents' => 1, 'win_probability_bp' => 5000],
            ['amount_cents' => 1, 'win_probability_bp' => 4999],
            ['amount_cents' => 0, 'win_probability_bp' => 10000],
        ]);
        self::assertSame([0, 12000, 1, 0, 0], $report['weighted_line_cents']);
        self::assertSame(12001, $report['weighted_total_cents']);
        self::assertSame(112001, $report['unweighted_total_cents']);
        self::assertSame(5, $report['opportunity_count']);
        self::assertSame(0, $forecast->preview([])['weighted_total_cents']);
    }

    public function test_maximum_safe_integer_is_valid_with_no_multiplication_overflow(): void
    {
        $max = SyntheticWeightedSalesForecast::MAX_EXACT_CENTS;
        $forecast = new SyntheticWeightedSalesForecast();
        $full = $forecast->preview([
            ['amount_cents' => $max, 'win_probability_bp' => 10000],
        ]);
        self::assertSame($max, $full['unweighted_total_cents']);
        self::assertSame($max, $full['weighted_total_cents']);

        $half = $forecast->preview([
            ['amount_cents' => $max, 'win_probability_bp' => 5000],
        ]);
        self::assertSame(intdiv($max, 2) + 1, $half['weighted_total_cents']);
        self::assertLessThanOrEqual($half['unweighted_total_cents'], $max);

        $report = $forecast->preview([
            ['amount_cents' => $max - 1, 'win_probability_bp' => 1000],
            ['amount_cents' => 1, 'win_probability_bp' => 5000],
        ]);
        self::assertSame($max, $report['unweighted_total_cents']);
        self::assertSame(array_sum($report['weighted_line_cents']), $report['weighted_total_cents']);
    }

    public function test_invalid_fields_probabilities_oversize_and_sum_overflow_fail_closed(): void
    {
        $good = ['amount_cents' => 1000, 'win_probability_bp' => 5000];
        $max = SyntheticWeightedSalesForecast::MAX_EXACT_CENTS;
        $invalid = [
            null, true, 5, 'opportunity',
            [1 => $good],
            [null],
            [['amount_cents' => -1, 'win_probability_bp' => 100]],
            [['amount_cents' => '100', 'win_probability_bp' => 100]],
            [['amount_cents' => 1.5, 'win_probability_bp' => 100]],
            [['amount_cents' => true, 'win_probability_bp' => 100]],
            [['amount_cents' => PHP_INT_MAX, 'win_probability_bp' => 100]],
            [['amount_cents' => 100, 'win_probability_bp' => -1]],
            [['amount_cents' => 100, 'win_probability_bp' => 10001]],
            [['amount_cents' => 100, 'win_probability_bp' => '5000']],
            [['amount_cents' => 100, 'win_probability_bp' => 50.0]],
            [['amount_cents' => 100, 'win_probability_bp' => true]],
            [['amount_cents' => 100]],
            [['amount_cents' => 100, 'win_probability_bp' => 10, 'tenant' => 'private@example.test']],
            [['amount_cents' => 100, 'win_probability_bp' => 10, 'customer_id' => 'invented']],
            [['amount_cents' => $max, 'win_probability_bp' => 10000], $good],
            array_fill(0, 21, $good),
        ];
        foreach ($invalid as $input) {
            try {
                (new SyntheticWeightedSalesForecast())->preview($input);
                self::fail('Bad or identity-bearing forecast was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Previsao ponderada sintetica invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_result_contains_no_real_sales_evidence_or_customer_identifiers(): void
    {
        $report = (new SyntheticWeightedSalesForecast())->preview([
            ['win_probability_bp' => 3000, 'amount_cents' => 10000],
        ]);
        self::assertSame([
            'currency_code', 'opportunity_count', 'unweighted_total_cents',
            'weighted_total_cents', 'weighted_line_cents', 'rounding',
            'probabilities_verified', 'forecast_verified', 'informational_only',
            'authorized', 'can_mark_won', 'can_invoice', 'can_charge', 'can_persist',
        ], array_keys($report));
        self::assertSame(3000, $report['weighted_total_cents']);
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['tenant', 'email', 'phone', 'lead_id', 'opportunity_id', 'customer', 'invoice', 'paid'] as $secret) {
            self::assertStringNotContainsString('"'.$secret.'"', $json);
        }
    }
}
