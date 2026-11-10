<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * Composes the synthetic follow-up aging and workload projections in-memory.
 *
 * This is not an authorized tenant query or an operational follow-up dashboard.
 * The caller supplies all records and the explicit clock; no side effects.
 */
final class FollowUpSnapshot
{
    /**
     * @param list<array{id:string,due_at:string}> $items
     * @return array{
     *   buckets:array{overdue:list<string>,due_24h:list<string>,due_later:list<string>},
     *   counts:array{overdue:int,due_24h:int,due_later:int,total:int}
     * }
     */
    public static function build(array $items, string $asOf): array
    {
        $buckets = FollowUpAgingProjection::project($items, $asOf);

        return [
            'buckets' => $buckets,
            'counts' => FollowUpWorkloadSummary::summarize($buckets),
        ];
    }
}
