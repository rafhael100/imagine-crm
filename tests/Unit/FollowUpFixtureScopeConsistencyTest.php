<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpFixtureScopeConsistency;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpFixtureScopeConsistencyTest extends TestCase
{
    private const SCOPE = 'fixture-company-a';
    private const CLOCK = '2026-11-10T10:00:00Z';

    /** @return array{id:string,due_at:string,scope:string} */
    private static function record(string $id, string $dueAt, string $scope = self::SCOPE): array
    {
        return ['id' => $id, 'due_at' => $dueAt, 'scope' => $scope];
    }

    /** @return array{at:string,scope:string,items:array} */
    private static function observation(string $at, array $items = [], string $scope = self::SCOPE): array
    {
        return ['at' => $at, 'scope' => $scope, 'items' => $items];
    }

    public function test_valid_synthetic_scope_tags_produce_only_aggregates(): void
    {
        $series = FollowUpFixtureScopeConsistency::project(self::SCOPE, [
            self::observation(self::CLOCK, [
                self::record('private-01', '2026-11-10T09:00:00Z'),
                self::record('private-02', '2026-11-12T09:00:00Z'),
            ]),
            self::observation('2026-11-11T10:00:00Z', [
                self::record('private-01', '2026-11-10T09:00:00Z'),
                self::record('private-02', '2026-11-12T09:00:00Z'),
            ]),
        ]);

        self::assertSame(['overdue' => 1, 'due_24h' => 0, 'due_later' => 1, 'total' => 2], $series[0]['counts']);
        self::assertSame(['overdue' => 1, 'due_24h' => 1, 'due_later' => 0, 'total' => 2], $series[1]['counts']);
        self::assertSame(['overdue' => 0, 'due_24h' => 1, 'due_later' => -1, 'total' => 0], $series[1]['delta']);
        self::assertSame(['entered' => 0, 'exited' => 0, 'unchanged' => 1, 'moved' => 1], $series[1]['continuity']);

        $serialized = json_encode($series, JSON_THROW_ON_ERROR);
        foreach ([self::SCOPE, 'private-01', 'private-02', 'scope', 'tenant_id'] as $secret) {
            self::assertStringNotContainsString($secret, $serialized);
        }
    }

    public function test_unrelated_fixtures_with_same_local_id_remain_independent(): void
    {
        $a = FollowUpFixtureScopeConsistency::project(self::SCOPE, [
            self::observation(self::CLOCK, [self::record('id-01', '2026-11-10T09:00:00Z')]),
        ]);
        $b = FollowUpFixtureScopeConsistency::project('fixture-company-b', [
            self::observation(self::CLOCK, [
                self::record('id-01', '2026-11-12T09:00:00Z', 'fixture-company-b'),
            ], 'fixture-company-b'),
        ]);

        self::assertSame(1, $a[0]['counts']['overdue']);
        self::assertSame(0, $a[0]['counts']['due_later']);
        self::assertSame(0, $b[0]['counts']['overdue']);
        self::assertSame(1, $b[0]['counts']['due_later']);
    }

    public function test_mismatched_record_scope_is_rejected_even_with_distinct_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureScopeConsistency::project(self::SCOPE, [
            self::observation(self::CLOCK, [
                self::record('id-one', '2026-11-10T09:00:00Z'),
                self::record('id-two', '2026-11-12T09:00:00Z', 'fixture-company-b'),
            ]),
        ]);
    }

    public function test_mismatched_observation_scope_is_rejected_even_when_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureScopeConsistency::project(self::SCOPE, [
            self::observation(self::CLOCK, [], 'fixture-company-b'),
        ]);
    }

    public function test_scope_must_be_laboratory_only_not_an_operational_tenant_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureScopeConsistency::project('550e8400-e29b-41d4-a716-446655440000', []);
    }

    public function test_unexpected_contact_field_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $record = self::record('id-01', '2026-11-10T09:00:00Z');
        $record['email'] = 'fake@example.invalid';
        FollowUpFixtureScopeConsistency::project(self::SCOPE, [
            self::observation(self::CLOCK, [$record]),
        ]);
    }

    public function test_unexpected_observation_field_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $observation = self::observation(self::CLOCK);
        $observation['principal'] = 'not-verified';
        FollowUpFixtureScopeConsistency::project(self::SCOPE, [$observation]);
    }

    public function test_exact_hundred_items_are_allowed_in_synthetic_fixture(): void
    {
        $items = [];
        for ($i = 0; $i < 100; ++$i) {
            $items[] = self::record('id-'.$i, '2026-11-10T09:00:00Z');
        }
        $series = FollowUpFixtureScopeConsistency::project(self::SCOPE, [
            self::observation(self::CLOCK, $items),
        ]);
        self::assertSame(100, $series[0]['counts']['total']);
    }

    public function test_oversized_fixture_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureScopeConsistency::project(self::SCOPE, [
            self::observation(self::CLOCK, array_fill(0, 101, self::record('dup', self::CLOCK))),
        ]);
    }

    public function test_unverified_matching_tags_do_not_constitute_real_authorization(): void
    {
        // Intentionally demonstrates the limitation: an arbitrary caller
        // can assert the same fictitious label. This is NOT an ALLOW check.
        $series = FollowUpFixtureScopeConsistency::project('fixture-unverified', [
            self::observation(self::CLOCK, [
                self::record('invented', '2026-11-10T09:00:00Z', 'fixture-unverified'),
            ], 'fixture-unverified'),
        ]);

        self::assertSame(1, $series[0]['counts']['total']);
        self::assertArrayNotHasKey('authorized', $series[0]);
        self::assertArrayNotHasKey('tenant', $series[0]);
    }
}
