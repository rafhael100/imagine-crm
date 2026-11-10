<?php

declare(strict_types=1);

namespace App\Core\Activities;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * F4-01: deterministic, side-effect-free follow-up deadline preview.
 *
 * Inputs are synthetic, anonymous fixture timestamps supplied by the caller.
 * This is not a scheduler, SLA policy, notification system, task repository,
 * authorization check or claim about a real organization's activities.
 */
final class SyntheticFollowUpQueue
{
    private const MAX_FIXTURES = 20;

    /**
     * @param list<array{due_at:string,completed:bool}> $fixtures
     * @return array{
     *   total:int,open:int,overdue:int,due_now:int,upcoming:int,completed:int,
     *   open_positions:list<int>,authorized:false,can_notify:false
     * }
     */
    public function summarize(array $fixtures, string $nowUtc): array
    {
        if (!array_is_list($fixtures) || count($fixtures) > self::MAX_FIXTURES) {
            self::invalid();
        }

        $now = self::timestamp($nowUtc);
        $due = [];
        $openPositions = [];
        $overdue = 0;
        $dueNow = 0;
        $upcoming = 0;
        $completed = 0;

        foreach ($fixtures as $position => $fixture) {
            if (!is_array($fixture)
                || array_keys($fixture) !== ['due_at', 'completed']
                || !is_string($fixture['due_at'])
                || !is_bool($fixture['completed'])) {
                self::invalid();
            }

            // Validate even completed/otherwise hidden fixtures. Never
            // silently skip an invalid or extra tenant/actor field.
            $timestamp = self::timestamp($fixture['due_at']);
            if ($fixture['completed']) {
                $completed++;
                continue;
            }

            $due[$position] = $timestamp;
            $openPositions[] = $position;
            if ($timestamp < $now) {
                $overdue++;
            } elseif ($timestamp === $now) {
                $dueNow++;
            } else {
                $upcoming++;
            }
        }

        usort($openPositions, static function (int $left, int $right) use ($due): int {
            // Explicit stable order for equal deadlines.
            return ($due[$left] <=> $due[$right]) ?: ($left <=> $right);
        });

        return [
            'total' => count($fixtures),
            'open' => count($openPositions),
            'overdue' => $overdue,
            'due_now' => $dueNow,
            'upcoming' => $upcoming,
            'completed' => $completed,
            'open_positions' => $openPositions,
            'authorized' => false,
            'can_notify' => false,
        ];
    }

    private static function timestamp(string $utc): int
    {
        // A fixed UTC vocabulary prevents ambiguous local dates and DST
        // interpretation. DateTime normalizes invalid days, so round-trip.
        if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D', $utc) !== 1) {
            self::invalid();
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $utc, new DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $utc) {
            self::invalid();
        }

        return $date->getTimestamp();
    }

    private static function invalid(): never
    {
        // Do not echo an untrusted timestamp, tenant, task or user field.
        throw new InvalidArgumentException('Invalid synthetic follow-up fixture.');
    }
}
