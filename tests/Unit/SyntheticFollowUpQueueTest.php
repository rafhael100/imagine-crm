<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpQueue;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticFollowUpQueueTest extends TestCase
{
    private const NOW = '2026-11-09T22:00:00Z';

    public function test_counts_follow_up_deadlines_and_orders_open_positions_without_authority(): void
    {
        $fixtures = [
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
            ['due_at' => self::NOW, 'completed' => false],
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => true],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
        ];

        $result = (new SyntheticFollowUpQueue())->summarize($fixtures, self::NOW);
        self::assertSame([
            'total' => 5,
            'open' => 4,
            'overdue' => 1,
            'due_now' => 1,
            'upcoming' => 2,
            'completed' => 1,
            'open_positions' => [0, 2, 1, 4],
            'authorized' => false,
            'can_notify' => false,
        ], $result);
        self::assertSame($result['open'], $result['overdue'] + $result['due_now'] + $result['upcoming']);
        self::assertSame($result['total'], $result['open'] + $result['completed']);
    }

    public function test_empty_queue_has_zero_counts_and_no_permissions(): void
    {
        self::assertSame([
            'total' => 0, 'open' => 0, 'overdue' => 0, 'due_now' => 0,
            'upcoming' => 0, 'completed' => 0, 'open_positions' => [],
            'authorized' => false, 'can_notify' => false,
        ], (new SyntheticFollowUpQueue())->summarize([], self::NOW));
    }

    public function test_completed_items_are_counted_but_never_ordered_as_pending(): void
    {
        $fixtures = array_fill(0, 20, ['due_at' => self::NOW, 'completed' => true]);
        $result = (new SyntheticFollowUpQueue())->summarize($fixtures, self::NOW);
        self::assertSame(20, $result['completed']);
        self::assertSame(0, $result['open']);
        self::assertSame([], $result['open_positions']);
        self::assertFalse($result['can_notify']);
    }

    public function test_equal_deadlines_preserve_original_position(): void
    {
        $fixtures = [
            ['due_at' => '2026-11-10T10:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-10T09:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-10T10:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-10T09:00:00Z', 'completed' => false],
        ];
        self::assertSame(
            [1, 3, 0, 2],
            (new SyntheticFollowUpQueue())->summarize($fixtures, self::NOW)['open_positions'],
        );
    }

    public function test_untrusted_or_ambiguous_deadlines_fail_closed(): void
    {
        $valid = ['due_at' => self::NOW, 'completed' => false];
        $invalid = [
            [[], '2026-11-09 22:00:00'],
            [[], '2026-11-09T22:00:00+00:00'],
            [[], '2026-02-30T22:00:00Z'],
            [[], '2026-11-09T25:00:00Z'],
            [[], "2026-11-09T22:00:00Z\n"],
            [[['due_at' => self::NOW, 'completed' => 'false']], self::NOW],
            [[['completed' => false, 'due_at' => self::NOW]], self::NOW],
            [[['due_at' => self::NOW, 'completed' => false, 'tenant_id' => 'forged']], self::NOW],
            [[['due_at' => self::NOW, 'completed' => false, 'lead_id' => 'forged']], self::NOW],
            [[['due_at' => self::NOW, 'completed' => false, 'actor' => 'forged']], self::NOW],
            [[['due_at' => '2026-02-29T22:00:00Z', 'completed' => true]], self::NOW],
            [array_fill(0, 21, $valid), self::NOW],
            [[7 => $valid], self::NOW],
        ];

        foreach ($invalid as [$fixtures, $now]) {
            try {
                (new SyntheticFollowUpQueue())->summarize($fixtures, $now);
                self::fail('Malformed or forged follow-up fixture was accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Invalid synthetic follow-up fixture.', $e->getMessage());
                self::assertStringNotContainsString('tenant_id', $e->getMessage());
            }
        }
    }

    public function test_exact_utc_boundary_and_leap_day_behave_deterministically(): void
    {
        $fixtures = [
            ['due_at' => '2028-02-29T00:00:00Z', 'completed' => false],
            ['due_at' => '2028-02-29T00:00:01Z', 'completed' => false],
        ];
        $result = (new SyntheticFollowUpQueue())->summarize($fixtures, '2028-02-29T00:00:00Z');
        self::assertSame(1, $result['due_now']);
        self::assertSame(1, $result['upcoming']);
        self::assertSame(0, $result['overdue']);
        self::assertSame([0, 1], $result['open_positions']);
    }
}
