<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Aggregate-only result from a bounded, offline import preview.
 *
 * No original cell values or header labels are retained in this result.
 * This is deliberately NOT a decision to import, persist or grant access.
 */
final readonly class SyntheticImportPreviewSummary
{
    /**
     * @param list<array{row: int, column: int}> $potentialFormulaPositions
     */
    public function __construct(
        public int $rowsInspected,
        public int $columnsInspected,
        public int $emptyCells,
        public array $potentialFormulaPositions,
    ) {}

    public function potentiallyExecutableCellCount(): int
    {
        return count($this->potentialFormulaPositions);
    }

    public function canImport(): bool
    {
        // Never convert an offline presentation result into write authority.
        return false;
    }
}
