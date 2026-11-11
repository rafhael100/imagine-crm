<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-55 compatibility facade for the original offline synthetic CSV preview.
 *
 * The ONE strict RFC-style in-memory parser is SyntheticCsvImportPreview.
 * Existing F3-20/F3-21 consumers preserve this API and their generic
 * fail-closed exception, including previewMapped's position-only mapping.
 * No file upload, I/O, real import, ACL, customer record or return of cells.
 */
final class SyntheticCsvPreviewDecoder
{
    public function preview(string $syntheticCsv): SyntheticImportPreviewSummary
    {
        try {
            return (new SyntheticCsvImportPreview())->inspect($syntheticCsv);
        } catch (InvalidArgumentException) {
            self::invalid();
        }
    }

    /**
     * @param array<mixed> $targetLabels
     * @param array<mixed> $mappingPairs
     */
    public function previewMapped(
        string $syntheticCsv,
        array $targetLabels,
        array $mappingPairs,
    ): SyntheticImportMappedPreviewSummary {
        try {
            return (new SyntheticCsvImportPreview())->inspectMapped(
                $syntheticCsv, $targetLabels, $mappingPairs,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }
    }

    /**
     * Suggest positions for exact technical labels without writing anything.
     *
     * @param array<mixed> $targetLabels
     */
    public function previewSuggested(
        string $syntheticCsv,
        array $targetLabels,
    ): SyntheticImportMappedPreviewSummary {
        try {
            return (new SyntheticCsvImportPreview())->inspectSuggested(
                $syntheticCsv, $targetLabels,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }
    }

    private static function invalid(): never
    {
        // Do not reveal client-like fields, cell contents or validation hints.
        throw new InvalidArgumentException('Pre-visualizacao CSV sintetica invalida.');
    }
}
