<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;
use JsonException;

/**
 * Fixed-schema, compact JSON encoder for the exclusively synthetic CSV review.
 *
 * No raw CSV or caller-supplied fields are serialized: metadata is rebuilt and
 * validated by the V1 report before encoding. Not a public endpoint.
 */
final class SyntheticCsvReviewReportV1JsonEncoder
{
    public const MAX_JSON_BYTES = 32768;

    public function encode(SyntheticCsvImportPreflightSummary $preflight): string
    {
        $report = (new SyntheticCsvReviewReportV1())->build($preflight);

        try {
            $json = json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            // Never echo raw CSV, exception details or caller-supplied labels.
            throw new InvalidArgumentException('Relatorio CSV sintetico invalido.');
        }

        if (strlen($json) > self::MAX_JSON_BYTES) {
            throw new InvalidArgumentException('Relatorio CSV sintetico invalido.');
        }

        return $json;
    }

    public function canImport(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }

    public function canEnableImportRoute(): bool
    {
        return false;
    }
}
