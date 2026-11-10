<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpObservationSeries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Negative fixture evidence only: caller separation is assumed, not proven.
 * This test does NOT claim to authenticate/authorize tenants.
 */
final class FollowUpCrossTenantFixtureTest extends TestCase
{
    private const FIRST = '2026-11-10T10:00:00Z';
    private const SECOND = '2026-11-11T10:00:00Z';

    /**
     * @param list<array{id:string,due_at:string}> $first
     * @param list<array{id:string,due_at:string}> $second
     */
    private static function observations(array $first, array $second): array
    {
        return [
            ['at' => self::FIRST, 'items' => $first],
            ['at' => self::SECOND, 'items' => $second],
        ];
    }

    public function test_independent_synthetic_company_samples_can_reuse_identical_local_ids(): void
    {
        // Two fictitious companies happen to use the same local follow-up ID.
        // Each sample is evaluated separately; this is NOT a tenant resolver.
        $companyA = self::observations(
            [
                ['id' => 'local-001', 'due_at' => '2026-11-10T09:00:00Z'],
                ['id' => 'local-002', 'due_at' => '2026-11-12T12:00:00Z'],
            ],
            [
                ['id' => 'local-001', 'due_at' => '2026-11-10T09:00:00Z'],
                ['id' => 'local-002', 'due_at' => '2026-11-12T12:00:00Z'],
            ],
        );
        $companyB = self::observations(
            [['id' => 'local-001', 'due_at' => '2026-11-11T11:00:00Z']],
            [
                ['id' => 'local-001', 'due_at' => '2026-11-11T11:00:00Z'],
                ['id' => 'local-003', 'due_at' => '2026-11-13T12:00:00Z'],
            ],
        );

        $a = FollowUpObservationSeries::project($companyA);
        $b = FollowUpObservationSeries::project($companyB);

        self::assertSame(['overdue' => 1, 'due_24h' => 0, 'due_later' => 1, 'total' => 2], $a[0]['counts']);
        self::assertSame(['overdue' => 1, 'due_24h' => 1, 'due_later' => 0, 'total' => 2], $a[1]['counts']);
        self::assertSame(2, $a[1]['continuity']['moved'] + $a[1]['continuity']['unchanged']);

        self::assertSame(['overdue' => 0, 'due_24h' => 0, 'due_later' => 1, 'total' => 1], $b[0]['counts']);
        self::assertSame(['overdue' => 0, 'due_24h' => 1, 'due_later' => 1, 'total' => 2], $b[1]['counts']);
        self::assertSame(1, $b[1]['continuity']['entered']);
        self::assertSame(1, $b[1]['continuity']['moved']);

        self::assertSame($a, FollowUpObservationSeries::project($companyA), 'Other fixture processing cannot change input A.');
        $serialized = json_encode([$a, $b], JSON_THROW_ON_ERROR);
        foreach (['local-001', 'local-002', 'local-003'] as $id) {
            self::assertStringNotContainsString($id, $serialized);
        }
    }

    public function test_mixing_company_samples_with_colliding_local_id_fails_closed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FollowUpObservationSeries::project([[
            'at' => self::FIRST,
            'items' => [
                ['id' => 'local-001', 'due_at' => '2026-11-10T09:00:00Z'],
                ['id' => 'local-001', 'due_at' => '2026-11-12T09:00:00Z'],
            ],
        ]]);
    }

    public function test_input_claiming_tenant_at_record_level_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([[
            'at' => self::FIRST,
            'items' => [
                ['id' => 'local-001', 'due_at' => '2026-11-10T09:00:00Z', 'tenant_id' => 'forged-tenant'],
            ],
        ]]);
    }

    public function test_input_claiming_tenant_at_observation_level_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([[
            'at' => self::FIRST,
            'items' => [],
            'tenant_id' => 'forged-tenant',
        ]]);
    }

    public function test_serialized_aggregate_has_no_tenant_or_identity_fields(): void
    {
        $series = FollowUpObservationSeries::project(self::observations(
            [['id' => 'private-a', 'due_at' => '2026-11-10T09:00:00Z']],
            [['id' => 'private-a', 'due_at' => '2026-11-10T09:00:00Z']],
        ));

        self::assertSame(['at', 'counts', 'delta', 'continuity'], array_keys($series[0]));
        foreach ($series as $entry) {
            self::assertSame(['overdue', 'due_24h', 'due_later', 'total'], array_keys($entry['counts']));
        }

        $json = json_encode($series, JSON_THROW_ON_ERROR);
        foreach (['tenant_id', 'organization_id', 'membership', 'principal', 'private-a'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
    }
}
