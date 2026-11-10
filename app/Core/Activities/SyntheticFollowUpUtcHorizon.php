<?php

declare(strict_types=1);

namespace App\Core\Activities;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;

/**
 * F4-04: pure UTC calendar-day buckets for anonymous follow-up fixtures.
 *
 * This does not define an SLA, a working day, a user's timezone, a task,
 * identity, an entitlement, a scheduling rule or a notification.
 */
final class SyntheticFollowUpUtcHorizon
{
    /**
     * @param list<array{due_at:string,completed:bool}> $fixtures
     * @return array{
     *   total:int,open:int,overdue:int,due_now:int,completed:int,
     *   horizon_days:int,daily:array<string,int>,upcoming_within_horizon:int,
     *   upcoming_outside_horizon:int,authorized:false,can_notify:false
     * }
     */
    public function summarize(array $fixtures, string $nowUtc, int $horizonDays = 7): array
    {
        if ($horizonDays < 1 || $horizonDays > 14) {
            throw new InvalidArgumentException('Invalid synthetic UTC horizon.');
        }

        // Validates the *entire* fixture set and exact ISO UTC string first.
        // An invalid completed or non-selected item cannot be hidden.
        $summary = (new SyntheticFollowUpQueue())->summarize($fixtures, $nowUtc);
        $start = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            substr($nowUtc, 0, 10),
            new DateTimeZone('UTC'),
        );
        if ($start === false) {
            throw new LogicException('Synthetic UTC horizon could not be constructed.');
        }

        $daily = [];
        for ($offset = 0; $offset < $horizonDays; ++$offset) {
            $day = $start->modify('+'.$offset.' days')->format('Y-m-d');
            $daily[$day] = 0;
        }

        $within = 0;
        $outside = 0;
        foreach ($fixtures as $fixture) {
            if ($fixture['completed'] || strcmp($fixture['due_at'], $nowUtc) <= 0) {
                continue;
            }

            // Both strings have passed canonical UTC validation; ordering of
            // zero-padded timestamps is chronological in UTC.
            $utcDay = substr($fixture['due_at'], 0, 10);
            if (array_key_exists($utcDay, $daily)) {
                $daily[$utcDay]++;
                $within++;
            } else {
                $outside++;
            }
        }

        if ($within + $outside !== $summary['upcoming']) {
            // Counts disagree: fail rather than display a potentially wrong
            // summary. Neither state grants operational authority.
            throw new LogicException('Synthetic UTC horizon count mismatch.');
        }

        return [
            'total' => $summary['total'],
            'open' => $summary['open'],
            'overdue' => $summary['overdue'],
            'due_now' => $summary['due_now'],
            'completed' => $summary['completed'],
            'horizon_days' => $horizonDays,
            'daily' => $daily,
            'upcoming_within_horizon' => $within,
            'upcoming_outside_horizon' => $outside,
            'authorized' => false,
            'can_notify' => false,
        ];
    }
}
