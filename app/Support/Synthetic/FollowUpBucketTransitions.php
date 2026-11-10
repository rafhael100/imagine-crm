<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Compares identifier memberships of two synthetic snapshots.
 *
 * Movement is descriptive, not proof of completed work, conversion,
 * SLA compliance, deletion reason or any other business event.
 */
final class FollowUpBucketTransitions
{
    private const BUCKETS = ['overdue', 'due_24h', 'due_later'];

    /**
     * @param array{buckets:array,counts:array} $previous
     * @param array{buckets:array,counts:array} $current
     * @return array{transitions:array<string,array<string,int>>,entered:list<string>,exited:list<string>,unchanged:int,moved:int}
     */
    public static function compare(array $previous, array $current): array
    {
        $before = self::indexSnapshot($previous);
        $after = self::indexSnapshot($current);

        $matrix = [];
        foreach (self::BUCKETS as $from) {
            $matrix[$from] = array_fill_keys(self::BUCKETS, 0);
        }

        $entered = [];
        $exited = [];
        $unchanged = 0;
        $moved = 0;

        foreach ($before as $id => $from) {
            if (!array_key_exists($id, $after)) {
                $exited[] = $id;
                continue;
            }
            $to = $after[$id];
            ++$matrix[$from][$to];
            if ($from === $to) {
                ++$unchanged;
            } else {
                ++$moved;
            }
        }

        foreach ($after as $id => $to) {
            if (!array_key_exists($id, $before)) {
                $entered[] = $id;
            }
        }

        return [
            'transitions' => $matrix,
            'entered' => $entered,
            'exited' => $exited,
            'unchanged' => $unchanged,
            'moved' => $moved,
        ];
    }

    /** @return array<string,string> */
    private static function indexSnapshot(array $snapshot): array
    {
        if (array_keys($snapshot) !== ['buckets', 'counts']
            || !is_array($snapshot['buckets']) || !is_array($snapshot['counts'])) {
            throw new InvalidArgumentException('Expected exact synthetic follow-up snapshot.');
        }

        $actual = FollowUpWorkloadSummary::summarize($snapshot['buckets']);
        if ($snapshot['counts'] !== $actual) {
            throw new InvalidArgumentException('Snapshot counts do not match bucket membership.');
        }

        $index = [];
        foreach (self::BUCKETS as $bucket) {
            foreach ($snapshot['buckets'][$bucket] as $id) {
                $index[$id] = $bucket;
            }
        }

        return $index;
    }
}
