<?php

declare(strict_types=1);

namespace App\Core\Activities;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * VEN-R20/R21: pure reducer of a fictional commercial-task lifecycle.
 *
 * Reuses the same validated due-time semantics as SyntheticFollowUpQueue.
 * Event revisions are NOT database optimistic locks; completion and the
 * supplied timestamps are claims, never audit evidence or authorization.
 */
final class SyntheticTaskLifecycleInspector
{
    private const MAX_EVENTS = 24;
    private const ACTIONS = [
        'create', 'start', 'complete', 'cancel',
        'reopen', 'reschedule', 'reprioritize',
    ];
    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    private const CANCEL_REASONS = ['duplicate', 'not_needed'];
    private const REOPEN_REASONS = ['correction', 'new_followup'];

    /**
     * @param mixed $events list<array{
     *   revision:int,action:string,occurred_at_utc:string,
     *   due_at_utc:?string,priority:?string,reason_code:?string
     * }>
     * @param mixed $observedAtUtc canonical UTC string
     * @return array<string,mixed> no task IDs or user-supplied content
     */
    public function inspect(mixed $events, mixed $observedAtUtc): array
    {
        if (!is_array($events)
            || !array_is_list($events)
            || count($events) > self::MAX_EVENTS
            || !is_string($observedAtUtc)
            || !self::canonicalUtc($observedAtUtc)) {
            self::invalid();
        }

        $state = null;
        $priority = null;
        $due = null;
        $previousAt = null;
        $starts = 0;
        $completions = 0;
        $cancellations = 0;
        $reopens = 0;
        $reschedules = 0;
        $reprioritizations = 0;

        foreach ($events as $index => $event) {
            if (!is_array($event) || count($event) !== 6) {
                self::invalid();
            }
            $keys = array_keys($event);
            sort($keys);
            if ($keys !== [
                'action', 'due_at_utc', 'occurred_at_utc',
                'priority', 'reason_code', 'revision',
            ]
                || !is_int($event['revision'])
                || $event['revision'] !== $index + 1
                || !is_string($event['action'])
                || !in_array($event['action'], self::ACTIONS, true)
                || !is_string($event['occurred_at_utc'])
                || !self::canonicalUtc($event['occurred_at_utc'])
                || ($previousAt !== null
                    && strcmp($event['occurred_at_utc'], $previousAt) <= 0)) {
                self::invalid();
            }

            $action = $event['action'];
            $newDue = $event['due_at_utc'];
            $newPriority = $event['priority'];
            $reason = $event['reason_code'];

            if ($action === 'create') {
                if ($index !== 0 || $state !== null
                    || !is_string($newDue) || !self::canonicalUtc($newDue)
                    || !is_string($newPriority)
                    || !in_array($newPriority, self::PRIORITIES, true)
                    || $reason !== null) {
                    self::invalid();
                }
                $state = 'pending';
                $due = $newDue;
                $priority = $newPriority;
            } elseif ($action === 'start') {
                if ($state !== 'pending'
                    || $newDue !== null || $newPriority !== null || $reason !== null) {
                    self::invalid();
                }
                $state = 'in_progress';
                ++$starts;
            } elseif ($action === 'complete') {
                if ($state !== 'in_progress'
                    || $newDue !== null || $newPriority !== null || $reason !== null) {
                    self::invalid();
                }
                $state = 'completed';
                ++$completions;
            } elseif ($action === 'cancel') {
                if (!in_array($state, ['pending', 'in_progress'], true)
                    || !is_string($reason)
                    || !in_array($reason, self::CANCEL_REASONS, true)
                    || $newDue !== null || $newPriority !== null) {
                    self::invalid();
                }
                $state = 'canceled';
                ++$cancellations;
            } elseif ($action === 'reopen') {
                if (!in_array($state, ['completed', 'canceled'], true)
                    || !is_string($reason)
                    || !in_array($reason, self::REOPEN_REASONS, true)
                    || $newDue !== null || $newPriority !== null) {
                    self::invalid();
                }
                $state = 'pending';
                ++$reopens;
            } elseif ($action === 'reschedule') {
                if (!in_array($state, ['pending', 'in_progress'], true)
                    || !is_string($newDue) || !self::canonicalUtc($newDue)
                    || $newDue === $due
                    || $newPriority !== null || $reason !== 'schedule_change') {
                    self::invalid();
                }
                $due = $newDue;
                ++$reschedules;
            } elseif ($action === 'reprioritize') {
                if (!in_array($state, ['pending', 'in_progress'], true)
                    || !is_string($newPriority)
                    || !in_array($newPriority, self::PRIORITIES, true)
                    || $newPriority === $priority
                    || $newDue !== null || $reason !== 'priority_change') {
                    self::invalid();
                }
                $priority = $newPriority;
                ++$reprioritizations;
            } else {
                self::invalid();
            }
            $previousAt = $event['occurred_at_utc'];
        }

        $duePhase = 'no_task';
        if ($state === 'completed' || $state === 'canceled') {
            $duePhase = 'closed';
        } elseif ($state !== null) {
            // The same purely synthetic UTC comparator is reused by the
            // activity queue; closed tasks never generate a due alert.
            $summary = (new SyntheticFollowUpQueue())->summarize([
                ['due_at' => $due, 'completed' => false],
            ], $observedAtUtc);
            $duePhase = $summary['overdue'] > 0
                ? 'overdue'
                : ($summary['due_now'] > 0 ? 'due_now' : 'upcoming');
        }

        return [
            'event_count' => count($events),
            'latest_revision' => count($events),
            'latest_claimed_status' => $state,
            'claimed_priority' => $priority,
            'due_phase' => $duePhase,
            'claimed_starts' => $starts,
            'claimed_completions' => $completions,
            'claimed_cancellations' => $cancellations,
            'claimed_reopenings' => $reopens,
            'claimed_reschedules' => $reschedules,
            'claimed_reprioritizations' => $reprioritizations,
            'history_verified' => false,
            'task_identity_verified' => false,
            'tenant_verified' => false,
            'priority_policy_verified' => false,
            'due_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_create_task' => false,
            'can_update_task' => false,
            'can_complete_task' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function canonicalUtc(string $value): bool
    {
        if (preg_match(
            '/\A20[0-9]{2}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D',
            $value,
        ) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'),
        );

        return $date !== false && $date->format('Y-m-d\TH:i:s\Z') === $value;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Ciclo sintetico de tarefa invalido.');
    }
}
