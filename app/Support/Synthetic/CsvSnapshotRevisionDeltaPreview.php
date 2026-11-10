<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * Compare two already-split, entirely invented CSV fixture revisions.
 *
 * Diff positions are NOT stable lead identities. The output contains only
 * relative positions/counts; it does not disclose any header, cell, target,
 * fingerprint or approval, and performs no I/O or business action.
 */
final class CsvSnapshotRevisionDeltaPreview
{
    /**
     * @param list<list<string>> $beforeRows
     * @param list<array{column:int,target:string}> $beforeMapping
     * @param list<list<string>> $afterRows
     * @param list<array{column:int,target:string}> $afterMapping
     * @return array{
     *   same_snapshot:bool,review_invalidated:bool,
     *   before_row_count:int,after_row_count:int,
     *   changed_row_positions:list<int>,added_row_positions:list<int>,
     *   removed_row_positions:list<int>,
     *   before_mapping_count:int,after_mapping_count:int,
     *   changed_mapping_positions:list<int>,added_mapping_positions:list<int>,
     *   removed_mapping_positions:list<int>,
     *   human_verified:bool,review_required:bool,import_permitted:bool
     * }
     */
    public static function compare(
        array $beforeRows,
        array $beforeMapping,
        array $afterRows,
        array $afterMapping,
    ): array {
        // Validate both inputs through all existing fixture-only bounds,
        // including rejection of operational mapping field names.
        $beforeFingerprint = CsvReviewSnapshotPreview::fingerprint($beforeRows, $beforeMapping);
        $afterFingerprint = CsvReviewSnapshotPreview::fingerprint($afterRows, $afterMapping);

        $changedRows = [];
        $commonRows = min(count($beforeRows), count($afterRows));
        for ($i = 0; $i < $commonRows; ++$i) {
            if ($beforeRows[$i] !== $afterRows[$i]) {
                $changedRows[] = $i + 1;
            }
        }

        $changedMappings = [];
        $commonMappings = min(count($beforeMapping), count($afterMapping));
        for ($i = 0; $i < $commonMappings; ++$i) {
            if ($beforeMapping[$i]['column'] !== $afterMapping[$i]['column']
                || $beforeMapping[$i]['target'] !== $afterMapping[$i]['target']) {
                $changedMappings[] = $i + 1;
            }
        }

        $same = hash_equals($beforeFingerprint, $afterFingerprint);

        return [
            'same_snapshot' => $same,
            'review_invalidated' => !$same,
            'before_row_count' => count($beforeRows),
            'after_row_count' => count($afterRows),
            'changed_row_positions' => $changedRows,
            'added_row_positions' => self::trailingPositions(count($beforeRows), count($afterRows)),
            'removed_row_positions' => self::trailingPositions(count($afterRows), count($beforeRows)),
            'before_mapping_count' => count($beforeMapping),
            'after_mapping_count' => count($afterMapping),
            'changed_mapping_positions' => $changedMappings,
            'added_mapping_positions' => self::trailingPositions(count($beforeMapping), count($afterMapping)),
            'removed_mapping_positions' => self::trailingPositions(count($afterMapping), count($beforeMapping)),
            // An unchanged public digest never attests a human or grants import.
            'human_verified' => false,
            'review_required' => true,
            'import_permitted' => false,
        ];
    }

    /** @return list<int> */
    private static function trailingPositions(int $beforeCount, int $afterCount): array
    {
        return $afterCount > $beforeCount
            ? range($beforeCount + 1, $afterCount)
            : [];
    }
}
