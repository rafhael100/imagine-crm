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
    private const MAX_SYNTHETIC_ITEMS = 100;

    /**
     * @param list<array{id:string,due_at:string}> $items
     * @return array{overdue:list<string>,due_24h:list<string>,due_later:list<string>}
     */
    public static function project(array $items, string $asOf): array
    {
        if (!array_is_list($items) || count($items) > self::MAX_SYNTHETIC_ITEMS) {
            throw new InvalidArgumentException('Synthetic follow-ups require a bounded list.');
        }

        $now = self::parseInstant($asOf);
        $buckets = array_fill_keys(self::BUCKETS, []);
        $cutoff = $now->modify('+24 hours');
        $ids = [];

        foreach ($items as $item) {
            if (!is_array($item) || count($item) !== 2
                || !isset($item['id'], $item['due_at'])
                || !is_string($item['id']) || trim($item['id']) === ''
                || !is_string($item['due_at'])) {
                throw new InvalidArgumentException('Synthetic follow-up requires id and due_at.');
            }
            if (isset($ids[$item['id']])) {
                throw new InvalidArgumentException('Duplicate synthetic follow-up id.');
            }
            $ids[$item['id']] = true;

            $due = self::parseInstant($item['due_at']);
            $bucket = $due < $now ? 'overdue' : ($due <= $cutoff ? 'due_24h' : 'due_later');
            $buckets[$bucket][] = $item['id'];
        }

        return $buckets;
    }

    private static function parseInstant(string $value): DateTimeImmutable
    {
        // Explicit offset prevents implicit system timezone and DST ambiguity.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', $value)) {
            throw new InvalidArgumentException('Expected ISO-8601 timestamp with explicit offset.');
        }
        try {
            $date = new DateTimeImmutable($value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                throw new InvalidArgumentException('Invalid calendar date or timezone offset.');
            }
        } catch (\Exception $exception) {
            throw new InvalidArgumentException('Invalid timestamp.', 0, $exception);
        }
        return $date->setTimezone(new DateTimeZone('UTC'));
    }
}
