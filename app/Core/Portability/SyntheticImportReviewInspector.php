<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-23: validates consistency of an UNTRUSTED synthetic preview summary
 * and derives closed-catalog review findings. No I/O or authorization.
 *
 * A caller can construct public readonly summaries with forged numbers, so
 * even their internal consistency must not be assumed. This is still not
 * proof that the source CSV, tenant or membership is trustworthy.
 */
final class SyntheticImportReviewInspector
{
    private const MAX_ROWS = 50;
    private const MAX_COLUMNS = 8;

    public function assess(SyntheticImportMappedPreviewSummary $preview): SyntheticImportReviewSummary
    {
        $csv = $preview->csvPreview;
        $mapping = $preview->mappingPreview;

        $rows = $csv->rowsInspected;
        $columns = $csv->columnsInspected;
        $targets = $mapping->targetColumns;

        if ($rows < 0 || $rows > self::MAX_ROWS
            || $columns < 1 || $columns > self::MAX_COLUMNS
            || $mapping->sourceColumns !== $columns
            || $targets < 1 || $targets > self::MAX_COLUMNS
            || $csv->emptyCells < 0 || $csv->emptyCells > $rows * $columns) {
            self::invalid();
        }

        if (!array_is_list($csv->potentialFormulaPositions)
            || count($csv->potentialFormulaPositions) > $rows * $columns) {
            self::invalid();
        }

        $formulaSeen = [];
        foreach ($csv->potentialFormulaPositions as $position) {
            if (!is_array($position)
                || array_keys($position) !== ['row', 'column']
                || !is_int($position['row'])
                || !is_int($position['column'])
                || $position['row'] < 1 || $position['row'] > $rows
                || $position['column'] < 1 || $position['column'] > $columns) {
                self::invalid();
            }

            $key = $position['row'].':'.$position['column'];
            if (isset($formulaSeen[$key])) {
                self::invalid();
            }
            $formulaSeen[$key] = true;
        }

        if (!array_is_list($mapping->positions)
            || count($mapping->positions) < 1
            || count($mapping->positions) > min($columns, $targets)) {
            self::invalid();
        }

        $sourceUsed = [];
        $targetUsed = [];
        foreach ($mapping->positions as $pair) {
            if (!is_array($pair)
                || array_keys($pair) !== ['source', 'target']
                || !is_int($pair['source'])
                || !is_int($pair['target'])) {
                self::invalid();
            }

            $source = $pair['source'];
            $target = $pair['target'];
            if ($source < 1 || $source > $columns
                || $target < 1 || $target > $targets
                || isset($sourceUsed[$source]) || isset($targetUsed[$target])) {
                self::invalid();
            }

            $sourceUsed[$source] = true;
            $targetUsed[$target] = true;
        }

        $unmappedSources = self::checkUnmapped($mapping->unmappedSourcePositions, $columns, $sourceUsed);
        $unmappedTargets = self::checkUnmapped($mapping->unmappedTargetPositions, $targets, $targetUsed);

        // Codes are deliberately fixed and ordered; no dynamic labels,
        // warnings, raw CSV data, or tenant/user identifiers are reflected.
        $findings = [];
        if ($rows === 0) {
            $findings[] = 'EMPTY_DATASET';
        }
        if ($unmappedSources > 0) {
            $findings[] = 'UNMAPPED_SOURCE_COLUMNS';
        }
        if ($unmappedTargets > 0) {
            $findings[] = 'UNMAPPED_TARGET_COLUMNS';
        }
        if ($formulaSeen !== []) {
            $findings[] = 'POTENTIAL_FORMULA_CELLS';
        }
        if ($csv->emptyCells > 0) {
            $findings[] = 'EMPTY_CELLS';
        }

        return new SyntheticImportReviewSummary(
            rowsInspected: $rows,
            mappedColumns: count($mapping->positions),
            unmappedSourceColumns: $unmappedSources,
            unmappedTargetColumns: $unmappedTargets,
            formulaWarnings: count($formulaSeen),
            emptyCells: $csv->emptyCells,
            findings: $findings,
        );
    }

    /**
     * Verify that mapped and unmapped positions partition the entire axis.
     *
     * @param array<mixed> $unmapped
     * @param array<int, bool> $used
     */
    private static function checkUnmapped(array $unmapped, int $max, array $used): int
    {
        if (!array_is_list($unmapped) || count($unmapped) + count($used) !== $max) {
            self::invalid();
        }

        $seen = [];
        foreach ($unmapped as $index) {
            if (!is_int($index) || $index < 1 || $index > $max
                || isset($used[$index]) || isset($seen[$index])) {
                self::invalid();
            }
            $seen[$index] = true;
        }

        // The cardinality and disjointness checks imply complete coverage.
        return count($unmapped);
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Resumo sintetico de revisao invalido.');
    }
}
