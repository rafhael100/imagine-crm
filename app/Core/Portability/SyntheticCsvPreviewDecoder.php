<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * Bounded in-memory decoding of SYNTHETIC UTF-8 CSV text for offline preview.
 *
 * No uploads, disk files, network, persistence, identity/membership checks,
 * import decisions or content-bearing return values are provided.
 *
 * F3-20 is a laboratory decoder, NOT a public CSV import endpoint.
 */
final class SyntheticCsvPreviewDecoder
{
    private const MAX_CSV_BYTES = 32768;
    private const MAX_ROWS = 50;
    private const MAX_COLUMNS = 8;

    public function preview(string $syntheticCsv): SyntheticImportPreviewSummary
    {
        return $this->decode($syntheticCsv)[0];
    }

    /**
     * Combine the EXACT header of the decoded synthetic CSV with a
     * position-only mapping in the same bounded parse, without exposing cells.
     *
     * @param array<mixed> $targetLabels Synthetic technical field names
     * @param array<mixed> $mappingPairs One-based source/target positions
     */
    public function previewMapped(
        string $syntheticCsv,
        array $targetLabels,
        array $mappingPairs,
    ): SyntheticImportMappedPreviewSummary {
        [$csvPreview, $sourceLabels] = $this->decode($syntheticCsv);

        try {
            $mappingPreview = (new SyntheticColumnMappingInspector())->assess(
                $sourceLabels,
                $targetLabels,
                $mappingPairs,
            );
        } catch (InvalidArgumentException) {
            // The caller cannot use failures to enumerate header values.
            self::invalid();
        }

        return new SyntheticImportMappedPreviewSummary($csvPreview, $mappingPreview);
    }

    /**
     * @return array{SyntheticImportPreviewSummary, list<string>}
     */
    private function decode(string $syntheticCsv): array
    {
        if ($syntheticCsv === ''
            || strlen($syntheticCsv) > self::MAX_CSV_BYTES
            || str_contains($syntheticCsv, "\0")
            || preg_match('//u', $syntheticCsv) !== 1) {
            self::invalid();
        }

        // The maximum byte count is far lower than php://temp's threshold,
        // so the entire fixture remains in memory, not a filesystem upload.
        $handle = fopen('php://temp/maxmemory:65536', 'w+');
        if ($handle === false) {
            self::invalid();
        }

        try {
            $written = fwrite($handle, $syntheticCsv);
            if ($written !== strlen($syntheticCsv) || rewind($handle) !== true) {
                self::invalid();
            }

            $columns = fgetcsv($handle, 0, ',', '"', '');
            if ($columns === false || $columns === [null] || count($columns) > self::MAX_COLUMNS) {
                self::invalid();
            }

            $rows = [];
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($row === [null]
                    || count($row) !== count($columns)
                    || count($rows) >= self::MAX_ROWS) {
                    self::invalid();
                }

                // Do not normalize, trim, convert encoding, deduplicate or
                // silently repair any user-looking values in this laboratory.
                $rows[] = $row;
            }

            // Reuse the existing conservative cell/formula check and
            // aggregate-only result; raw contents never escape the method.
            try {
                $summary = (new SyntheticImportPreviewInspector())->inspect($columns, $rows);
                return [$summary, $columns];
            } catch (InvalidArgumentException) {
                // Maintain a single generic CSV-facing error contract;
                // never expose headers, CSV content or validation details.
                self::invalid();
            }
        } finally {
            fclose($handle);
        }
    }

    private static function invalid(): never
    {
        // Never reflect a header, CSV cell or malformed row into a log/error.
        throw new InvalidArgumentException('Pre-visualizacao CSV sintetica invalida.');
    }
}
