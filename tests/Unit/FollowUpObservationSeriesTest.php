<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpObservationSeries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpObservationSeriesTest extends TestCase
{
    public function test_projects_records_into_a_count_only_timeline(): void
    {
        $series = FollowUpObservationSeries::project([
            [
                'at' => '2026-11-10T10:00:00Z',
                'items' => [
                    ['id' => 'private-a', 'due_at' => '2026-11-10T11:00:00Z'],
                ],
            ],
            [
                'at' => '2026-11-11T07:00:00-03:00',
                'items' => [
                    ['id' => 'private-a', 'due_at' => '2026-11-10T11:00:00Z'],
                    ['id' => 'private-b', 'due_at' => '2026-11-13T11:00:00Z'],
                ],
            ],
        ]);

        self::assertSame([
            [
                'at' => '2026-11-10T10:00:00.000000Z',
                'counts' => ['overdue' => 0, 'due_24h' => 1, 'due_later' => 0, 'total' => 1],
                'delta' => null,
                'continuity' => null,
            ],
            [
                'at' => '2026-11-11T10:00:00.000000Z',
                'counts' => ['overdue' => 1, 'due_24h' => 0, 'due_later' => 1, 'total' => 2],
                'delta' => ['overdue' => 1, 'due_24h' => -1, 'due_later' => 1, 'total' => 1],
                'continuity' => ['entered' => 1, 'exited' => 0, 'unchanged' => 0, 'moved' => 1],
            ],
        ], $series);

        $serialized = json_encode($series, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('private-a', $serialized);
        self::assertStringNotContainsString('private-b', $serialized);
    }

    public function test_empty_series_is_allowed(): void
    {
        self::assertSame([], FollowUpObservationSeries::project([]));
    }

    public function test_rejects_duplicate_ids_within_an_observation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([[
            'at' => '2026-11-10T10:00:00Z',
            'items' => [
                ['id' => 'duplicate', 'due_at' => '2026-11-10T09:00:00Z'],
                ['id' => 'duplicate', 'due_at' => '2026-11-10T11:00:00Z'],
            ],
        ]]);
    }

    public function test_rejects_same_instant_in_different_timezones(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([
            ['at' => '2026-11-10T10:00:00Z', 'items' => []],
            ['at' => '2026-11-10T07:00:00-03:00', 'items' => []],
        ]);
    }

    public function test_rejects_unknown_fields_without_implicit_authority(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([[
            'at' => '2026-11-10T10:00:00Z',
            'items' => [],
            'tenant_id' => 'fake',
        ]]);
    }

    public function test_rejects_oversized_observations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([[
            'at' => '2026-11-10T10:00:00Z',
            'items' => array_fill(0, 101, ['id' => 'not-checked', 'due_at' => '2026-11-10T10:00:00Z']),
        ]]);
    }

    public function test_rejects_more_than_thirty_one_snapshots(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project(array_fill(0, 32, [
            'at' => '2026-11-10T10:00:00Z',
            'items' => [],
        ]));
    }

    public function test_rejects_implicit_timezone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([[
            'at' => '2026-11-10T10:00:00',
            'items' => [],
        ]]);
    }

    public function test_rejects_associative_item_collection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpObservationSeries::project([[
            'at' => '2026-11-10T10:00:00Z',
            'items' => ['fictitious' => ['id' => 'fake', 'due_at' => '2026-11-10T10:00:00Z']],
        ]]);
    }
}
