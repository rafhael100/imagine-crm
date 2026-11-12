<?php

declare(strict_types=1);

namespace App\Core\Activities;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * VEN-R21: deterministic UTC occurrence preview for fictional recurring
 * commercial tasks. No task identity, timezone policy, worker, scheduling
 * grant, delivery, modification, persistence or background execution.
 *
 * Monthly occurrences preserve the ORIGINAL anchor day and clamp only that
 * month's day. Jan 31 => Feb 28 => Mar 31, rather than drifting to Mar 28.
 */
final class SyntheticRecurringTaskSchedule
{
    private const FREQUENCIES = ['daily', 'weekly', 'monthly'];
    private const MAX_OCCURRENCES = 16;

    /**
     * @param mixed $firstAtUtc exact YYYY-MM-DDTHH:mm:ssZ
     * @param mixed $frequency daily|weekly|monthly
     * @param mixed $count integer 1..16
     * @param mixed $observedAtUtc exact YYYY-MM-DDTHH:mm:ssZ
     * @return array<string,mixed> Fixed, no-authority schedule readout
     */
    public function preview(
        mixed $firstAtUtc,
        mixed $frequency,
        mixed $count,
        mixed $observedAtUtc,
    ): array {
        if (!is_string($firstAtUtc)
            || !is_string($observedAtUtc)
            || !is_string($frequency)
            || !in_array($frequency, self::FREQUENCIES, true)
            || !is_int($count)
            || $count < 1
            || $count > self::MAX_OCCURRENCES) {
            self::invalid();
        }

        $first = self::canonicalUtc($firstAtUtc);
        $observed = self::canonicalUtc($observedAtUtc);
        $occurrences = [];
        $past = 0;
        $dueNow = 0;
        $upcoming = 0;
        $next = null;

        for ($index = 0; $index < $count; ++$index) {
            if ($frequency === 'daily') {
                $date = $first->modify('+'.$index.' days');
            } elseif ($frequency === 'weekly') {
                $date = $first->modify('+'.$index.' weeks');
            } else {
                // Start at the first day of the original month, then add
                // months without allowing end-of-month PHP rollover.
                $month = $first->modify('first day of this month')
                    ->modify('+'.$index.' months');
                $day = min((int) $first->format('j'), (int) $month->format('t'));
                $date = $month->setDate(
                    (int) $month->format('Y'),
                    (int) $month->format('n'),
                    $day,
                )->setTime(
                    (int) $first->format('H'),
                    (int) $first->format('i'),
                    (int) $first->format('s'),
                );
            }

            $stamp = $date->format('Y-m-d\TH:i:s\Z');
            if (!str_starts_with($stamp, '20') || !self::isCanonicalUtc($stamp)) {
                self::invalid();
            }

            $occurrences[] = $stamp;
            $comparison = strcmp($stamp, $observedAtUtc);
            if ($comparison < 0) {
                ++$past;
            } elseif ($comparison === 0) {
                ++$dueNow;
            } else {
                ++$upcoming;
                $next ??= $stamp;
            }
        }

        return [
            'frequency' => $frequency,
            'occurrence_count' => $count,
            'occurrences_utc' => $occurrences,
            'past_count' => $past,
            'due_now_count' => $dueNow,
            'upcoming_count' => $upcoming,
            'next_upcoming_utc' => $next,
            'calendar_policy_verified' => false,
            'task_identity_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_create_task' => false,
            'can_schedule_job' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function canonicalUtc(string $value): DateTimeImmutable
    {
        if (!self::isCanonicalUtc($value)) {
            self::invalid();
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z',
            $value,
            new DateTimeZone('UTC'),
        );

        if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $value) {
            self::invalid();
        }

        return $date;
    }

    private static function isCanonicalUtc(string $value): bool
    {
        return preg_match(
            '/\A20[0-9]{2}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D',
            $value,
        ) === 1;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Recorrencia sintetica de tarefa invalida.');
    }
}
