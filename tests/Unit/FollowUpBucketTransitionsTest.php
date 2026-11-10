<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpBucketTransitions;
use App\Support\Synthetic\FollowUpSnapshot;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpBucketTransitionsTest extends TestCase
{
    public function test_tracks_movements_entry_and_exit_without_inference(): void
    {
        $before = FollowUpSnapshot::build([
            ['id' => 'over', 'due_at' => '2026-11-10T09:00:00Z'],
            ['id' => 'move', 'due_at' => '2026-11-10T12:00:00Z'],
            ['id' => 'exit', 'due_at' => '2026-11-12T10:00:00Z'],
        ], '2026-11-10T10:00:00Z');
        $after = FollowUpSnapshot::build([
            ['id' => 'over', 'due_at' => '2026-11-10T09:00:00Z'],
            ['id' => 'move', 'due_at' => '2026-11-10T12:00:00Z'],
            ['id' => 'new', 'due_at' => '2026-11-12T10:00:00Z'],
        ], '2026-11-11T10:00:00Z');

        self::assertSame([
            'transitions' => [
                'overdue' => ['overdue' => 1, 'due_24h' => 0, 'due_later' => 0],
                'due_24h' => ['overdue' => 1, 'due_24h' => 0, 'due_later' => 0],
                'due_later' => ['overdue' => 0, 'due_24h' => 0, 'due_later' => 0],
            ],
            'entered' => ['new'],
            'exited' => ['exit'],
            'unchanged' => 1,
            'moved' => 1,
        ], FollowUpBucketTransitions::compare($before, $after));
    }

    public function test_empty_snapshots_are_stable(): void
    {
        $empty = FollowUpSnapshot::build([], '2026-11-10T10:00:00Z');
        $result = FollowUpBucketTransitions::compare($empty, $empty);
        self::assertSame([], $result['entered']);
        self::assertSame([], $result['exited']);
        self::assertSame(0, $result['unchanged']);
        self::assertSame(0, $result['moved']);
    }

    public function test_rejects_count_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $snapshot = FollowUpSnapshot::build([], '2026-11-10T10:00:00Z');
        $snapshot['counts']['total'] = 1;
        FollowUpBucketTransitions::compare($snapshot, $snapshot);
    }

    public function test_rejects_duplicate_identifier_across_buckets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $snapshot = FollowUpSnapshot::build([], '2026-11-10T10:00:00Z');
        $snapshot['buckets']['overdue'] = ['duplicate'];
        $snapshot['buckets']['due_24h'] = ['duplicate'];
        FollowUpBucketTransitions::compare($snapshot, $snapshot);
    }

    public function test_rejects_unexpected_snapshot_field(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $snapshot = FollowUpSnapshot::build([], '2026-11-10T10:00:00Z');
        $snapshot['tenant_id'] = 'untrusted';
        FollowUpBucketTransitions::compare($snapshot, $snapshot);
    }
}
