<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Bind an untrusted, synthetic CSV review to the exact bytes and exact order
 * of one invented matrix and mapping.
 *
 * A public checksum is neither an authenticated human signature nor a
 * durable/immutable storage snapshot. It must NEVER authorize an import.
 */
final class CsvReviewSnapshotPreview
{
    private const VERSION = 'fixture-sha256-v1:';

    /**
     * @param list<list<string>> $rows Already split, entirely invented cells.
     * @param list<array{column:int,target:string}> $mapping Fixture-only column assignments.
     */
    public static function fingerprint(array $rows, array $mapping = []): string
    {
        // Reuse all existing bounds and reject non-fixture destinations
        // before hashing. The hash is NOT a substitute for this validation.
        CsvRowReviewPreview::inspect($rows, $mapping);

        $ctx = hash_init('sha256');
        hash_update($ctx, "imagine-crm:csv-review:synthetic:v1\x00");
        hash_update($ctx, pack('N', count($rows)));

        foreach ($rows as $row) {
            hash_update($ctx, pack('N', count($row)));
            foreach ($row as $cell) {
                self::putString($ctx, $cell);
            }
        }

        // Order is significant: the list of assignments is not a map.
        hash_update($ctx, pack('N', count($mapping)));
        foreach ($mapping as $assignment) {
            hash_update($ctx, pack('N', $assignment['column']));
            self::putString($ctx, $assignment['target']);
        }

        return self::VERSION.hash_final($ctx);
    }

    /**
     * Compare the expected simulated batch version BEFORE interpreting
     * simulated decisions. A stale marker throws and returns no report.
     *
     * @param list<list<string>> $rows
     * @param list<array{column:int,target:string}> $mapping
     * @param list<array{row:int,status:string}> $decisions
     * @return array<string, mixed>
     */
    public static function reconcile(
        array $rows,
        array $mapping,
        string $expectedFingerprint,
        array $decisions = [],
    ): array {
        if (preg_match('/\Afixture-sha256-v1:[0-9a-f]{64}\z/D', $expectedFingerprint) !== 1) {
            throw new InvalidArgumentException('Invalid synthetic snapshot marker.');
        }

        $current = self::fingerprint($rows, $mapping);
        if (!hash_equals($current, $expectedFingerprint)) {
            throw new InvalidArgumentException('Synthetic batch changed; review decisions are stale.');
        }

        // All existing explicit fail-closed decisions still apply, and
        // human_verified/import_permitted remain false even on a match.
        return [
            'snapshot_version' => 'fixture-sha256-v1',
            'snapshot_matches' => true,
            ...CsvRowDispositionPreview::summarize($rows, $mapping, $decisions),
        ];
    }

    /** @param \HashContext $context */
    private static function putString(\HashContext $context, string $value): void
    {
        // A length prefix makes embedded nulls, delimiters, non-UTF8 bytes,
        // and otherwise ambiguous concatenations unambiguous.
        hash_update($context, pack('N', strlen($value)));
        hash_update($context, $value);
    }
}
