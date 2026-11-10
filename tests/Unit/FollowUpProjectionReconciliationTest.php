<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpAggregateReport;
use App\Support\Synthetic\FollowUpBucketTransitions;
use App\Support\Synthetic\FollowUpSnapshot;
use PHPUnit\Framework\TestCase;

final class FollowUpProjectionReconciliationTest extends TestCase
{
    /**
     * Exhausts all nine origin/destination bucket combinations, plus
     * an entered record and an exited record. All records are synthetic.
     */
    public function test_transition_matrix_and_report_reconcile_all_bucket_pairs(): void
    {
        $previous = [
            'overdue' => [],
            'due_24h' => [],
            'due_later' => [],
        ];
        $current = [
            'overdue' => [],
            'due_24h' => [],
            'due_later' => [],
        ];

        foreach (array_keys($previous) as $from) {
            foreach (array_keys($current) as $to) {
                $id = 'synthetic-'.$from.'-'.$to;
                $previous[$from][] = $id;
                $current[$to][] = $id;
            }
        }

        $previous['overdue'][] = 'synthetic-exited';
        $current['due_later'][] = 'synthetic-entered';

        $oldSnapshot = [
            'buckets' => $previous,
            'counts' => ['overdue' => 4, 'due_24h' => 3, 'due_later' => 3, 'total' => 10],
        ];
        $newSnapshot = [
            'buckets' => $current,
            'counts' => ['overdue' => 3, 'due_24h' => 3, 'due_later' => 4, 'total' => 10],
        ];

        $transitions = FollowUpBucketTransitions::compare($oldSnapshot, $newSnapshot);
        foreach ($transitions['transitions'] as $row) {
            self::assertSame([1, 1, 1], array_values($row));
        }

        self::assertSame(3, $transitions['unchanged']);
        self::assertSame(6, $transitions['moved']);
        self::assertSame(['synthetic-entered'], $transitions['entered']);
        self::assertSame(['synthetic-exited'], $transitions['exited']);

        $report = FollowUpAggregateReport::build($oldSnapshot, $newSnapshot);
        self::assertSame([
            'entered' => 1, 'exited' => 1, 'unchanged' => 3, 'moved' => 6,
        ], $report['continuity']);
        self::assertSame(0, $report['delta']['total']);
        self::assertSame(10, $report['current']['total']);
        self::assertSame(10, $report['previous']['total']);
        self::assertStringNotContainsString('synthetic-', json_encode($report, JSON_THROW_ON_ERROR));
    }

    public function test_permutation_of_input_order_does_not_change_aggregate_counts(): void
    {
        $items = [
            ['id' => 'alpha', 'due_at' => '2026-11-10T09:00:00Z'],
            ['id' => 'beta', 'due_at' => '2026-11-10T12:00:00Z'],
            ['id' => 'gamma', 'due_at' => '2026-11-12T10:00:00Z'],
        ];

        $clock = '2026-11-10T10:00:00Z';
        $first = FollowUpSnapshot::build($items, $clock);
        $reversed = FollowUpSnapshot::build(array_reverse($items), $clock);

        self::assertSame($first['counts'], $reversed['counts']);
        $report = FollowUpAggregateReport::build($first, $reversed);
        self::assertSame(3, $report['continuity']['unchanged']);
        self::assertSame(0, $report['continuity']['moved']);
        self::assertSame(0, $report['continuity']['entered']);
        self::assertSame(0, $report['continuity']['exited']);
    }
}
