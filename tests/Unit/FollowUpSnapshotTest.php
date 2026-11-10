<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpSnapshot;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpSnapshotTest extends TestCase
{
    public function test_builds_consistent_buckets_and_counts(): void
    {
        self::assertSame([
            'buckets' => [
                'overdue' => ['a'],
                'due_24h' => ['b'],
                'due_later' => ['c'],
            ],
            'counts' => [
                'overdue' => 1,
                'due_24h' => 1,
                'due_later' => 1,
                'total' => 3,
            ],
        ], FollowUpSnapshot::build([
            ['id' => 'a', 'due_at' => '2026-11-10T09:59:59Z'],
            ['id' => 'b', 'due_at' => '2026-11-11T10:00:00Z'],
            ['id' => 'c', 'due_at' => '2026-11-11T10:00:01Z'],
        ], '2026-11-10T10:00:00Z'));
    }

    public function test_empty_snapshot_is_consistent(): void
    {
        self::assertSame([
            'buckets' => ['overdue' => [], 'due_24h' => [], 'due_later' => []],
            'counts' => ['overdue' => 0, 'due_24h' => 0, 'due_later' => 0, 'total' => 0],
        ], FollowUpSnapshot::build([], '2026-11-10T10:00:00Z'));
    }

    public function test_fails_closed_on_repeated_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpSnapshot::build([
            ['id' => 'same', 'due_at' => '2026-11-10T09:00:00Z'],
            ['id' => 'same', 'due_at' => '2026-11-10T11:00:00Z'],
        ], '2026-11-10T10:00:00Z');
    }

    public function test_fails_closed_on_missing_timezone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpSnapshot::build([], '2026-11-10T10:00:00');
    }

    public function test_preserves_precision_for_subsecond_due_dates(): void
    {
        self::assertSame([
            'overdue' => ['before'],
            'due_24h' => ['now'],
            'due_later' => [],
        ], FollowUpSnapshot::build([
            ['id' => 'before', 'due_at' => '2026-11-10T09:59:59.999999Z'],
            ['id' => 'now', 'due_at' => '2026-11-10T10:00:00.000000Z'],
        ], '2026-11-10T10:00:00.000000Z')['buckets']);
    }
}
