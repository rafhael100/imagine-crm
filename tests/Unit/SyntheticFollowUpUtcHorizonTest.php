<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpUtcHorizon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticFollowUpUtcHorizonTest extends TestCase
{
    private const NOW = '2026-11-09T22:00:00Z';

    public function test_one_week_horizon_counts_only_future_anonymous_fixtures(): void
    {
        $fixtures = [
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => false],
            ['due_at' => self::NOW, 'completed' => false],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-10T00:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-15T23:59:59Z', 'completed' => false],
            ['due_at' => '2026-11-16T00:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-12T09:00:00Z', 'completed' => true],
        ];
        $summary = (new SyntheticFollowUpUtcHorizon())->summarize($fixtures, self::NOW);
        self::assertSame([
            'total' => 7,
            'open' => 6,
            'overdue' => 1,
            'due_now' => 1,
            'completed' => 1,
            'horizon_days' => 7,
            'daily' => [
                '2026-11-09' => 1,
                '2026-11-10' => 1,
                '2026-11-11' => 0,
                '2026-11-12' => 0,
                '2026-11-13' => 0,
                '2026-11-14' => 0,
                '2026-11-15' => 1,
            ],
            'upcoming_within_horizon' => 3,
            'upcoming_outside_horizon' => 1,
            'authorized' => false,
            'can_notify' => false,
        ], $summary);
        self::assertSame(
            $summary['open'],
            $summary['overdue'] + $summary['due_now']
                + $summary['upcoming_within_horizon'] + $summary['upcoming_outside_horizon'],
        );
    }

    public function test_leap_day_month_and_year_edges_do_not_depend_on_locale(): void
    {
        $horizon = new SyntheticFollowUpUtcHorizon();
        $result = $horizon->summarize([
            ['due_at' => '2028-02-28T23:59:59Z', 'completed' => false],
            ['due_at' => '2028-02-29T00:00:00Z', 'completed' => false],
            ['due_at' => '2028-03-01T00:00:00Z', 'completed' => false],
        ], '2028-02-28T23:50:00Z', 2);
        self::assertSame([
            '2028-02-28' => 1, '2028-02-29' => 1,
        ], $result['daily']);
        self::assertSame(2, $result['upcoming_within_horizon']);
        self::assertSame(1, $result['upcoming_outside_horizon']);

        $newYear = $horizon->summarize([
            ['due_at' => '2027-01-01T00:00:00Z', 'completed' => false],
        ], '2026-12-31T23:59:59Z', 2);
        self::assertSame(['2026-12-31' => 0, '2027-01-01' => 1], $newYear['daily']);
        self::assertFalse($newYear['can_notify']);
    }

    public function test_empty_and_maximum_synthetic_fixture_set_remain_bounded(): void
    {
        $horizon = new SyntheticFollowUpUtcHorizon();
        $empty = $horizon->summarize([], self::NOW, 1);
        self::assertSame(0, $empty['total']);
        self::assertSame(['2026-11-09' => 0], $empty['daily']);
        self::assertSame(0, $empty['upcoming_within_horizon']);
        self::assertFalse($empty['authorized']);

        $full = $horizon->summarize(
            array_fill(0, 20, ['due_at' => '2026-11-10T12:00:00Z', 'completed' => false]),
            self::NOW,
            2,
        );
        self::assertSame(20, $full['total']);
        self::assertSame(['2026-11-09' => 0, '2026-11-10' => 20], $full['daily']);
        self::assertSame(20, $full['upcoming_within_horizon']);
        self::assertSame(0, $full['upcoming_outside_horizon']);
        self::assertFalse($full['can_notify']);
    }

    public function test_invalid_range_utc_or_hidden_untrusted_fixture_is_rejected(): void
    {
        $horizon = new SyntheticFollowUpUtcHorizon();
        $valid = ['due_at' => '2026-11-11T12:00:00Z', 'completed' => false];
        $invalid = [
            [[], self::NOW, 0],
            [[], self::NOW, 15],
            [[], '2026-11-09T22:00:00+00:00', 7],
            [[], '2026-02-30T22:00:00Z', 7],
            [[['due_at' => self::NOW, 'completed' => true, 'tenant_id' => 'forged']], self::NOW, 7],
            [[['due_at' => self::NOW, 'completed' => false, 'actor' => 'forged']], self::NOW, 7],
            [[['due_at' => '2026-02-29T22:00:00Z', 'completed' => true]], self::NOW, 7],
            [[['due_at' => self::NOW, 'completed' => 'false']], self::NOW, 7],
            [array_fill(0, 21, $valid), self::NOW, 7],
            [[7 => $valid], self::NOW, 7],
        ];
        foreach ($invalid as $args) {
            try {
                $horizon->summarize(...$args);
                self::fail('Invalid synthetic horizon unexpectedly accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
                self::assertStringNotContainsString('forged', $e->getMessage());
            }
        }
    }
}
