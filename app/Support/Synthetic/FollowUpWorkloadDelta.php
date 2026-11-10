<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Pure, inert comparison of two synthetic F4-07 workload summaries.
 *
 * Negative deltas indicate fewer items in the corresponding bucket, but do
 * not imply resolution, SLA compliance, user performance or lead conversion.
 */
final class FollowUpWorkloadDelta
{
    private const KEYS = ['overdue', 'due_24h', 'due_later', 'total'];

    /**
     * @param array{overdue:int,due_24h:int,due_later:int,total:int} $previous
     * @param array{overdue:int,due_24h:int,due_later:int,total:int} $current
     * @return array{overdue:int,due_24h:int,due_later:int,total:int}
     */
    public static function compare(array $previous, array $current): array
    {
        self::validate($previous);
        self::validate($current);

        return [
            'overdue' => $current['overdue'] - $previous['overdue'],
            'due_24h' => $current['due_24h'] - $previous['due_24h'],
            'due_later' => $current['due_later'] - $previous['due_later'],
            'total' => $current['total'] - $previous['total'],
        ];
    }

    private static function validate(array $summary): void
    {
        if (array_keys($summary) !== self::KEYS) {
            throw new InvalidArgumentException('Expected exact ordered workload summary.');
        }

        foreach (self::KEYS as $key) {
            if (!is_int($summary[$key]) || $summary[$key] < 0) {
                throw new InvalidArgumentException('Expected non-negative integer counts.');
            }
        }

        if ($summary['total'] !== $summary['overdue'] + $summary['due_24h'] + $summary['due_later']) {
            throw new InvalidArgumentException('Workload total does not equal bucket sum.');
        }
    }
}
