<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaSnapshotBand;
use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticQuotaSnapshotComparison;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticQuotaSnapshotComparisonTest extends TestCase
{
    /**
     * @param list<array{string,int,int}> $meters
     */
    private function readout(array $meters): QuotaSnapshotReadout
    {
        $snapshots = [];
        foreach ($meters as [$meter, $limit, $used]) {
            $snapshots[] = new QuotaUsageProjection($meter, $limit, $used, 0);
        }

        return new QuotaSnapshotReadout($snapshots);
    }

    public function test_usage_and_limit_deltas_are_deterministic_and_preserve_earlier_order(): void
    {
        $earlier = $this->readout([
            ['leads', 20, 4],
            ['users', 2, 2],
            ['automations', 0, 0],
            ['storage_bytes', 10, 12],
        ]);
        // Reversed order must not silently change the correspondence.
        $later = $this->readout([
            ['storage_bytes', 10, 7],
            ['automations', 3, 0],
            ['users', 2, 3],
            ['leads', 25, 9],
        ]);
        $result = (new SyntheticQuotaSnapshotComparison($earlier, $later))->compare();

        self::assertSame(['leads', 'users', 'automations', 'storage_bytes'], array_column($result['rows'], 'meter'));
        self::assertSame(4, $result['meters']);
        self::assertSame(2, $result['usage_increased']);
        self::assertSame(1, $result['usage_decreased']);
        self::assertSame(1, $result['usage_unchanged']);
        self::assertSame(2, $result['limits_changed']);
        self::assertSame(4, $result['bands_changed']);

        $lead = $result['rows'][0];
        self::assertSame(4, $lead['earlier_used']);
        self::assertSame(9, $lead['later_used']);
        self::assertSame(5, $lead['usage_delta']);
        self::assertSame(5, $lead['limit_delta']);
        self::assertSame(16, $lead['earlier_remaining']);
        self::assertSame(16, $lead['later_remaining']);
        self::assertSame(0, $lead['remaining_delta']);
        self::assertSame(QuotaSnapshotBand::InRange, $lead['earlier_band']);
        self::assertSame(QuotaSnapshotBand::InRange, $lead['later_band']);
        self::assertSame('increased', $lead['usage_trend']);
        self::assertTrue($lead['limit_changed']);
        self::assertFalse($lead['band_changed']);

        $users = $result['rows'][1];
        self::assertSame(1, $users['usage_delta']);
        self::assertSame(1, $users['excess_delta']);
        self::assertSame(QuotaSnapshotBand::AtLimit, $users['earlier_band']);
        self::assertSame(QuotaSnapshotBand::OverLimit, $users['later_band']);
        self::assertTrue($users['band_changed']);

        $automations = $result['rows'][2];
        self::assertSame(3, $automations['limit_delta']);
        self::assertSame(3, $automations['remaining_delta']);
        self::assertSame('unchanged', $automations['usage_trend']);
        self::assertSame(QuotaSnapshotBand::ZeroCapacity, $automations['earlier_band']);
        self::assertSame(QuotaSnapshotBand::InRange, $automations['later_band']);

        $storage = $result['rows'][3];
        self::assertSame(-5, $storage['usage_delta']);
        self::assertSame(-2, $storage['excess_delta']);
        self::assertSame(3, $storage['later_remaining']);
        self::assertSame(QuotaSnapshotBand::OverLimit, $storage['earlier_band']);
        self::assertSame(QuotaSnapshotBand::InRange, $storage['later_band']);

        self::assertSame([
            'rows','meters','usage_increased','usage_decreased','usage_unchanged',
            'limits_changed','bands_changed','informational_only','authorized',
            'can_charge','can_enforce','can_export',
        ], array_keys($result));
        self::assertTrue($result['informational_only']);
        foreach (['authorized','can_charge','can_enforce','can_export'] as $name) {
            self::assertFalse($result[$name]);
        }
    }

    public function test_unmatched_added_or_removed_meter_is_rejected_without_reflecting_names(): void
    {
        $valid = $this->readout([['leads', 20, 4], ['users', 2, 1]]);
        foreach ([
            $this->readout([['leads', 20, 5]]),
            $this->readout([['leads', 20, 5], ['forged_meter', 2, 1]]),
            $this->readout([['leads', 20, 5], ['users', 2, 1], ['forged_meter', 1, 1]]),
        ] as $invalid) {
            try {
                (new SyntheticQuotaSnapshotComparison($valid, $invalid))->compare();
                self::fail('A comparison with different meter sets was allowed.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Comparacao sintetica de franquias invalida.', $e->getMessage());
                self::assertStringNotContainsString('forged_meter', $e->getMessage());
            }
        }
    }

    public function test_one_meter_and_twelve_meters_do_not_overflow_or_sum_large_usage(): void
    {
        $one = (new SyntheticQuotaSnapshotComparison(
            $this->readout([['large', PHP_INT_MAX, PHP_INT_MAX]]),
            $this->readout([['large', 0, 0]]),
        ))->compare();
        self::assertSame(-PHP_INT_MAX, $one['rows'][0]['usage_delta']);
        self::assertSame(-PHP_INT_MAX, $one['rows'][0]['limit_delta']);
        self::assertSame(0, $one['rows'][0]['remaining_delta']);
        self::assertSame(1, $one['usage_decreased']);
        self::assertFalse($one['can_charge']);

        $before = [];
        $after = [];
        for ($i = 0; $i < 12; ++$i) {
            $before[] = ['meter_'.$i, PHP_INT_MAX, 0];
            $after[] = ['meter_'.$i, PHP_INT_MAX, PHP_INT_MAX];
        }
        $output = (new SyntheticQuotaSnapshotComparison(
            $this->readout($before),
            $this->readout(array_reverse($after)),
        ))->compare();
        self::assertCount(12, $output['rows']);
        self::assertSame(12, $output['usage_increased']);
        self::assertSame(0, $output['usage_decreased']);
        self::assertSame('meter_0', $output['rows'][0]['meter']);
        self::assertSame('meter_11', $output['rows'][11]['meter']);
        self::assertSame(PHP_INT_MAX, $output['rows'][11]['usage_delta']);
        self::assertFalse($output['authorized']);
    }

    public function test_unchanged_pair_has_no_false_trend_and_cannot_enforce(): void
    {
        $same = $this->readout([['leads', 0, 0]]);
        $result = (new SyntheticQuotaSnapshotComparison($same, $same))->compare();
        self::assertSame(0, $result['usage_increased']);
        self::assertSame(0, $result['usage_decreased']);
        self::assertSame(1, $result['usage_unchanged']);
        self::assertSame(0, $result['bands_changed']);
        self::assertSame(0, $result['limits_changed']);
        self::assertSame(QuotaSnapshotBand::ZeroCapacity, $result['rows'][0]['later_band']);
        self::assertFalse($result['can_enforce']);

        $obj = new SyntheticQuotaSnapshotComparison($same, $same);
        self::assertTrue((new \ReflectionClass(SyntheticQuotaSnapshotComparison::class))->isReadOnly());
        self::assertSame(['compare'], array_values(array_filter(
            get_class_methods($obj),
            static fn (string $name): bool => $name !== '__construct',
        )));
        foreach (['authorize','grant','charge','commit','save','reserve','persist','enforce','export'] as $name) {
            self::assertFalse(method_exists($obj, $name));
        }
    }
}
