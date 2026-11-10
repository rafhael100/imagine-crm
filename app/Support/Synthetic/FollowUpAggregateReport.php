<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * Pure, count-only projection of two synthetic follow-up snapshots.
 *
 * Input identifiers never appear in the result. This does not authorize
 * publication of real aggregate data or establish tenant permissions.
 */
final class FollowUpAggregateReport
{
    /**
     * @param array{buckets:array,counts:array} $previous
     * @param array{buckets:array,counts:array} $current
     * @return array{
     *   previous:array{overdue:int,due_24h:int,due_later:int,total:int},
     *   current:array{overdue:int,due_24h:int,due_later:int,total:int},
     *   delta:array{overdue:int,due_24h:int,due_later:int,total:int},
     *   continuity:array{entered:int,exited:int,unchanged:int,moved:int}
     * }
     */
    public static function build(array $previous, array $current): array
    {
        $changes = FollowUpBucketTransitions::compare($previous, $current);

        return [
            'previous' => $previous['counts'],
            'current' => $current['counts'],
            'delta' => FollowUpWorkloadDelta::compare($previous['counts'], $current['counts']),
            'continuity' => [
                'entered' => count($changes['entered']),
                'exited' => count($changes['exited']),
                'unchanged' => $changes['unchanged'],
                'moved' => $changes['moved'],
            ],
        ];
    }
}
