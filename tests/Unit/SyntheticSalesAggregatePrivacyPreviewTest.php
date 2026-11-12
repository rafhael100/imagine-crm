<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticSalesAggregatePrivacyPreview;
use App\Core\Sales\SyntheticSalesEventUniquenessInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSalesAggregatePrivacyPreviewTest extends TestCase
{
    /** @return array{stage:string,claimed_value_cents:int} */
    private static function row(string $stage, int $value): array
    {
        return ['stage' => $stage, 'claimed_value_cents' => $value];
    }

    public function test_small_groups_are_suppressed_without_revealing_a_grand_total(): void
    {
        $rows = [
            self::row('new', 100),
            self::row('new', 200),
            self::row('new', 300),
            self::row('proposal', 555555),
            self::row('won', 10000),
            self::row('won', 20000),
        ];
        $original = $rows;
        $report = (new SyntheticSalesAggregatePrivacyPreview())->summarize($rows, 3);
        self::assertSame('BRL', $report['currency_code']);
        self::assertSame(3, $report['minimum_group_size']);
        self::assertSame([
            ['stage' => 'new', 'count' => 3, 'claimed_sum_cents' => 600, 'suppressed' => false],
            ['stage' => 'qualified', 'count' => 0, 'claimed_sum_cents' => 0, 'suppressed' => false],
            ['stage' => 'proposal', 'count' => null, 'claimed_sum_cents' => null, 'suppressed' => true],
            ['stage' => 'won', 'count' => null, 'claimed_sum_cents' => null, 'suppressed' => true],
            ['stage' => 'lost', 'count' => 0, 'claimed_sum_cents' => 0, 'suppressed' => false],
        ], $report['cells']);
        self::assertSame(1, $report['visible_bucket_count']);
        self::assertSame(2, $report['suppressed_bucket_count']);
        self::assertSame(600, $report['visible_claimed_sum_cents']);
        self::assertSame(0, $report['recognized_revenue_cents']);
        self::assertSame($original, $rows);
        self::assertSame($report, (new SyntheticSalesAggregatePrivacyPreview())->summarize($rows, 3));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('555555', $json);
        self::assertStringNotContainsString('30000', $json);
        self::assertStringNotContainsString('grand_total', $json);
        self::assertStringNotContainsString('"row_count"', $json);
        self::assertStringNotContainsString('"all_rows_total_cents"', $json);
    }

    public function test_suppression_threshold_is_illustrative_not_real_tenant_policy(): void
    {
        $four = array_fill(0, 4, self::row('lost', 250));
        $strict = (new SyntheticSalesAggregatePrivacyPreview())->summarize($four, 5);
        self::assertSame(1, $strict['suppressed_bucket_count']);
        self::assertNull($strict['cells'][4]['count']);
        self::assertNull($strict['cells'][4]['claimed_sum_cents']);
        self::assertSame(0, $strict['visible_claimed_sum_cents']);
        self::assertFalse($strict['privacy_policy_verified']);
        self::assertFalse($strict['tenant_scope_verified']);
        self::assertFalse($strict['can_export']);

        $relaxed = (new SyntheticSalesAggregatePrivacyPreview())->summarize(
            $four, 2,
        );
        self::assertSame(4, $relaxed['cells'][4]['count']);
        self::assertSame(1000, $relaxed['cells'][4]['claimed_sum_cents']);
        self::assertSame(1000, $relaxed['visible_claimed_sum_cents']);
        self::assertFalse($relaxed['report_permissions_verified']);
        self::assertFalse($relaxed['can_schedule_report']);
    }

    public function test_claimed_won_values_never_turn_into_verified_revenue(): void
    {
        $report = (new SyntheticSalesAggregatePrivacyPreview())->summarize([
            self::row('won', 10000),
            self::row('won', 20000),
            self::row('won', 30000),
        ]);
        self::assertSame(60000, $report['cells'][3]['claimed_sum_cents']);
        self::assertSame(3, $report['cells'][3]['count']);
        self::assertSame(0, $report['recognized_revenue_cents']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'privacy_policy_verified', 'tenant_scope_verified',
            'report_permissions_verified', 'claims_verified',
            'authorized', 'can_read_customers', 'can_export',
            'can_schedule_report', 'can_notify', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag]);
        }

        $empty = (new SyntheticSalesAggregatePrivacyPreview())->summarize([]);
        self::assertSame(0, $empty['visible_bucket_count']);
        self::assertSame(0, $empty['suppressed_bucket_count']);
        self::assertSame(0, $empty['visible_claimed_sum_cents']);
        foreach ($empty['cells'] as $cell) {
            self::assertSame(0, $cell['count']);
            self::assertSame(0, $cell['claimed_sum_cents']);
            self::assertFalse($cell['suppressed']);
        }
    }

    public function test_integer_bounds_are_checked_across_suppressed_and_visible_groups(): void
    {
        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $review = new SyntheticSalesAggregatePrivacyPreview();
        $alone = $review->summarize([self::row('new', $max)]);
        self::assertSame(1, $alone['suppressed_bucket_count']);
        self::assertSame(0, $alone['visible_claimed_sum_cents']);
        self::assertNull($alone['cells'][0]['claimed_sum_cents']);

        $visible = $review->summarize([
            self::row('won', $max - 2),
            self::row('won', 1),
            self::row('won', 1),
        ], 3);
        self::assertSame($max, $visible['visible_claimed_sum_cents']);
        self::assertSame(0, $visible['recognized_revenue_cents']);
    }

    public function test_invalid_hidden_rows_thresholds_and_private_fields_fail_closed(): void
    {
        $good = self::row('new', 100);
        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $bad = [
            [null, 3], ['rows', 3], [false, 3],
            [[null], 3], [[1 => $good], 3],
            [[$good], null], [[$good], '3'], [[$good], 3.0],
            [[$good], 1], [[$good], 6],
            [[self::row('NEW', 100)], 3],
            [[self::row('hidden', 100)], 3],
            [[self::row('new', -1)], 3],
            [[self::row('new', PHP_INT_MAX)], 3],
            [[['stage' => 'new', 'claimed_value_cents' => '100']], 3],
            [[['stage' => 'new', 'claimed_value_cents' => 100.0]], 3],
            [[array_merge($good, ['email' => 'private@example.test'])], 3],
            [[array_merge($good, ['tenant' => 'secret'])], 3],
            [[['stage' => 'new']], 3],
            [array_fill(0, 21, $good), 3],
            [[self::row('proposal', $max), self::row('won', 1)], 3],
            [
                [self::row('new', 1), self::row('new', 1),
                    array_merge($good, ['phone' => 'private@example.test'])],
                3,
            ],
        ];
        foreach ($bad as [$rows, $threshold]) {
            try {
                (new SyntheticSalesAggregatePrivacyPreview())->summarize($rows, $threshold);
                self::fail('Invalid, hidden or private report input was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Agregacao sintetica comercial invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_report_schema_hides_identity_and_has_no_export_right(): void
    {
        $report = (new SyntheticSalesAggregatePrivacyPreview())->summarize([
            self::row('new', 100),
        ]);
        self::assertSame([
            'currency_code', 'minimum_group_size', 'cells',
            'visible_bucket_count', 'suppressed_bucket_count',
            'visible_claimed_sum_cents', 'recognized_revenue_cents',
            'privacy_policy_verified', 'tenant_scope_verified',
            'report_permissions_verified', 'claims_verified',
            'informational_only', 'authorized', 'can_read_customers',
            'can_export', 'can_schedule_report', 'can_notify', 'can_persist',
        ], array_keys($report));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach ([
            'customer', 'tenant', 'email', 'phone', 'lead_id', 'opportunity_id',
            'event_reference', 'actor', 'token', 'recipient',
        ] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
        self::assertFalse($report['can_export']);
        self::assertFalse($report['can_read_customers']);
    }
}
