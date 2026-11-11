<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * F3-66: aggregate-only structural row-repeat diagnostic.
 *
 * Neither a duplicate warning nor its absence authenticates an organization,
 * grants a write, deduplicates people or authorizes an import. No cell values,
 * identifiers, hashes, header labels or contact data are retained.
 */
final readonly class SyntheticCsvRowRepeatSummary
{
    /**
     * @param list<array{firstRow: int, repeatedRow: int}> $repeatedRowPositions
     */
    public function __construct(
        public SyntheticImportPreviewSummary $csvPreview,
        public array $repeatedRowPositions,
    ) {}

    public function repeatedRowCount(): int
    {
        return count($this->repeatedRowPositions);
    }

    public function hasRepeatedRows(): bool
    {
        return $this->repeatedRowPositions !== [];
    }

    public function canImport(): bool
    {
        return false;
    }

    public function canDeduplicateContacts(): bool
    {
        return false;
    }
}
