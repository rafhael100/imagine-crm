<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Aggregate-only preview of a synthetic one-to-one mapping.
 * Numeric positions are one-based; neither labels nor cell values escape.
 */
final readonly class SyntheticColumnMappingSummary
{
    /**
     * @param list<array{source: int, target: int}> $positions
     * @param list<int> $unmappedSourcePositions
     * @param list<int> $unmappedTargetPositions
     */
    public function __construct(
        public int $sourceColumns,
        public int $targetColumns,
        public array $positions,
        public array $unmappedSourcePositions,
        public array $unmappedTargetPositions,
    ) {}

    public function mappedColumns(): int
    {
        return count($this->positions);
    }

    public function isCompleteOneToOne(): bool
    {
        return $this->unmappedSourcePositions === []
            && $this->unmappedTargetPositions === [];
    }

    public function canImport(): bool
    {
        // Mapping completeness is never a permission or import confirmation.
        return false;
    }
}
