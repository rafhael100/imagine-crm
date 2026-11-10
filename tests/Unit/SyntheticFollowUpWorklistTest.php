<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpWorklist;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticFollowUpWorklistTest extends TestCase
{
    private const NOW = '2026-11-09T22:00:00Z';
    private const FIXTURES = [
        ['due_at' => '2026-11-09T21:00:00Z', 'completed' => false],
        ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
        ['due_at' => self::NOW, 'completed' => false],
        ['due_at' => '2026-11-09T21:00:00Z', 'completed' => true],
        ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
    ];

    public function test_all_five_views_preserve_dates_and_prevent_authorization(): void
    {
        $worklist = new SyntheticFollowUpWorklist();
        foreach ([
            'open' => [0, 2, 1, 4],
            'overdue' => [0],
            'due_now' => [2],
            'upcoming' => [1, 4],
            'completed' => [3],
        ] as $view => $expected) {
            $result = $worklist->page(self::FIXTURES, self::NOW, $view, 0, 20);
            self::assertSame($expected, $result['positions']);
            self::assertSame($view, $result['view']);
            self::assertSame(5, $result['total']);
            self::assertSame(count($expected), $result['matched']);
            self::assertSame(1, $result['page_count']);
            self::assertFalse($result['has_previous']);
            self::assertFalse($result['has_next']);
            self::assertFalse($result['empty']);
            self::assertFalse($result['authorized']);
            self::assertFalse($result['can_notify']);
            self::assertSame(
                ['positions','view','total','matched','page','page_size','page_count','has_previous','has_next','empty','authorized','can_notify'],
                array_keys($result),
            );
        }
    }

    public function test_paginated_open_and_completed_ties_remain_stable(): void
    {
        $worklist = new SyntheticFollowUpWorklist();
        self::assertSame([0, 2], $worklist->page(self::FIXTURES, self::NOW, 'open', 0, 2)['positions']);
        self::assertSame([1, 4], $worklist->page(self::FIXTURES, self::NOW, 'open', 1, 2)['positions']);
        self::assertTrue($worklist->page(self::FIXTURES, self::NOW, 'open', 0, 2)['has_next']);
        self::assertFalse($worklist->page(self::FIXTURES, self::NOW, 'open', 1, 2)['has_next']);

        $same = [
            ['due_at' => '2026-11-12T00:00:00Z', 'completed' => true],
            ['due_at' => '2026-11-11T00:00:00Z', 'completed' => true],
            ['due_at' => '2026-11-12T00:00:00Z', 'completed' => true],
        ];
        self::assertSame([1, 0, 2], $worklist->page($same, self::NOW, 'completed', 0, 20)['positions']);
    }

    public function test_all_views_and_sizes_reassemble_filtered_order_exactly(): void
    {
        $worklist = new SyntheticFollowUpWorklist();
        foreach (['open','overdue','due_now','upcoming','completed'] as $view) {
            $full = $worklist->page(self::FIXTURES, self::NOW, $view, 0, 20);
            foreach ([1,2,3,4,20] as $pageSize) {
                $first = $worklist->page(self::FIXTURES, self::NOW, $view, 0, $pageSize);
                $all = [];
                for ($page = 0; $page < max(1, $first['page_count']); $page++) {
                    $part = $worklist->page(self::FIXTURES, self::NOW, $view, $page, $pageSize);
                    array_push($all, ...$part['positions']);
                    self::assertFalse($part['authorized']);
                    self::assertFalse($part['can_notify']);
                    self::assertLessThanOrEqual($pageSize, count($part['positions']));
                }
                self::assertSame($full['positions'], $all);
                self::assertSame($full['matched'], $first['matched']);
            }
        }
    }

    public function test_empty_views_and_empty_fixture_stay_empty_without_cursor(): void
    {
        $worklist = new SyntheticFollowUpWorklist();
        foreach (['open','overdue','due_now','upcoming','completed'] as $view) {
            $result = $worklist->page([], self::NOW, $view);
            self::assertSame([], $result['positions']);
            self::assertSame(0, $result['matched']);
            self::assertSame(0, $result['page_count']);
            self::assertTrue($result['empty']);
            self::assertFalse($result['has_next']);
        }
        $result = $worklist->page(
            [['due_at' => '2026-11-10T00:00:00Z', 'completed' => false]],
            self::NOW,
            'overdue',
        );
        self::assertSame(0, $result['matched']);
        self::assertSame(1, $result['total']);
        self::assertSame([], $result['positions']);
    }

    public function test_invalid_filters_pages_and_hidden_rows_fail_closed(): void
    {
        $worklist = new SyntheticFollowUpWorklist();
        $invalid = [
            [self::FIXTURES, self::NOW, 'all', 0, 4],
            [self::FIXTURES, self::NOW, 'OPEN', 0, 4],
            [self::FIXTURES, self::NOW, 'open', -1, 4],
            [self::FIXTURES, self::NOW, 'open', 20, 4],
            [self::FIXTURES, self::NOW, 'open', 1, 20],
            [self::FIXTURES, self::NOW, 'overdue', 1, 1],
            [self::FIXTURES, self::NOW, 'open', 0, 0],
            [self::FIXTURES, self::NOW, 'open', 0, 21],
            [[['due_at' => self::NOW, 'completed' => true, 'tenant_id' => 'forged']], self::NOW, 'open', 0, 4],
            [[['due_at' => self::NOW, 'completed' => false, 'actor' => 'forged']], self::NOW, 'completed', 0, 4],
            [[['due_at' => '2026-02-30T22:00:00Z', 'completed' => true]], self::NOW, 'open', 0, 4],
            [[['due_at' => self::NOW, 'completed' => 'false']], self::NOW, 'open', 0, 4],
            [array_fill(0, 21, ['due_at' => self::NOW, 'completed' => false]), self::NOW, 'open', 0, 4],
            [self::FIXTURES, '2026-11-09 22:00:00', 'open', 0, 4],
        ];
        foreach ($invalid as $params) {
            try {
                $worklist->page(...$params);
                self::fail('Untrusted fixture/view/page unexpectedly accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
                self::assertStringNotContainsString('forged', $e->getMessage());
            }
        }
    }
}
