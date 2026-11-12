<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpQueue;
use App\Core\Activities\SyntheticRecurringTaskSchedule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticRecurringTaskScheduleTest extends TestCase
{
    public function test_month_end_recurrence_preserves_anchor_day_instead_of_drifting(): void
    {
        $scheduler = new SyntheticRecurringTaskSchedule();
        $result = $scheduler->preview(
            '2026-01-31T10:15:30Z', 'monthly', 4, '2026-02-28T10:15:30Z',
        );
        self::assertSame([
            '2026-01-31T10:15:30Z',
            '2026-02-28T10:15:30Z',
            '2026-03-31T10:15:30Z',
            '2026-04-30T10:15:30Z',
        ], $result['occurrences_utc']);
        self::assertSame('monthly', $result['frequency']);
        self::assertSame(4, $result['occurrence_count']);
        self::assertSame(1, $result['past_count']);
        self::assertSame(1, $result['due_now_count']);
        self::assertSame(2, $result['upcoming_count']);
        self::assertSame('2026-03-31T10:15:30Z', $result['next_upcoming_utc']);
        self::assertSame($result, $scheduler->preview(
            '2026-01-31T10:15:30Z', 'monthly', 4, '2026-02-28T10:15:30Z',
        ));
        self::assertFalse($result['calendar_policy_verified']);
        self::assertFalse($result['task_identity_verified']);
        self::assertTrue($result['informational_only']);
        foreach (['authorized', 'can_create_task', 'can_schedule_job', 'can_notify', 'can_persist'] as $flag) {
            self::assertFalse($result[$flag]);
        }
    }

    public function test_leap_february_does_not_shift_march_or_produce_invalid_dates(): void
    {
        $result = (new SyntheticRecurringTaskSchedule())->preview(
            '2028-01-31T23:59:59Z', 'monthly', 4, '2028-02-28T23:59:59Z',
        );
        self::assertSame([
            '2028-01-31T23:59:59Z',
            '2028-02-29T23:59:59Z',
            '2028-03-31T23:59:59Z',
            '2028-04-30T23:59:59Z',
        ], $result['occurrences_utc']);
        self::assertSame(1, $result['past_count']);
        self::assertSame(3, $result['upcoming_count']);
        self::assertSame('2028-02-29T23:59:59Z', $result['next_upcoming_utc']);
        self::assertSame(0, $result['due_now_count']);

        $leapAnchor = (new SyntheticRecurringTaskSchedule())->preview(
            '2028-02-29T12:00:00Z', 'monthly', 3, '2028-04-30T12:00:00Z',
        );
        self::assertSame([
            '2028-02-29T12:00:00Z',
            '2028-03-29T12:00:00Z',
            '2028-04-29T12:00:00Z',
        ], $leapAnchor['occurrences_utc']);
    }

    public function test_daily_and_weekly_utc_intervals_cross_months_and_years(): void
    {
        $scheduler = new SyntheticRecurringTaskSchedule();
        $daily = $scheduler->preview(
            '2026-12-30T00:00:00Z', 'daily', 4, '2026-12-31T00:00:00Z',
        );
        self::assertSame([
            '2026-12-30T00:00:00Z',
            '2026-12-31T00:00:00Z',
            '2027-01-01T00:00:00Z',
            '2027-01-02T00:00:00Z',
        ], $daily['occurrences_utc']);
        self::assertSame(1, $daily['past_count']);
        self::assertSame(1, $daily['due_now_count']);
        self::assertSame(2, $daily['upcoming_count']);

        $weekly = $scheduler->preview(
            '2026-11-01T08:00:00Z', 'weekly', 4, '2026-11-16T08:00:00Z',
        );
        self::assertSame([
            '2026-11-01T08:00:00Z',
            '2026-11-08T08:00:00Z',
            '2026-11-15T08:00:00Z',
            '2026-11-22T08:00:00Z',
        ], $weekly['occurrences_utc']);
        self::assertSame(3, $weekly['past_count']);
        self::assertSame(0, $weekly['due_now_count']);
        self::assertSame(1, $weekly['upcoming_count']);
        self::assertSame('2026-11-22T08:00:00Z', $weekly['next_upcoming_utc']);
    }

    public function test_planned_occurrences_reconcile_with_existing_followup_deadline_calculator(): void
    {
        $now = '2026-02-28T10:15:30Z';
        $plan = (new SyntheticRecurringTaskSchedule())->preview(
            '2026-01-31T10:15:30Z', 'monthly', 4, $now,
        );
        $followups = array_map(
            static fn (string $due): array => ['due_at' => $due, 'completed' => false],
            $plan['occurrences_utc'],
        );
        $queue = (new SyntheticFollowUpQueue())->summarize($followups, $now);

        self::assertSame($plan['occurrence_count'], $queue['total']);
        self::assertSame($plan['past_count'], $queue['overdue']);
        self::assertSame($plan['due_now_count'], $queue['due_now']);
        self::assertSame($plan['upcoming_count'], $queue['upcoming']);
        self::assertFalse($queue['authorized']);
        self::assertFalse($queue['can_notify']);
        self::assertFalse($plan['can_schedule_job']);
    }

    public function test_count_bounds_and_no_future_occurrence_remain_non_operational(): void
    {
        $last = (new SyntheticRecurringTaskSchedule())->preview(
            '2026-11-01T00:00:00Z', 'weekly', 16, '2027-12-01T00:00:00Z',
        );
        self::assertSame(16, count($last['occurrences_utc']));
        self::assertSame(16, $last['past_count']);
        self::assertSame(0, $last['upcoming_count']);
        self::assertNull($last['next_upcoming_utc']);
        self::assertFalse($last['can_notify']);

        $single = (new SyntheticRecurringTaskSchedule())->preview(
            '2026-11-01T00:00:00Z', 'daily', 1, '2026-10-31T23:59:59Z',
        );
        self::assertSame(0, $single['past_count']);
        self::assertSame(0, $single['due_now_count']);
        self::assertSame(1, $single['upcoming_count']);
        self::assertSame('2026-11-01T00:00:00Z', $single['next_upcoming_utc']);
    }

    public function test_invalid_frequencies_dates_types_and_year_overflow_are_denied(): void
    {
        $first = '2026-11-01T00:00:00Z';
        $now = '2026-11-05T00:00:00Z';
        $bad = [
            [null, 'daily', 2, $now],
            [$first, null, 2, $now],
            [$first, 'hourly', 2, $now],
            [$first, 'DAILY', 2, $now],
            [$first, 'daily', 0, $now],
            [$first, 'daily', 17, $now],
            [$first, 'daily', '2', $now],
            [$first, 'daily', true, $now],
            [$first, 'daily', 2.0, $now],
            [$first, 'daily', 2, null],
            [$first, 'daily', 2, 'private@example.test'],
            ['2026-02-30T12:00:00Z', 'daily', 2, $now],
            ['2026-11-01T00:00:60Z', 'daily', 2, $now],
            ['2026-11-01 00:00:00Z', 'daily', 2, $now],
            ['2026-11-01T00:00:00+00:00', 'daily', 2, $now],
            ['2026-11-01T00:00:00.000Z', 'daily', 2, $now],
            ['1900-11-01T00:00:00Z', 'daily', 2, $now],
            ['2100-01-01T00:00:00Z', 'daily', 2, $now],
            [$first, 'weekly', 2, '2026-02-30T00:00:00Z'],
            ['2099-12-31T00:00:00Z', 'daily', 2, $now],
            ['2099-12-31T00:00:00Z', 'weekly', 2, $now],
            ['2099-12-31T00:00:00Z', 'monthly', 2, $now],
        ];

        foreach ($bad as [$anchor, $freq, $count, $observed]) {
            try {
                (new SyntheticRecurringTaskSchedule())->preview($anchor, $freq, $count, $observed);
                self::fail('Invalid synthetic recurrence was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Recorrencia sintetica de tarefa invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_result_has_no_task_identity_authorization_or_background_execution(): void
    {
        $out = (new SyntheticRecurringTaskSchedule())->preview(
            '2026-11-01T12:00:00Z', 'daily', 2, '2026-11-01T12:00:00Z',
        );
        self::assertSame([
            'frequency', 'occurrence_count', 'occurrences_utc',
            'past_count', 'due_now_count', 'upcoming_count',
            'next_upcoming_utc', 'calendar_policy_verified',
            'task_identity_verified', 'informational_only',
            'authorized', 'can_create_task', 'can_schedule_job',
            'can_notify', 'can_persist',
        ], array_keys($out));
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach ([
            'tenant', 'task_id', 'actor', 'customer', 'email', 'phone',
            'message', 'webhook', 'worker', 'job_id',
        ] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
        foreach ($out as $key => $value) {
            if ($key === 'authorized' || str_starts_with($key, 'can_')
                || str_ends_with($key, '_verified')) {
                self::assertFalse($value);
            }
        }
    }
}
