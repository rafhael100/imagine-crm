<?php

declare(strict_types=1);

namespace App\Core\Portability;

use App\Core\Authorization\AuthorizationDecision;

/**
 * Metadata-only diagnostic: neither complete mapping nor an identical tenant
 * string can grant import or export. No CSV contents, headers or PII stored.
 */
final readonly class SyntheticCsvImportPreflightSummary
{
    public function __construct(
        public SyntheticImportMappedPreviewSummary $preview,
        public AuthorizationDecision $decision,
        public ?SyntheticCsvRowRepeatSummary $repeatPreview = null,
    ) {}

    public function hasRepeatWarnings(): bool
    {
        return $this->repeatPreview?->hasRepeatedRows() ?? false;
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
