<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-211 / IN-118: pure, bounded column selection/reordering for an
 * untrusted in-memory table. No schema, tenant, identity, access, consent,
 * deduplication, merge, file, DB, API or persistence is inferred.
 *
 * Positions are explicit zero-based integers. There is no fuzzy name
 * matching or automatic repair of collisions.
 */
final class PortableCsvColumnProjection
{
    private const MAX_ROWS = 100;
    private const MAX_COLUMNS = 32;
    private const MAX_CELLS = 3200;
    private const MAX_CELL_BYTES = 4096;
    private const MAX_TOTAL_BYTES = 262144;

    /**
     * @param mixed $table Rectangular list<list<string>>, already in memory
     * @param mixed $positions Unique ordered list of zero-based source indices
     * @param mixed $hasHeader Explicit caller claim, not verified semantics
     * @return array<string,mixed> Projected untrusted values and local counts
     */
    public function project(mixed $table, mixed $positions, mixed $hasHeader = false): array
    {
        if (!is_array($table) || !array_is_list($table) || $table === []
            || count($table) > self::MAX_ROWS
            || !is_bool($hasHeader)
            || !is_array($positions) || !array_is_list($positions)
            || $positions === [] || count($positions) > self::MAX_COLUMNS) {
            self::invalid();
        }

        $width = null;
        $cells = 0;
        $bytes = 0;
        foreach ($table as $row) {
            if (!is_array($row) || !array_is_list($row) || $row === []
                || count($row) > self::MAX_COLUMNS) {
                self::invalid();
            }
            $width ??= count($row);
            if (count($row) !== $width) {
                self::invalid();
            }
            foreach ($row as $value) {
                if (!is_string($value) || str_contains($value, "\0")
                    || strlen($value) > self::MAX_CELL_BYTES
                    || preg_match('//u', $value) !== 1
                    || ++$cells > self::MAX_CELLS) {
                    self::invalid();
                }
                $bytes += strlen($value);
                if ($bytes > self::MAX_TOTAL_BYTES) {
                    self::invalid();
                }
            }
        }

        $seen = [];
        foreach ($positions as $position) {
            if (!is_int($position) || $position < 0
                || $position >= $width || isset($seen[$position])) {
                self::invalid();
            }
            $seen[$position] = true;
        }

        $selected = [];
        foreach ($table as $row) {
            $projected = [];
            foreach ($positions as $position) {
                $projected[] = $row[$position];
            }
            $selected[] = $projected;
        }

        // Header detection is not automatic: a claimed header is returned
        // separately so it cannot silently become a customer record.
        $header = $hasHeader ? array_shift($selected) : null;

        return [
            'source_column_count' => $width,
            'selected_column_count' => count($positions),
            'source_record_count' => count($table),
            'data_row_count' => count($selected),
            'header_claimed' => $hasHeader,
            'untrusted_header' => $header,
            'untrusted_data_rows' => $selected,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Projecao CSV em memoria invalida.');
    }
}
