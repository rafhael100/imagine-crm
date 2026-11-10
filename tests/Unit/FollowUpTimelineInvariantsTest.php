<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpSnapshot;
use App\Support\Synthetic\FollowUpTimeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpTimelineInvariantsTest extends TestCase
{
    private function blank(): array
    {
        return FollowUpSnapshot::build([], '2026-11-10T00:00:00Z');
    }

    public function test_accepts_exactly_thirty_one_monotonic_observations(): void
    {
        $observations = [];
        for ($day = 1; $day <= 31; ++$day) {
            $instant = sprintf('2026-10-%02dT12:00:00Z', $day);
            $observations[] = ['at' => $instant, 'snapshot' => $this->blank()];
        }

        $result = FollowUpTimeline::build($observations);
        self::assertCount(31, $result);
        self::assertNull($result[0]['continuity']);
        self::assertSame(0, $result[30]['delta']['total']);
        self::assertSame(0, $result[30]['continuity']['moved']);
    }

    public function test_rejects_descending_instants(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpTimeline::build([
            ['at' => '2026-11-11T00:00:00Z', 'snapshot' => $this->blank()],
            ['at' => '2026-11-10T00:00:00Z', 'snapshot' => $this->blank()],
        ]);
    }

    public function test_rejects_nonlist_observations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpTimeline::build([
            'first' => ['at' => '2026-11-10T00:00:00Z', 'snapshot' => $this->blank()],
        ]);
    }

    public function test_rejects_missing_timezone_offset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpTimeline::build([
            ['at' => '2026-11-10T00:00:00', 'snapshot' => $this->blank()],
        ]);
    }

    public function test_rejects_unknown_observation_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpTimeline::build([
            ['at' => '2026-11-10T00:00:00Z', 'snapshot' => $this->blank(), 'tenant' => 'not-authorized'],
        ]);
    }

    public function test_rejects_cross_bucket_duplicate_ids_in_single_snapshot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $snapshot = $this->blank();
        $snapshot['buckets']['overdue'][] = 'duplicate';
        $snapshot['buckets']['due_24h'][] = 'duplicate';
        FollowUpTimeline::build([
            ['at' => '2026-11-10T00:00:00Z', 'snapshot' => $snapshot],
        ]);
    }
}
