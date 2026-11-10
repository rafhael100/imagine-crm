<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * Structural inspection of an already split, invented CSV-like matrix.
 *
 * This is not an importer, file reader, schema mapping, authorization layer
 * or a decision to coerce or discard data. Headers and values are never
 * returned. Actual CSV encoding/dialect/normalization rules remain open.
 */
final class CsvStructurePreview
{
    /**
     * @param list<list<string>> $rows
     * @return array{
     *   row_count:int,data_row_count:int,header_columns:int,header_missing:bool,
     *   ragged_data_rows:list<int>,empty_header_columns:list<int>,
     *   duplicate_header_groups:list<list<int>>,
     *   flagged_cell_count:int,
     *   cell_findings:list<array{row:int,column:int,code:string}>
     * }
     */
    public static function summarize(array $rows): array
    {
        // Reuse all strict fixture bounds and cell inspections. No file IO.
        $cellPreview = CsvCellRiskPreview::inspect($rows);

        $header = $rows[0] ?? [];
        $columns = count($header);
        $ragged = [];
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex > 0 && count($row) !== $columns) {
                $ragged[] = $rowIndex + 1;
            }
        }

        $empty = [];
        foreach ($header as $index => $value) {
            // Only detect a *byte-exact empty string*. No trim/case folding,
            // Unicode normalization or policy for required CRM fields.
            if ($value === '') {
                $empty[] = $index + 1;
            }
        }

        $duplicates = [];
        $seen = [];
        foreach ($header as $i => $value) {
            if (isset($seen[$i])) {
                continue;
            }
            $positions = [$i + 1];
            foreach ($header as $j => $candidate) {
                if ($j > $i && $candidate === $value) {
                    $positions[] = $j + 1;
                    $seen[$j] = true;
                }
            }
            if (count($positions) > 1) {
                $duplicates[] = $positions;
            }
        }

        return [
            'row_count' => count($rows),
            'data_row_count' => max(0, count($rows) - 1),
            'header_columns' => $columns,
            'header_missing' => $columns === 0,
            'ragged_data_rows' => $ragged,
            'empty_header_columns' => $empty,
            'duplicate_header_groups' => $duplicates,
            'flagged_cell_count' => $cellPreview['flagged_count'],
            'cell_findings' => $cellPreview['findings'],
        ];
    }
}
