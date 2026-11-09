<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Fixed-code, aggregate-only review of a synthetic preview.
 * No schema, identifiers, source labels, cell values, or rights.
 */
final readonly class SyntheticImportReviewSummary
{
    /**
     * @param list<string> $findings Closed catalog of descriptive finding codes
     */
    public function __construct(
        public int $rowsInspected,
        public int $mappedColumns,
        public int $unmappedSourceColumns,
        public int $unmappedTargetColumns,
        public int $formulaWarnings,
        public int $emptyCells,
        public array $findings,
    ) {}

    public function needsReview(): bool
    {
        return $this->findings !== [];
    }

    public function canImport(): bool
    {
        // Neither a clean preview nor an empty finding list proves authority.
        return false;
    }
}
