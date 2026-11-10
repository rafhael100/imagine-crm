<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Offline follow-up worklist for invented, explicitly scoped fixtures.
 *
 * This does NOT fetch or authorize data, resolve a real tenant, verify a user,
 * grant access or establish a right to see IDs or dates. No runtime use.
 */
final class FollowUpFixtureWorklistPage
{
    private const BUCKETS = ['all', 'overdue', 'due_24h', 'due_later'];
    private const MAX_PAGE_SIZE = 25;

    /**
     * @param list<array{id:string,due_at:string,scope:string}> $items
     * @return array{
     *   bucket:string,page:int,page_size:int,pages:int,selected_total:int,
     *   has_previous:bool,has_next:bool,
     *   counts:array{overdue:int,due_24h:int,due_later:int,total:int},
     *   items:list<array{id:string,due_at:string,bucket:string}>
     * }
     */
    public static function paginate(
        string $fixtureScope,
        array $items,
        string $asOf,
        string $bucket = 'all',
        int $page = 1,
        int $pageSize = 10,
    ): array {
        if (!in_array($bucket, self::BUCKETS, true)
            || $page < 1 || $pageSize < 1 || $pageSize > self::MAX_PAGE_SIZE) {
            throw new InvalidArgumentException('Invalid synthetic worklist bucket or page parameters.');
        }

        // Strict scope and input-shape checks are re-used rather than
        // treating a caller-supplied scope as proof of legitimate access.
        $series = FollowUpFixtureScopeConsistency::project($fixtureScope, [[
            'at' => $asOf,
            'scope' => $fixtureScope,
            'items' => $items,
        ]]);

        $records = [];
        foreach ($items as $item) {
            $utc = (new DateTimeImmutable($item['due_at']))
                ->setTimezone(new DateTimeZone('UTC'));
            $records[$item['id']] = [
                'id' => $item['id'],
                'due_at' => $utc->format('Y-m-d\TH:i:s.u\Z'),
                'instant' => $utc,
            ];
        }

        // FollowUpAgingProjection already validated all dates and unique
        // identifiers through FollowUpFixtureScopeConsistency.
        $byBucket = FollowUpAgingProjection::project(
            array_map(
                static fn (array $item): array => [
                    'id' => $item['id'],
                    'due_at' => $item['due_at'],
                ],
                $items,
            ),
            $asOf,
        );

        $selected = [];
        foreach ($byBucket as $group => $ids) {
            if ($bucket !== 'all' && $bucket !== $group) {
                continue;
            }

            foreach ($ids as $id) {
                $record = $records[$id];
                $record['bucket'] = $group;
                $selected[] = $record;
            }
        }

        // Sorting is by absolute instant, NOT lexical timestamp offsets or
        // input order. An ID is only a deterministic tie-break for fixtures.
        usort($selected, static function (array $a, array $b): int {
            $byTime = $a['instant'] <=> $b['instant'];
            return $byTime !== 0 ? $byTime : strcmp($a['id'], $b['id']);
        });

        $selectedTotal = count($selected);
        $pages = max(1, intdiv($selectedTotal + $pageSize - 1, $pageSize));
        if ($page > $pages) {
            throw new InvalidArgumentException('Requested page exceeds the synthetic result window.');
        }

        $slice = array_slice($selected, ($page - 1) * $pageSize, $pageSize);
        $view = array_map(
            static fn (array $record): array => [
                'id' => $record['id'],
                'due_at' => $record['due_at'],
                'bucket' => $record['bucket'],
            ],
            $slice,
        );

        return [
            'bucket' => $bucket,
            'page' => $page,
            'page_size' => $pageSize,
            'pages' => $pages,
            'selected_total' => $selectedTotal,
            'has_previous' => $page > 1,
            'has_next' => $page < $pages,
            'counts' => $series[0]['counts'],
            'items' => $view,
        ];
    }
}
