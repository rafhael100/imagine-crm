<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Strictly checks caller-supplied fixture tags before the offline projection.
 *
 * THIS IS NOT TENANT AUTHORIZATION. Tags are freely asserted by the caller;
 * the method does not prove authenticated identity, membership, ownership,
 * entitlement, data provenance or RLS. Never expose this through runtime.
 */
final class FollowUpFixtureScopeConsistency
{
    private const MAX_OBSERVATIONS = 31;
    private const MAX_ITEMS_PER_OBSERVATION = 100;

    /**
     * @param list<array{at:string,scope:string,items:list<array{id:string,due_at:string,scope:string}>}> $observations
     * @return list<array{at:string,counts:array,delta:?array,continuity:?array}>
     */
    public static function project(string $fixtureScope, array $observations): array
    {
        if (!preg_match('/^fixture-[a-z0-9][a-z0-9_-]{0,39}$/D', $fixtureScope)) {
            throw new InvalidArgumentException('Only named synthetic fixture scopes are supported.');
        }
        if (!array_is_list($observations) || count($observations) > self::MAX_OBSERVATIONS) {
            throw new InvalidArgumentException('Synthetic fixture observations must be a bounded list.');
        }

        $normalized = [];

        foreach ($observations as $observation) {
            if (!is_array($observation) || count($observation) !== 3
                || !isset($observation['at'], $observation['scope'], $observation['items'])
                || !is_string($observation['at'])
                || !is_string($observation['scope'])
                || $observation['scope'] !== $fixtureScope
                || !is_array($observation['items'])
                || !array_is_list($observation['items'])
                || count($observation['items']) > self::MAX_ITEMS_PER_OBSERVATION) {
                throw new InvalidArgumentException('Invalid or mismatched synthetic observation scope.');
            }

            $normalizedItems = [];
            foreach ($observation['items'] as $item) {
                if (!is_array($item) || count($item) !== 3
                    || !isset($item['id'], $item['due_at'], $item['scope'])
                    || !is_string($item['id'])
                    || !is_string($item['due_at'])
                    || !is_string($item['scope'])
                    || $item['scope'] !== $fixtureScope) {
                    throw new InvalidArgumentException('Invalid or mismatched synthetic record scope.');
                }

                $normalizedItems[] = [
                    'id' => $item['id'],
                    'due_at' => $item['due_at'],
                ];
            }

            $normalized[] = [
                'at' => $observation['at'],
                'items' => $normalizedItems,
            ];
        }

        // Reuses existing timestamp, uniqueness, count and transition
        // validation after removing fixture-only scope labels.
        return FollowUpObservationSeries::project($normalized);
    }
}
