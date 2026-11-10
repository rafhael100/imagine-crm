<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Bounded and deterministic synthetic follow-up trend projection.
 *
 * No storage, real customer information, authorization or business actions.
 */
final class FollowUpTimeline
{
    /**
     * @param list<array{at:string,snapshot:array}> $observations
     * @return list<array{at:string,counts:array,delta:?array,continuity:?array}>
     */
    public static function build(array $observations): array
    {
        if (!array_is_list($observations) || count($observations) > 31) {
            throw new InvalidArgumentException('Expected a bounded list of at most 31 observations.');
        }

        $result = [];
        $lastInstant = null;
        $lastSnapshot = null;

        foreach ($observations as $item) {
            if (!is_array($item) || array_keys($item) !== ['at', 'snapshot']
                || !is_string($item['at']) || !is_array($item['snapshot'])) {
                throw new InvalidArgumentException('Invalid synthetic observation shape.');
            }

            $instant = self::parseInstant($item['at']);
            if ($lastInstant !== null && $instant <= $lastInstant) {
                throw new InvalidArgumentException('Observation times must be strictly increasing.');
            }

            $snapshot = $item['snapshot'];
            if (array_keys($snapshot) !== ['buckets', 'counts']
                || !is_array($snapshot['buckets']) || !is_array($snapshot['counts'])
                || $snapshot['counts'] !== FollowUpWorkloadSummary::summarize($snapshot['buckets'])) {
                throw new InvalidArgumentException('Invalid synthetic snapshot.');
            }

            $comparison = $lastSnapshot === null
                ? null
                : FollowUpAggregateReport::build($lastSnapshot, $snapshot);

            $result[] = [
                'at' => $instant->format('Y-m-d\TH:i:s.u\Z'),
                'counts' => $snapshot['counts'],
                'delta' => $comparison['delta'] ?? null,
                'continuity' => $comparison['continuity'] ?? null,
            ];

            $lastInstant = $instant;
            $lastSnapshot = $snapshot;
        }

        return $result;
    }

    private static function parseInstant(string $raw): DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', $raw)) {
            throw new InvalidArgumentException('Observation timestamp requires an ISO-8601 offset.');
        }

        try {
            $date = new DateTimeImmutable($raw);
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                throw new InvalidArgumentException('Invalid observation timestamp.');
            }
            return $date->setTimezone(new DateTimeZone('UTC'));
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid observation timestamp.', 0, $e);
        }
    }
}
