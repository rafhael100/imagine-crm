<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticTaskPortfolioInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticTaskPortfolioInspectorTest extends TestCase
{
    private static function history(string $due, string $priority, bool $closed = false): array
    {
        $events = [[
            'revision' => 1, 'action' => 'create',
            'occurred_at_utc' => '2026-11-01T10:00:00Z',
            'due_at_utc' => $due, 'priority' => $priority,
            'reason_code' => null,
        ]];
        if ($closed) {
            $events[] = [
                'revision' => 2, 'action' => 'start',
                'occurred_at_utc' => '2026-11-01T11:00:00Z',
                'due_at_utc' => null, 'priority' => null, 'reason_code' => null,
            ];
            $events[] = [
                'revision' => 3, 'action' => 'complete',
                'occurred_at_utc' => '2026-11-01T12:00:00Z',
                'due_at_utc' => null, 'priority' => null, 'reason_code' => null,
            ];
        }
        return $events;
    }

    public function test_sorts_open_tasks_by_due_phase_then_priority_and_stable_position(): void
    {
        $now = '2026-11-10T12:00:00Z';
        $items = [
            self::history('2026-11-11T12:00:00Z', 'urgent'),
            self::history('2026-11-09T12:00:00Z', 'normal'),
            self::history('2026-11-10T12:00:00Z', 'low'),
            self::history('2026-11-08T12:00:00Z', 'urgent'),
            self::history('2026-11-08T12:00:00Z', 'urgent'),
            self::history('2026-11-07T12:00:00Z', 'high', true),
        ];
        $original = $items;
        $result = (new SyntheticTaskPortfolioInspector())->inspect($items, $now);
        self::assertSame(6, $result['task_count']);
        self::assertSame(5, $result['open_count']);
        self::assertSame(1, $result['closed_count']);
        self::assertSame(3, $result['overdue_count']);
        self::assertSame(1, $result['due_now_count']);
        self::assertSame(1, $result['upcoming_count']);
        self::assertSame([3, 4, 1, 2, 0], $result['ordered_open_positions']);
        self::assertSame(['urgent'=>3,'high'=>0,'normal'=>1,'low'=>1], $result['open_priority_counts']);
        self::assertFalse($result['can_notify']);
        self::assertFalse($result['can_assign']);
        self::assertSame($original, $items);
        self::assertSame($result, (new SyntheticTaskPortfolioInspector())->inspect($items, $now));
    }

    public function test_closed_only_is_valid_but_creates_no_work_items_or_permissions(): void
    {
        $out = (new SyntheticTaskPortfolioInspector())->inspect([
            self::history('2026-11-05T12:00:00Z', 'high', true),
        ], '2026-11-10T12:00:00Z');
        self::assertSame(0, $out['open_count']);
        self::assertSame([], $out['ordered_open_positions']);
        self::assertSame(1, $out['closed_count']);
        self::assertFalse($out['authorized']);
    }

    public function test_bad_histories_hidden_identifiers_and_invalid_time_fail_closed(): void
    {
        $good = self::history('2026-11-09T12:00:00Z', 'normal');
        $bad = [
            [[], '2026-11-10T12:00:00Z'],
            [array_fill(0, 11, $good), '2026-11-10T12:00:00Z'],
            [[[]], '2026-11-10T12:00:00Z'],
            [[$good, 'wrong'], '2026-11-10T12:00:00Z'],
            [[array_merge($good, [['email' => 'secret@example.test']])], '2026-11-10T12:00:00Z'],
            [[$good], '2026-11-10'],
            [[$good], null],
            [[$good], '2026-02-30T12:00:00Z'],
            [['first' => $good], '2026-11-10T12:00:00Z'],
        ];
        foreach ($bad as [$rows, $now]) {
            try {
                (new SyntheticTaskPortfolioInspector())->inspect($rows, $now);
                self::fail('Unvalidated synthetic portfolio accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Carteira sintetica de atividades invalida.', $exception->getMessage());
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }

    public function test_bounded_output_contains_no_timestamps_id_or_operational_permissions(): void
    {
        $out = (new SyntheticTaskPortfolioInspector())->inspect([
            self::history('2026-11-09T12:00:00Z', 'normal'),
        ], '2026-11-10T12:00:00Z');
        self::assertSame([
            'task_count', 'open_count', 'closed_count', 'overdue_count',
            'due_now_count', 'upcoming_count', 'open_priority_counts',
            'ordered_open_positions', 'source_history_verified',
            'task_identity_verified', 'tenant_scope_verified',
            'sla_policy_verified', 'informational_only', 'authorized',
            'can_read_tasks', 'can_assign', 'can_update_task', 'can_notify',
            'can_persist',
        ], array_keys($out));
        self::assertTrue($out['informational_only']);
        foreach (['source_history_verified','task_identity_verified','tenant_scope_verified',
            'sla_policy_verified','authorized','can_read_tasks','can_assign',
            'can_update_task','can_notify','can_persist'] as $key) {
            self::assertFalse($out[$key]);
        }
        $encoded = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['2026-','email','actor_id','tenant_id','task_id','due_at_utc','contact'] as $forbidden) {
            self::assertStringNotContainsString('"'.$forbidden.'"', $encoded);
        }
    }
}
