<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Descriptive, read-only dry run for explicitly declared synthetic column
 * positions. NOT an importer, a CRM field catalog or an authorization check.
 *
 * All target names must begin with fixture_; names and cell contents are
 * never returned. Even a clean preview does not authorize any operation.
 */
final class CsvMappingPlanPreview
{
    /**
     * @param list<list<string>> $rows Invented matrix previously split by the caller.
     * @param list<array{column:int,target:string}> $mapping Proposed, explicit fixture-only destinations.
     * @return array{
     *   header_columns:int,data_rows:int,mapping_count:int,
     *   unmapped_columns:list<int>,duplicate_source_columns:list<int>,
     *   duplicate_target_mapping_positions:list<list<int>>,
     *   issue_codes:list<string>,cell_warning_count:int,
     *   review_required:bool
     * }
     */
    public static function inspect(array $rows, array $mapping): array
    {
        $structure = CsvStructurePreview::summarize($rows);

        if (!array_is_list($mapping) || count($mapping) > 25) {
            throw new InvalidArgumentException('Synthetic mapping must be a bounded list.');
        }

        $headerColumns = $structure['header_columns'];
        $byColumn = [];
        $byTarget = [];

        foreach ($mapping as $index => $assignment) {
            if (!is_array($assignment)
                || count($assignment) !== 2
                || !isset($assignment['column'], $assignment['target'])
                || !is_int($assignment['column'])
                || $assignment['column'] < 1
                || $assignment['column'] > $headerColumns
                || !is_string($assignment['target'])
                || preg_match('/\Afixture_[a-z][a-z0-9_]{0,31}\z/D', $assignment['target']) !== 1) {
                throw new InvalidArgumentException('Invalid fixture-only column mapping.');
            }

            $column = $assignment['column'];
            $target = $assignment['target'];
            $byColumn[$column] = ($byColumn[$column] ?? 0) + 1;
            $byTarget[$target][] = $index + 1;
        }

        $duplicates = [];
        foreach ($byColumn as $column => $count) {
            if ($count > 1) {
                $duplicates[] = (int) $column;
            }
        }
        sort($duplicates, SORT_NUMERIC);

        $duplicateTargetPositions = [];
        foreach ($byTarget as $positions) {
            if (count($positions) > 1) {
                $duplicateTargetPositions[] = $positions;
            }
        }

        $unmapped = [];
        for ($column = 1; $column <= $headerColumns; ++$column) {
            if (!isset($byColumn[$column])) {
                $unmapped[] = $column;
            }
        }

        // Deterministic, coarse diagnostics. Do not reveal header values,
        // intended field names, row contents or user-provided identifiers.
        $issueCodes = [];
        if ($structure['header_missing']) {
            $issueCodes[] = 'missing_header';
        }
        if ($structure['empty_header_columns'] !== []) {
            $issueCodes[] = 'empty_header';
        }
        if ($structure['duplicate_header_groups'] !== []) {
            $issueCodes[] = 'duplicate_header';
        }
        if ($structure['ragged_data_rows'] !== []) {
            $issueCodes[] = 'ragged_data_rows';
        }
        if ($structure['flagged_cell_count'] > 0) {
            $issueCodes[] = 'risky_cells';
        }
        if ($duplicates !== []) {
            $issueCodes[] = 'duplicate_source_mapping';
        }
        if ($duplicateTargetPositions !== []) {
            $issueCodes[] = 'duplicate_target_mapping';
        }

        return [
            'header_columns' => $headerColumns,
            'data_rows' => $structure['data_row_count'],
            'mapping_count' => count($mapping),
            'unmapped_columns' => $unmapped,
            'duplicate_source_columns' => $duplicates,
            'duplicate_target_mapping_positions' => $duplicateTargetPositions,
            'issue_codes' => $issueCodes,
            'cell_warning_count' => $structure['flagged_cell_count'],
            // ALWAYS true. A synthetic preview cannot approve import/export.
            'review_required' => true,
        ];
    }
}
