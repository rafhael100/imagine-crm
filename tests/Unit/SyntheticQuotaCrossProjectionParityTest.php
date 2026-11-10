<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticQuotaImpactPreview;
use App\Core\Plans\SyntheticQuotaSnapshotComparison;
use PHPUnit\Framework\TestCase;

/**
 * QA-REV-F5-04: cross-check every informational projection using fabricated
 * integers only, never a tenant or a trusted usage stream.
 */
final class SyntheticQuotaCrossProjectionParityTest extends TestCase
{
    public function test_all_thirty_six_illustrative_state_pairs_have_consistent_arithmetic(): void
    {
        $states = [
            [0, 0],
            [20, 4],
            [20, 20],
            [20, 21],
            [PHP_INT_MAX, PHP_INT_MAX],
            [0, PHP_INT_MAX],
        ];

        foreach ($states as [$earlierLimit, $earlierUsed]) {
            foreach ($states as [$laterLimit, $laterUsed]) {
                $earlierProjection = new QuotaUsageProjection('leads', $earlierLimit, $earlierUsed, 0);
                $laterProjection = new QuotaUsageProjection('leads', $laterLimit, $laterUsed, 0);
                $earlier = new QuotaSnapshotReadout([$earlierProjection]);
                $later = new QuotaSnapshotReadout([$laterProjection]);

                $output = (new SyntheticQuotaSnapshotComparison($earlier, $later))->compare();
                $row = $output['rows'][0];

                self::assertSame(1, $output['meters']);
                self::assertSame($laterUsed - $earlierUsed, $row['usage_delta']);
                self::assertSame($laterLimit - $earlierLimit, $row['limit_delta']);
                self::assertSame($earlierProjection->remainingBefore(), $row['earlier_remaining']);
                self::assertSame($laterProjection->remainingBefore(), $row['later_remaining']);
                self::assertSame(
                    $laterProjection->remainingBefore() - $earlierProjection->remainingBefore(),
                    $row['remaining_delta'],
                );
                self::assertSame($earlierProjection->existingExcess(), $row['earlier_excess']);
                self::assertSame($laterProjection->existingExcess(), $row['later_excess']);
                self::assertSame(
                    $laterProjection->existingExcess() - $earlierProjection->existingExcess(),
                    $row['excess_delta'],
                );
                self::assertSame($earlier->cards()[0]['band'], $row['earlier_band']);
                self::assertSame($later->cards()[0]['band'], $row['later_band']);
                self::assertSame(
                    $row['earlier_band'] !== $row['later_band'],
                    $row['band_changed'],
                );
                self::assertSame(
                    (int) ($earlierLimit !== $laterLimit),
                    $output['limits_changed'],
                );
                self::assertSame(
                    (int) $row['band_changed'],
                    $output['bands_changed'],
                );

                $trend = match (true) {
                    $laterUsed > $earlierUsed => 'increased',
                    $laterUsed < $earlierUsed => 'decreased',
                    default => 'unchanged',
                };
                self::assertSame($trend, $row['usage_trend']);
                self::assertSame(1, $output['usage_increased'] + $output['usage_decreased'] + $output['usage_unchanged']);

                $impact = (new SyntheticQuotaImpactPreview([$laterProjection]))->preview();
                $mappedImpact = match (true) {
                    $laterUsed > $laterLimit => 'already_over',
                    $laterLimit === 0 => 'zero_capacity',
                    $laterUsed === $laterLimit => 'at_limit',
                    default => 'in_range',
                };
                self::assertSame($mappedImpact, $impact['rows'][0]['impact']);
                self::assertSame(1, $impact['impact_counts'][$mappedImpact]);
                self::assertSame($row['later_remaining'], $impact['rows'][0]['remaining_before']);
                self::assertSame($row['later_excess'], $impact['rows'][0]['excess_before']);

                self::assertTrue($output['informational_only']);
                self::assertFalse($output['authorized']);
                self::assertFalse($output['can_charge']);
                self::assertFalse($output['can_enforce']);
                self::assertFalse($output['can_export']);
                self::assertFalse($impact['authorized']);
                self::assertFalse($impact['can_reserve']);
                self::assertFalse($impact['can_increase']);
                self::assertFalse($impact['can_charge']);
            }
        }
    }

    public function test_meter_alignment_does_not_claim_same_tenant_or_a_valid_billing_period(): void
    {
        $before = new QuotaSnapshotReadout([
            new QuotaUsageProjection('leads', 20, 4, 0),
            new QuotaUsageProjection('users', 2, 1, 0),
        ]);
        $after = new QuotaSnapshotReadout([
            new QuotaUsageProjection('users', 2, 1, 0),
            new QuotaUsageProjection('leads', 20, 4, 0),
        ]);
        $comparison = new SyntheticQuotaSnapshotComparison($before, $after);
        $output = $comparison->compare();

        self::assertSame(['leads','users'], array_column($output['rows'], 'meter'));
        self::assertSame(2, $output['usage_unchanged']);
        self::assertSame(0, $output['bands_changed']);
        self::assertSame(0, $output['limits_changed']);
        self::assertSame(
            [
                'rows', 'meters', 'usage_increased', 'usage_decreased',
                'usage_unchanged', 'limits_changed', 'bands_changed',
                'informational_only', 'authorized', 'can_charge', 'can_enforce', 'can_export',
            ],
            array_keys($output),
        );
        foreach (['authorized','can_charge','can_enforce','can_export'] as $capability) {
            self::assertFalse($output[$capability]);
        }
    }
}
