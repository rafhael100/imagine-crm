<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticRecurringTaskSchedule;
use App\Core\Activities\SyntheticTaskLifecycleInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticTaskLifecycleInspectorTest extends TestCase
{
    /** @return array<string,mixed> */
    private static function step(
        int $revision,
        string $action,
        string $at,
        ?string $due = null,
        ?string $priority = null,
        ?string $reason = null,
    ): array {
        return [
            'revision' => $revision,
            'action' => $action,
            'occurred_at_utc' => $at,
            'due_at_utc' => $due,
            'priority' => $priority,
            'reason_code' => $reason,
        ];
    }

    /** @return array<string,mixed> */
    private static function created(
        string $due = '2026-11-06T09:00:00Z',
    ): array {
        return self::step(
            1, 'create', '2026-11-01T09:00:00Z', $due, 'high',
        );
    }

    public function test_task_transitions_priority_deadlines_and_reopenings_are_pure_claims(): void
    {
        $events = [
            self::created(),
            self::step(2, 'start', '2026-11-01T09:00:01Z'),
            self::step(3, 'reschedule', '2026-11-01T09:00:02Z',
                '2026-11-07T09:00:00Z', null, 'schedule_change'),
            self::step(4, 'reprioritize', '2026-11-01T09:00:03Z',
                null, 'urgent', 'priority_change'),
            self::step(5, 'complete', '2026-11-01T09:00:04Z'),
            self::step(6, 'reopen', '2026-11-01T09:00:05Z',
                null, null, 'correction'),
            self::step(7, 'start', '2026-11-01T09:00:06Z'),
            self::step(8, 'cancel', '2026-11-01T09:00:07Z',
                null, null, 'not_needed'),
        ];
        $original = $events;
        $inspector = new SyntheticTaskLifecycleInspector();
        $report = $inspector->inspect($events, '2026-11-11T09:00:00Z');

        self::assertSame(8, $report['event_count']);
        self::assertSame(8, $report['latest_revision']);
        self::assertSame('canceled', $report['latest_claimed_status']);
        self::assertSame('urgent', $report['claimed_priority']);
        self::assertSame('closed', $report['due_phase']);
        self::assertSame(2, $report['claimed_starts']);
        self::assertSame(1, $report['claimed_completions']);
        self::assertSame(1, $report['claimed_cancellations']);
        self::assertSame(1, $report['claimed_reopenings']);
        self::assertSame(1, $report['claimed_reschedules']);
        self::assertSame(1, $report['claimed_reprioritizations']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'history_verified', 'task_identity_verified', 'tenant_verified',
            'priority_policy_verified', 'due_policy_verified',
            'authorized', 'can_create_task', 'can_update_task',
            'can_complete_task', 'can_notify', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag]);
        }
        self::assertSame($original, $events);
        self::assertSame($report, $inspector->inspect($events, '2026-11-11T09:00:00Z'));
    }

    public function test_due_phase_reuses_followup_comparison_at_exact_utc_second(): void
    {
        $events = [self::created()];
        $review = new SyntheticTaskLifecycleInspector();
        self::assertSame(
            'upcoming',
            $review->inspect($events, '2026-11-06T08:59:59Z')['due_phase'],
        );
        self::assertSame(
            'due_now',
            $review->inspect($events, '2026-11-06T09:00:00Z')['due_phase'],
        );
        self::assertSame(
            'overdue',
            $review->inspect($events, '2026-11-06T09:00:01Z')['due_phase'],
        );

        $completed = [
            ...$events,
            self::step(2, 'start', '2026-11-01T09:00:01Z'),
            self::step(3, 'complete', '2026-11-01T09:00:02Z'),
        ];
        self::assertSame(
            'closed',
            $review->inspect($completed, '2026-11-11T12:00:00Z')['due_phase'],
        );
        self::assertFalse(
            $review->inspect($completed, '2026-11-11T12:00:00Z')['can_notify'],
        );

        $empty = $review->inspect([], '2026-11-11T12:00:00Z');
        self::assertSame('no_task', $empty['due_phase']);
        self::assertSame(0, $empty['event_count']);
        self::assertNull($empty['latest_claimed_status']);
        self::assertNull($empty['claimed_priority']);
        self::assertFalse($empty['authorized']);
    }

    public function test_due_date_from_existing_monthly_recurrence_can_be_previewed_without_creating_tasks(): void
    {
        $recurring = (new SyntheticRecurringTaskSchedule())->preview(
            '2026-01-31T09:00:00Z', 'monthly', 3, '2026-01-15T09:00:00Z',
        );
        self::assertSame('2026-02-28T09:00:00Z', $recurring['occurrences_utc'][1]);

        $events = [self::step(
            1, 'create', '2026-01-31T08:59:59Z',
            $recurring['occurrences_utc'][1], 'normal',
        )];
        $task = (new SyntheticTaskLifecycleInspector())
            ->inspect($events, '2026-02-28T09:00:00Z');
        self::assertSame('due_now', $task['due_phase']);
        self::assertSame('normal', $task['claimed_priority']);
        self::assertFalse($task['can_create_task']);
        self::assertFalse($recurring['can_schedule_job']);
    }

    public function test_bounded_twenty_four_revisions_can_reopen_without_implying_audit(): void
    {
        $events = [self::step(
            1, 'create', '2026-11-01T00:00:00Z',
            '2026-11-03T00:00:00Z', 'normal',
        )];
        $state = 'pending';
        for ($revision = 2; $revision <= 24; ++$revision) {
            $at = sprintf('2026-11-01T00:00:%02dZ', $revision - 1);
            if ($state === 'pending') {
                $events[] = self::step($revision, 'start', $at);
                $state = 'in_progress';
            } elseif ($state === 'in_progress') {
                $events[] = self::step($revision, 'complete', $at);
                $state = 'completed';
            } else {
                $events[] = self::step($revision, 'reopen', $at,
                    null, null, 'correction');
                $state = 'pending';
            }
        }
        $report = (new SyntheticTaskLifecycleInspector())
            ->inspect($events, '2026-11-03T00:00:00Z');
        self::assertSame(24, $report['event_count']);
        self::assertSame(24, $report['latest_revision']);
        self::assertFalse($report['history_verified']);

        $events[] = self::step(25, 'start', '2026-11-01T00:00:24Z');
        $this->expectException(InvalidArgumentException::class);
        (new SyntheticTaskLifecycleInspector())
            ->inspect($events, '2026-11-03T00:00:00Z');
    }

    public function test_invalid_transitions_priorities_due_dates_reasons_and_private_fields_fail_closed(): void
    {
        $create = self::created();
        $start = self::step(2, 'start', '2026-11-01T09:00:01Z');
        $complete = self::step(3, 'complete', '2026-11-01T09:00:02Z');
        $bad = [
            [null, '2026-11-01T09:00:00Z'],
            ['tasks', '2026-11-01T09:00:00Z'],
            [[], 'not-a-date'],
            [[], '2026-02-30T09:00:00Z'],
            [[null], '2026-11-01T09:00:00Z'],
            [[1 => $create], '2026-11-01T09:00:00Z'],
            [[self::step(1, 'start', '2026-11-01T09:00:00Z')], '2026-11-11T09:00:00Z'],
            [[$create, $create], '2026-11-11T09:00:00Z'],
            [[$create, self::step(3, 'start', '2026-11-01T09:00:01Z')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'start', '2026-11-01T09:00:00Z')], '2026-11-11T09:00:00Z'],
            [[$create, $start, self::step(3, 'start', '2026-11-01T09:00:02Z')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'complete', '2026-11-01T09:00:01Z')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'reopen', '2026-11-01T09:00:01Z', null, null, 'correction')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'cancel', '2026-11-01T09:00:01Z')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'cancel', '2026-11-01T09:00:01Z', null, null, 'private@example.test')], '2026-11-11T09:00:00Z'],
            [[$create, $start, $complete, self::step(4, 'complete', '2026-11-01T09:00:03Z')], '2026-11-11T09:00:00Z'],
            [[$create, $start, $complete, self::step(4, 'reschedule', '2026-11-01T09:00:03Z', '2026-11-07T09:00:00Z', null, 'schedule_change')], '2026-11-11T09:00:00Z'],
            [[$create, $start, $complete, self::step(4, 'reopen', '2026-11-01T09:00:03Z')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'reschedule', '2026-11-01T09:00:01Z', '2026-11-06T09:00:00Z', null, 'schedule_change')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'reschedule', '2026-11-01T09:00:01Z', '2026-02-30T00:00:00Z', null, 'schedule_change')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'reschedule', '2026-11-01T09:00:01Z', '2026-11-07T09:00:00Z')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'reprioritize', '2026-11-01T09:00:01Z', null, 'high', 'priority_change')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'reprioritize', '2026-11-01T09:00:01Z', null, 'unlimited', 'priority_change')], '2026-11-11T09:00:00Z'],
            [[$create, self::step(2, 'reprioritize', '2026-11-01T09:00:01Z', null, 'urgent')], '2026-11-11T09:00:00Z'],
            [[array_merge($create, ['tenant' => 'private@example.test'])], '2026-11-11T09:00:00Z'],
            [[array_merge($create, ['priority' => 'P0'])], '2026-11-11T09:00:00Z'],
            [[array_merge($create, ['due_at_utc' => null])], '2026-11-11T09:00:00Z'],
            [[array_merge($create, ['occurred_at_utc' => '2026-11-01 09:00:00Z'])], '2026-11-11T09:00:00Z'],
            [[array_merge($create, ['revision' => '1'])], '2026-11-11T09:00:00Z'],
        ];
        foreach ($bad as [$events, $observed]) {
            try {
                (new SyntheticTaskLifecycleInspector())->inspect($events, $observed);
                self::fail('Malformed fictional task or unsafe transition accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Ciclo sintetico de tarefa invalido.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_output_never_exposes_task_identity_dates_payload_or_real_authority(): void
    {
        $result = (new SyntheticTaskLifecycleInspector())->inspect(
            [self::created()],
            '2026-11-01T09:00:00Z',
        );
        self::assertSame([
            'event_count', 'latest_revision', 'latest_claimed_status',
            'claimed_priority', 'due_phase', 'claimed_starts',
            'claimed_completions', 'claimed_cancellations', 'claimed_reopenings',
            'claimed_reschedules', 'claimed_reprioritizations',
            'history_verified', 'task_identity_verified', 'tenant_verified',
            'priority_policy_verified', 'due_policy_verified',
            'informational_only', 'authorized', 'can_create_task',
            'can_update_task', 'can_complete_task', 'can_notify', 'can_persist',
        ], array_keys($result));
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('2026-11-01T', $json);
        foreach ([
            'task_id', 'tenant', 'email', 'phone', 'customer', 'lead_id',
            'event_reference', 'actor_id', 'description', 'note', 'attachment',
        ] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
        self::assertFalse($result['can_complete_task']);
        self::assertFalse($result['can_persist']);
    }
}
