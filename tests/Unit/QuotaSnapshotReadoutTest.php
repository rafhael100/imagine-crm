<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaSnapshotBand;
use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class QuotaSnapshotReadoutTest extends TestCase
{
    public function test_distinct_synthetic_meters_are_read_only_and_preserve_input_order(): void
    {
        $summary = new QuotaSnapshotReadout([
            new QuotaUsageProjection('leads', 20, 12, 0),
            new QuotaUsageProjection('automations', 2, 2, 0),
            new QuotaUsageProjection('attachments_bytes', 0, 0, 0),
            new QuotaUsageProjection('users', 2, 3, 0),
        ]);

        self::assertSame([
            ['meter' => 'leads', 'limit' => 20, 'used' => 12, 'remaining' => 8, 'excess' => 0, 'band' => QuotaSnapshotBand::InRange],
            ['meter' => 'automations', 'limit' => 2, 'used' => 2, 'remaining' => 0, 'excess' => 0, 'band' => QuotaSnapshotBand::AtLimit],
            ['meter' => 'attachments_bytes', 'limit' => 0, 'used' => 0, 'remaining' => 0, 'excess' => 0, 'band' => QuotaSnapshotBand::ZeroCapacity],
            ['meter' => 'users', 'limit' => 2, 'used' => 3, 'remaining' => 0, 'excess' => 1, 'band' => QuotaSnapshotBand::OverLimit],
        ], $summary->cards());

        self::assertSame($summary->cards(), $summary->cards());
        self::assertTrue((new \ReflectionClass(QuotaSnapshotReadout::class))->isReadOnly());
    }

    public function test_limit_zero_does_not_mean_unlimited_and_existing_excess_is_not_deleted(): void
    {
        $summary = new QuotaSnapshotReadout([
            new QuotaUsageProjection('leads', 0, 1, 0),
            new QuotaUsageProjection('storage_bytes', PHP_INT_MAX, PHP_INT_MAX, 0),
        ]);
        $rows = $summary->cards();

        self::assertSame(QuotaSnapshotBand::OverLimit, $rows[0]['band']);
        self::assertSame(1, $rows[0]['excess']);
        self::assertSame(QuotaSnapshotBand::AtLimit, $rows[1]['band']);
        self::assertSame(0, $rows[1]['excess']);
        self::assertSame(PHP_INT_MAX, $rows[1]['used']);
    }

    public function test_twelve_meters_are_supported_without_implicit_plan_authority(): void
    {
        $snapshots = [];
        for ($index = 0; $index < 12; $index++) {
            $snapshots[] = new QuotaUsageProjection('synthetic_'.$index, 5, 0, 0);
        }
        self::assertCount(12, (new QuotaSnapshotReadout($snapshots))->cards());
    }

    public function test_invalid_snapshot_collections_fail_closed_without_echoing_meters(): void
    {
        $cases = [
            [],
            ['named' => new QuotaUsageProjection('leads', 20, 0, 0)],
            [new QuotaUsageProjection('leads', 20, 0, 0), new QuotaUsageProjection('leads', 10, 0, 0)],
            [new QuotaUsageProjection('leads', 20, 0, 1)],
            ['raw input'],
        ];
        $tooMany = [];
        for ($index = 0; $index < 13; $index++) {
            $tooMany[] = new QuotaUsageProjection('test_'.$index, 1, 0, 0);
        }
        $cases[] = $tooMany;

        foreach ($cases as $case) {
            try {
                new QuotaSnapshotReadout($case);
                self::fail('Invalid quota readout was accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Resumo informativo de franquias invalido.', $exception->getMessage());
                self::assertStringNotContainsString('leads', $exception->getMessage());
            }
        }
    }

    public function test_display_bands_do_not_expose_permission_or_billing_actions(): void
    {
        $readout = new QuotaSnapshotReadout([new QuotaUsageProjection('leads', 20, 1, 0)]);
        foreach (['allow', 'authorize', 'canCreate', 'canUpload', 'reserve', 'commit',
            'charge', 'bill', 'notify', 'delete', 'create', 'persist', 'grant', 'enablePlan'] as $method) {
            self::assertFalse(method_exists($readout, $method), $method);
        }
        self::assertSame(['cards'], array_values(array_filter(
            get_class_methods(QuotaSnapshotReadout::class),
            static fn (string $name): bool => $name !== '__construct',
        )));
    }
}
