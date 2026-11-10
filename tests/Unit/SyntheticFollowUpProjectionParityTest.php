<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpQueue;
use App\Core\Activities\SyntheticFollowUpWorklist;
use App\Core\Activities\SyntheticFollowUpUtcHorizon;
use PHPUnit\Framework\TestCase;

/**
 * QA-F4-05: cross-projection consistency of three inert UTC laboratories.
 * All inputs remain anonymous, bounded, and completely fabricated.
 */
final class SyntheticFollowUpProjectionParityTest extends TestCase
{
    private const NOW = '2026-11-09T22:00:00Z';

    public function test_cross_projection_invariants_hold_for_every_fixture_count_zero_to_twenty(): void
    {
        $palette = [
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => false],
            ['due_at' => self::NOW, 'completed' => false],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-10T00:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-12T23:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-23T00:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => true],
            ['due_at' => '2026-11-13T00:00:00Z', 'completed' => true],
        ];
        $queue = new SyntheticFollowUpQueue();
        $worklist = new SyntheticFollowUpWorklist();
        $horizon = new SyntheticFollowUpUtcHorizon();

        for ($size = 0; $size <= 20; ++$size) {
            $fixtures = [];
            for ($index = 0; $index < $size; ++$index) {
                $fixtures[] = $palette[$index % count($palette)];
            }

            $counts = $queue->summarize($fixtures, self::NOW);
            self::assertSame($size, $counts['total']);
            self::assertSame($size, $counts['open'] + $counts['completed']);
            self::assertSame(
                $counts['open'],
                $counts['overdue'] + $counts['due_now'] + $counts['upcoming'],
            );
            self::assertFalse($counts['authorized']);
            self::assertFalse($counts['can_notify']);

            $views = [];
            foreach (['open', 'overdue', 'due_now', 'upcoming', 'completed'] as $view) {
                $full = $worklist->page($fixtures, self::NOW, $view, 0, 20);
                $views[$view] = $full['positions'];
                self::assertSame($counts[$view], $full['matched']);
                self::assertSame($counts['total'], $full['total']);
                self::assertFalse($full['authorized']);
                self::assertFalse($full['can_notify']);

                foreach ([1, 2, 4, 20] as $pageSize) {
                    $first = $worklist->page($fixtures, self::NOW, $view, 0, $pageSize);
                    $assembled = [];
                    for ($page = 0; $page < max(1, $first['page_count']); ++$page) {
                        $part = $worklist->page($fixtures, self::NOW, $view, $page, $pageSize);
                        array_push($assembled, ...$part['positions']);
                        self::assertLessThanOrEqual($pageSize, count($part['positions']));
                        self::assertFalse($part['authorized']);
                        self::assertFalse($part['can_notify']);
                    }
                    self::assertSame($full['positions'], $assembled);
                }
            }

            self::assertSame($counts['open_positions'], $views['open']);
            $partition = array_merge($views['overdue'], $views['due_now'], $views['upcoming']);
            sort($partition);
            $open = $views['open'];
            sort($open);
            self::assertSame($open, $partition);

            $all = array_merge($views['open'], $views['completed']);
            sort($all);
            self::assertSame($size === 0 ? [] : range(0, $size - 1), $all);

            foreach ([1, 3, 7, 14] as $days) {
                $projection = $horizon->summarize($fixtures, self::NOW, $days);
                self::assertCount($days, $projection['daily']);
                self::assertSame($counts['total'], $projection['total']);
                self::assertSame($counts['open'], $projection['open']);
                self::assertSame($counts['overdue'], $projection['overdue']);
                self::assertSame($counts['due_now'], $projection['due_now']);
                self::assertSame($counts['completed'], $projection['completed']);
                self::assertSame($counts['upcoming'], $projection['upcoming_within_horizon'] + $projection['upcoming_outside_horizon']);
                self::assertSame($projection['upcoming_within_horizon'], array_sum($projection['daily']));
                self::assertFalse($projection['authorized']);
                self::assertFalse($projection['can_notify']);
            }
        }
    }

    public function test_js_demo_fixture_timestamps_feed_the_same_php_domain_contract(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        preg_match_all(
            "/Object\\.freeze\\(\\{dueAt:'([^']+)',completed:(true|false)\\}\\)/",
            $html,
            $matches,
            PREG_SET_ORDER,
        );
        self::assertCount(5, $matches);

        $fixtures = [];
        foreach ($matches as $match) {
            $fixtures[] = [
                'due_at' => $match[1],
                'completed' => $match[2] === 'true',
            ];
        }

        $summary = (new SyntheticFollowUpQueue())->summarize($fixtures, self::NOW);
        self::assertSame(5, $summary['total']);
        self::assertSame(1, $summary['overdue']);
        self::assertSame(1, $summary['due_now']);
        self::assertSame(2, $summary['upcoming']);
        self::assertSame(1, $summary['completed']);

        $one = (new SyntheticFollowUpUtcHorizon())->summarize($fixtures, self::NOW, 1);
        $seven = (new SyntheticFollowUpUtcHorizon())->summarize($fixtures, self::NOW, 7);
        self::assertSame(['2026-11-09' => 1], $one['daily']);
        self::assertSame(1, $one['upcoming_outside_horizon']);
        self::assertSame(2, $seven['upcoming_within_horizon']);
        self::assertSame(0, $seven['upcoming_outside_horizon']);
        self::assertSame(1, $seven['daily']['2026-11-12']);
        self::assertSame(
            [1, 4],
            (new SyntheticFollowUpWorklist())->page($fixtures, self::NOW, 'upcoming', 0, 20)['positions'],
        );
        self::assertFalse($one['authorized']);
        self::assertFalse($seven['can_notify']);
    }
}
