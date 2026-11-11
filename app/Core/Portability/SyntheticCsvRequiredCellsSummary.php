<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * F3-69: one-based, position-only required-cell diagnostics for SYNTHETIC CSV.
 *
 * This does NOT declare real mandatory CRM fields or authorize a write.
 * Original header names, row contents, hashes and contact data are absent.
 */
final readonly class SyntheticCsvRequiredCellsSummary
{
    /**
     * @param list<int> $requiredSourcePositions
     * @param list<int> $missingRequiredTargetPositions
     * @param list<array{row: int, column: int}> $emptyRequiredCellPositions
     */
    public function __construct(
        public SyntheticImportPreviewSummary $csvPreview,
        public array $requiredSourcePositions,
        public array $missingRequiredTargetPositions,
        public array $emptyRequiredCellPositions,
    ) {}

    public function hasMissingRequiredHeaders(): bool
    {
        return $this->missingRequiredTargetPositions !== [];
    }

    public function hasEmptyRequiredCells(): bool
    {
        return $this->emptyRequiredCellPositions !== [];
    }

    public function canImport(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }
}
