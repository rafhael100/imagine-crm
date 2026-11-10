<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpAggregateReport;
use App\Support\Synthetic\FollowUpSnapshot;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpAggregateReportTest extends TestCase
{
    public function test_reports_counts_only_and_reconciles_transitions(): void
    {
        $previous = FollowUpSnapshot::build([
            ['id' => 'secret-a', 'due_at' => '2026-11-10T09:00:00Z'],
            ['id' => 'secret-b', 'due_at' => '2026-11-10T11:00:00Z'],
            ['id' => 'secret-c', 'due_at' => '2026-11-12T11:00:00Z'],
        ], '2026-11-10T10:00:00Z');
        $current = FollowUpSnapshot::build([
            ['id' => 'secret-a', 'due_at' => '2026-11-10T09:00:00Z'],
            ['id' => 'secret-b', 'due_at' => '2026-11-10T11:00:00Z'],
            ['id' => 'secret-d', 'due_at' => '2026-11-12T11:00:00Z'],
        ], '2026-11-11T10:00:00Z');

        $report = FollowUpAggregateReport::build($previous, $current);

        self::assertSame([
            'previous' => ['overdue' => 1, 'due_24h' => 1, 'due_later' => 1, 'total' => 3],
            'current' => ['overdue' => 2, 'due_24h' => 0, 'due_later' => 1, 'total' => 3],
            'delta' => ['overdue' => 1, 'due_24h' => -1, 'due_later' => 0, 'total' => 0],
            'continuity' => ['entered' => 1, 'exited' => 1, 'unchanged' => 1, 'moved' => 1],
        ], $report);
        $encoded = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['secret-a', 'secret-b', 'secret-c', 'secret-d'] as $id) {
            self::assertStringNotContainsString($id, $encoded);
        }
    }

    public function test_empty_snapshots_return_only_zero_counts(): void
    {
        $empty = FollowUpSnapshot::build([], '2026-11-10T10:00:00Z');
        $report = FollowUpAggregateReport::build($empty, $empty);
        self::assertSame(0, $report['previous']['total']);
        self::assertSame(0, $report['current']['total']);
        self::assertSame(0, $report['continuity']['moved']);
    }

    public function test_rejects_falsified_snapshot_count(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $snapshot = FollowUpSnapshot::build([], '2026-11-10T10:00:00Z');
        $snapshot['counts']['total'] = 99;
        FollowUpAggregateReport::build($snapshot, $snapshot);
    }

    public function test_rejects_identifier_repeated_between_groups(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $snapshot = FollowUpSnapshot::build([], '2026-11-10T10:00:00Z');
        $snapshot['buckets']['overdue'][] = 'dup';
        $snapshot['buckets']['due_later'][] = 'dup';
        FollowUpAggregateReport::build($snapshot, $snapshot);
    }
}
