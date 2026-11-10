<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpSnapshot;
use App\Support\Synthetic\FollowUpTimeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpTimelineTest extends TestCase
{
    private function snapshot(string $clock, array $records = []): array
    {
        return FollowUpSnapshot::build($records, $clock);
    }

    public function test_builds_consistent_timeline_without_identifiers(): void
    {
        $series = FollowUpTimeline::build([
            ['at' => '2026-11-10T10:00:00Z', 'snapshot' => $this->snapshot('2026-11-10T10:00:00Z', [
                ['id' => 'private-a', 'due_at' => '2026-11-10T11:00:00Z'],
            ])],
            ['at' => '2026-11-11T07:00:00-03:00', 'snapshot' => $this->snapshot('2026-11-11T10:00:00Z', [
                ['id' => 'private-a', 'due_at' => '2026-11-10T11:00:00Z'],
                ['id' => 'private-b', 'due_at' => '2026-11-13T10:00:00Z'],
            ])],
        ]);

        self::assertCount(2, $series);
        self::assertSame('2026-11-11T10:00:00.000000Z', $series[1]['at']);
        self::assertNull($series[0]['delta']);
        self::assertSame(1, $series[1]['delta']['total']);
        self::assertSame(['entered' => 1, 'exited' => 0, 'unchanged' => 0, 'moved' => 1], $series[1]['continuity']);
        self::assertStringNotContainsString('private-', json_encode($series, JSON_THROW_ON_ERROR));
    }

    public function test_accepts_empty_list(): void
    {
        self::assertSame([], FollowUpTimeline::build([]));
    }

    public function test_rejects_submicrosecond_observation_instead_of_rounding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpTimeline::build([
            ['at' => '2026-11-10T10:00:00.0000001Z', 'snapshot' => $this->snapshot('2026-11-10T10:00:00Z')],
        ]);
    }

    public function test_rejects_same_instant_in_other_offset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $empty = $this->snapshot('2026-11-10T10:00:00Z');
        FollowUpTimeline::build([
            ['at' => '2026-11-10T10:00:00Z', 'snapshot' => $empty],
            ['at' => '2026-11-10T07:00:00-03:00', 'snapshot' => $empty],
        ]);
    }

    public function test_rejects_invalid_calendar_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpTimeline::build([
            ['at' => '2026-02-30T10:00:00Z', 'snapshot' => $this->snapshot('2026-11-10T10:00:00Z')],
        ]);
    }

    public function test_rejects_tampered_snapshot_counts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $snapshot = $this->snapshot('2026-11-10T10:00:00Z');
        $snapshot['counts']['total'] = 2;
        FollowUpTimeline::build([['at' => '2026-11-10T10:00:00Z', 'snapshot' => $snapshot]]);
    }

    public function test_rejects_more_than_thirty_one_observations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $empty = $this->snapshot('2026-11-10T10:00:00Z');
        FollowUpTimeline::build(array_fill(0, 32, [
            'at' => '2026-11-10T10:00:00Z', 'snapshot' => $empty,
        ]));
    }
}
