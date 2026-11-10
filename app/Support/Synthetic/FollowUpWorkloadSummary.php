<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Inert summary of an already-classified, synthetic follow-up list.
 * The caller must perform all real-world authorization separately.
 */
final class FollowUpWorkloadSummary
{
    private const KEYS = ['overdue', 'due_24h', 'due_later'];

    /**
     * @param array{overdue:list<string>,due_24h:list<string>,due_later:list<string>} $buckets
     * @return array{overdue:int,due_24h:int,due_later:int,total:int}
     */
    public static function summarize(array $buckets): array
    {
        if (array_keys($buckets) !== self::KEYS) {
            throw new InvalidArgumentException('Expected exact ordered projection buckets.');
        }

        $seen = [];
        $counts = [];
        foreach (self::KEYS as $key) {
            if (!is_array($buckets[$key]) || !array_is_list($buckets[$key])) {
                throw new InvalidArgumentException('Expected list of synthetic identifiers.');
            }
            foreach ($buckets[$key] as $id) {
                if (!is_string($id) || trim($id) === '' || isset($seen[$id])) {
                    throw new InvalidArgumentException('Invalid or repeated synthetic identifier.');
                }
                $seen[$id] = true;
            }
            $counts[$key] = count($buckets[$key]);
        }

        return [
            'overdue' => $counts['overdue'],
            'due_24h' => $counts['due_24h'],
            'due_later' => $counts['due_later'],
            'total' => count($seen),
        ];
    }
}
