<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Pure offline adapter: synthetic follow-up records -> bounded count-only series.
 *
 * No database, tenant resolution, authorizations, notifications, clock reads
 * or access to real customer data. An actual application adapter must undergo
 * independent authorization and data minimization reviews.
 */
final class FollowUpObservationSeries
{
    private const MAX_OBSERVATIONS = 31;
    private const MAX_ITEMS_PER_OBSERVATION = 100;

    /**
     * @param list<array{at:string,items:list<array{id:string,due_at:string}>}> $observations
     * @return list<array{at:string,counts:array,delta:?array,continuity:?array}>
     */
    public static function project(array $observations): array
    {
        if (!array_is_list($observations) || count($observations) > self::MAX_OBSERVATIONS) {
            throw new InvalidArgumentException('Synthetic observation list exceeds the supported limit.');
        }

        $snapshots = [];

        foreach ($observations as $observation) {
            if (!is_array($observation) || array_keys($observation) !== ['at', 'items']
                || !is_string($observation['at'])
                || !is_array($observation['items'])
                || !array_is_list($observation['items'])
                || count($observation['items']) > self::MAX_ITEMS_PER_OBSERVATION) {
                throw new InvalidArgumentException('Invalid or oversized synthetic observation.');
            }

            $snapshots[] = [
                'at' => $observation['at'],
                'snapshot' => FollowUpSnapshot::build($observation['items'], $observation['at']),
            ];
        }

        return FollowUpTimeline::build($snapshots);
    }
}
