<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaSnapshotBand;
use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticTrialQuotaOverview;
use App\Core\Plans\TrialWindowAdvisory;
use PHPUnit\Framework\TestCase;

final class SyntheticTrialQuotaOverviewTest extends TestCase
{
    private const START = 1000000;
    private const WINDOW = 14 * 86400;

    private function snapshot(): QuotaSnapshotReadout
    {
        return new QuotaSnapshotReadout([
            new QuotaUsageProjection('leads', 20, 4, 0),
            new QuotaUsageProjection('users', 2, 2, 0),
            new QuotaUsageProjection('automations', 2, 3, 0),
            new QuotaUsageProjection('attachments_bytes', 0, 0, 0),
        ]);
    }

    public function test_combined_trial_and_quota_readout_is_stable_in_order_and_non_authorizing(): void
    {
        $output = (new SyntheticTrialQuotaOverview(
            new TrialWindowAdvisory(self::START, self::START + 13 * 86400),
            $this->snapshot(),
        ))->readout();

        self::assertSame([
            'phase' => 'within_window',
            'ends_at_epoch' => self::START + self::WINDOW,
            'remaining_seconds' => 86400,
            'remaining_days_rounded_up' => 1,
        ], $output['trial']);
        self::assertSame([
            ['meter' => 'leads', 'limit' => 20, 'used' => 4, 'remaining' => 16, 'excess' => 0, 'band' => QuotaSnapshotBand::InRange],
            ['meter' => 'users', 'limit' => 2, 'used' => 2, 'remaining' => 0, 'excess' => 0, 'band' => QuotaSnapshotBand::AtLimit],
            ['meter' => 'automations', 'limit' => 2, 'used' => 3, 'remaining' => 0, 'excess' => 1, 'band' => QuotaSnapshotBand::OverLimit],
            ['meter' => 'attachments_bytes', 'limit' => 0, 'used' => 0, 'remaining' => 0, 'excess' => 0, 'band' => QuotaSnapshotBand::ZeroCapacity],
        ], $output['quotas']);
        self::assertSame([
            'in_range' => 1, 'at_limit' => 1,
            'over_limit' => 1, 'zero_capacity' => 1,
        ], $output['quota_band_counts']);
        self::assertSame(count($output['quotas']), array_sum($output['quota_band_counts']));
        self::assertSame([
            'trial', 'quotas', 'quota_band_counts',
            'informational_only', 'authorized', 'can_activate', 'can_charge', 'can_enforce',
        ], array_keys($output));
        self::assertTrue($output['informational_only']);
        foreach (['authorized', 'can_activate', 'can_charge', 'can_enforce'] as $ability) {
            self::assertFalse($output[$ability]);
        }
        self::assertSame($output, (new SyntheticTrialQuotaOverview(
            new TrialWindowAdvisory(self::START, self::START + 13 * 86400),
            $this->snapshot(),
        ))->readout());
    }

    public function test_trial_time_edges_never_change_quota_independence_or_permissions(): void
    {
        foreach ([
            [self::START - 1, 'before_start', 14],
            [self::START, 'within_window', 14],
            [self::START + 1, 'within_window', 14],
            [self::START + self::WINDOW - 1, 'within_window', 1],
            [self::START + self::WINDOW, 'after_window', 0],
            [self::START + self::WINDOW + 1, 'after_window', 0],
        ] as [$now, $phase, $days]) {
            $overview = new SyntheticTrialQuotaOverview(
                new TrialWindowAdvisory(self::START, $now),
                $this->snapshot(),
            );
            $output = $overview->readout();
            self::assertSame($phase, $output['trial']['phase']);
            self::assertSame($days, $output['trial']['remaining_days_rounded_up']);
            self::assertSame(4, array_sum($output['quota_band_counts']));
            self::assertFalse($output['authorized']);
            self::assertFalse($output['can_activate']);
            self::assertFalse($output['can_charge']);
            self::assertFalse($output['can_enforce']);
        }
    }

    public function test_zero_capacity_and_overflow_risk_remain_descriptive_and_bounded(): void
    {
        $readout = new QuotaSnapshotReadout([
            new QuotaUsageProjection('disabled_meter', 0, 0, 0),
            new QuotaUsageProjection('over_meter', 0, PHP_INT_MAX, 0),
            new QuotaUsageProjection('upper_meter', PHP_INT_MAX, PHP_INT_MAX, 0),
        ]);
        $output = (new SyntheticTrialQuotaOverview(
            new TrialWindowAdvisory(PHP_INT_MAX - self::WINDOW, PHP_INT_MAX),
            $readout,
        ))->readout();

        self::assertSame('after_window', $output['trial']['phase']);
        self::assertSame(0, $output['trial']['remaining_seconds']);
        self::assertSame(['in_range' => 0, 'at_limit' => 1, 'over_limit' => 1, 'zero_capacity' => 1], $output['quota_band_counts']);
        self::assertSame(PHP_INT_MAX, $output['quotas'][1]['excess']);
        self::assertSame(0, $output['quotas'][0]['remaining']);
        self::assertFalse($output['can_enforce']);
        self::assertFalse($output['can_charge']);
    }

    public function test_lab_has_no_entitlement_billing_storage_or_notification_methods(): void
    {
        $obj = new SyntheticTrialQuotaOverview(
            new TrialWindowAdvisory(self::START, self::START),
            $this->snapshot(),
        );
        self::assertSame(['readout'], array_values(array_filter(
            get_class_methods($obj),
            static fn (string $name): bool => $name !== '__construct',
        )));
        foreach (['allow', 'authorize', 'canAccess', 'isEligible', 'canCreate',
            'extendTrial', 'charge', 'bill', 'suspend', 'notify', 'invite',
            'reserve', 'persist', 'activate', 'grant', 'delete'] as $forbidden) {
            self::assertFalse(method_exists($obj, $forbidden));
        }
        self::assertTrue((new \ReflectionClass(SyntheticTrialQuotaOverview::class))->isReadOnly());
    }
}
