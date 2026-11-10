<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpAgingProjection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpAgingProjectionTest extends TestCase
{
    public function test_classifies_boundaries_and_preserves_input_order(): void
    {
        $result = FollowUpAgingProjection::project([
            ['id' => 'past', 'due_at' => '2026-11-10T09:59:59Z'],
            ['id' => 'now', 'due_at' => '2026-11-10T10:00:00Z'],
            ['id' => 'tomorrow', 'due_at' => '2026-11-11T10:00:00Z'],
            ['id' => 'later', 'due_at' => '2026-11-11T10:00:01Z'],
        ], '2026-11-10T10:00:00+00:00');

        self::assertSame([
            'overdue' => ['past'],
            'due_24h' => ['now', 'tomorrow'],
            'due_later' => ['later'],
        ], $result);
    }

    public function test_equivalent_offsets_produce_same_result(): void
    {
        self::assertSame([
            'overdue' => [],
            'due_24h' => ['example'],
            'due_later' => [],
        ], FollowUpAgingProjection::project([
            ['id' => 'example', 'due_at' => '2026-11-10T08:00:00-03:00'],
        ], '2026-11-10T11:00:00Z'));
    }

    public function test_rejects_implicit_timezone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project([], '2026-11-10T10:00:00');
    }

    public function test_rejects_invalid_calendar_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project([], '2026-02-30T10:00:00Z');
    }

    public function test_rejects_duplicate_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project([
            ['id' => 'same', 'due_at' => '2026-11-10T10:00:00Z'],
            ['id' => 'same', 'due_at' => '2026-11-10T11:00:00Z'],
        ], '2026-11-10T10:00:00Z');
    }

    public function test_rejects_missing_due_at(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project([
            ['id' => 'missing'],
        ], '2026-11-10T10:00:00Z');
    }
}
