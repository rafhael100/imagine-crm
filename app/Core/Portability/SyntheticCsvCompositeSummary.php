<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * F3-71: metadata-only aggregate of one canonical synthetic CSV decode.
 *
 * The three diagnostics refer to the SAME decoded 32 KiB-bounded snapshot.
 * No CSV cells, headers or hashes survive the call and none grants a write.
 */
final readonly class SyntheticCsvCompositeSummary
{
    public function __construct(
        public SyntheticImportMappedPreviewSummary $mappedPreview,
        public SyntheticCsvRowRepeatSummary $repeatPreview,
        public ?SyntheticCsvRequiredCellsSummary $requiredCellsPreview,
    ) {}

    public function canImport(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }
}
