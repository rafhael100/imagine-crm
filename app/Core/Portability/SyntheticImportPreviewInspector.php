<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * Bounded preview of already-decoded SYNTHETIC rows only.
 *
 * The caller supplies a list of column labels and a rectangular list of
 * string cells. There is no file ingestion, CSV/XLSX parsing, automatic
 * normalization, contact deduplication, tenant resolution, permission check
 * or persistence. Output contains only counts and one-based cell positions.
 *
 * No real data or untrusted uploads may be routed through this lab class.
 */
final class SyntheticImportPreviewInspector
{
    private const MAX_COLUMNS = 8;
    private const MAX_ROWS = 50;
    private const MAX_CELL_BYTES = 512;

    /**
     * @param array<mixed> $columns
     * @param array<mixed> $rows
     */
    public function inspect(array $columns, array $rows): SyntheticImportPreviewSummary
    {
        $columnCount = count($columns);

        if (!array_is_list($columns) || $columnCount < 1 || $columnCount > self::MAX_COLUMNS
            || !array_is_list($rows) || count($rows) > self::MAX_ROWS) {
            self::invalid();
        }

        $seen = [];
        foreach ($columns as $label) {
            if (!is_string($label) || !preg_match('/\A[a-z][a-z0-9_]{0,31}\z/D', $label)
                || isset($seen[$label])) {
                self::invalid();
            }
            $seen[$label] = true;
        }

        $emptyCells = 0;
        $potentialFormulaPositions = [];

        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row) || !array_is_list($row) || count($row) !== $columnCount) {
                self::invalid();
            }

            foreach ($row as $columnIndex => $cell) {
                if (!is_string($cell) || strlen($cell) > self::MAX_CELL_BYTES) {
                    self::invalid();
                }

                if (trim($cell) === '') {
                    $emptyCells++;
                }

                if (SpreadsheetTextRiskGuard::isPotentiallyExecutable($cell)) {
                    $potentialFormulaPositions[] = [
                        'row' => $rowIndex + 1,
                        'column' => $columnIndex + 1,
                    ];
                }
            }
        }

        return new SyntheticImportPreviewSummary(
            rowsInspected: count($rows),
            columnsInspected: $columnCount,
            emptyCells: $emptyCells,
            potentialFormulaPositions: $potentialFormulaPositions,
        );
    }

    private static function invalid(): never
    {
        // Never echo a spreadsheet cell, header, or upload content.
        throw new InvalidArgumentException('Estrutura de pre-visualizacao invalida.');
    }
}
