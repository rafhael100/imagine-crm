<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Pure offline projection for synthetic follow-up due dates.
 *
 * No database access, authorization decisions, notifications or side effects.
 * All due dates and the reference clock must be ISO-8601 strings with explicit
 * offsets. An absent or malformed timestamp is rejected, never guessed.
 */
final class FollowUpAgingProjection
{
    private const BUCKETS = ['overdue', 'due_24h', 'due_later'];

    /**
     * @param list<array{id:string,due_at:string}> $items
     * @return array{overdue:list<string>,due_24h:list<string>,due_later:list<string>}
     */
    public static function project(array $items, string $asOf): array
    {
        $now = self::parseInstant($asOf);
        $buckets = array_fill_keys(self::BUCKETS, []);
        $ids = [];

        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['id'], $item['due_at'])
                || !is_string($item['id']) || trim($item['id']) === ''
                || !is_string($item['due_at'])) {
                throw new InvalidArgumentException('Synthetic follow-up requires id and due_at.');
            }
            if (isset($ids[$item['id']])) {
                throw new InvalidArgumentException('Duplicate synthetic follow-up id.');
            }
            $ids[$item['id']] = true;

            $due = self::parseInstant($item['due_at']);
            $seconds = $due->getTimestamp() - $now->getTimestamp();
            $bucket = $seconds < 0 ? 'overdue' : ($seconds <= 86400 ? 'due_24h' : 'due_later');
            $buckets[$bucket][] = $item['id'];
        }

        return $buckets;
    }

    private static function parseInstant(string $value): DateTimeImmutable
    {
        // Explicit offset prevents implicit system timezone and DST ambiguity.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $value)) {
            throw new InvalidArgumentException('Expected ISO-8601 timestamp with explicit offset.');
        }
        try {
            $date = new DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new InvalidArgumentException('Invalid timestamp.', 0, $exception);
        }
        return $date->setTimezone(new DateTimeZone('UTC'));
    }
}
