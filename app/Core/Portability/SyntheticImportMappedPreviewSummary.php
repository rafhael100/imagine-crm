<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * Read-only, aggregate-only result of a synthetic CSV + mapping preflight.
 *
 * Both nested summaries contain counts/positions, never original fields
 * or cell contents. This object never grants an import or export operation.
 */
final readonly class SyntheticImportMappedPreviewSummary
{
    public function __construct(
        public SyntheticImportPreviewSummary $csvPreview,
        public SyntheticColumnMappingSummary $mappingPreview,
    ) {}

    public function hasFormulaWarnings(): bool
    {
        return $this->csvPreview->potentiallyExecutableCellCount() > 0;
    }

    public function isMappingComplete(): bool
    {
        return $this->mappingPreview->isCompleteOneToOne();
    }

    public function canImport(): bool
    {
        // A preview has no identity, tenant, ownership or write authority.
        return false;
    }
}
