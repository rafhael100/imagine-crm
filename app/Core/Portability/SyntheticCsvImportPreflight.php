<?php

declare(strict_types=1);

namespace App\Core\Portability;

use App\Core\Tenancy\TenantContext;

/**
 * F3-57 — bounded synthetic CSV -> suggested positions -> mandatory DENY.
 *
 * All parameters are lab descriptions, NOT authenticated service identity,
 * tenant membership or user authorization. No routing, upload, SQL or writes.
 */
final class SyntheticCsvImportPreflight
{
    /**
     * @param array<mixed> $targetLabels Fictional technical field names.
     * @param mixed $targetTenantId Unverified claimed tenant.
     * @param mixed $requestedFields Unverified proposed target field names.
     */
    public function inspect(
        string $syntheticCsv,
        array $targetLabels,
        TenantContext $unverifiedContext,
        mixed $targetTenantId,
        mixed $requestedFields,
    ): SyntheticCsvImportPreflightSummary {
        // Decodes once, discards cell/header values; preview only counts/positions.
        $preview = (new SyntheticCsvImportPreview())->inspectSuggested(
            $syntheticCsv, $targetLabels,
        );

        // Do not permit the requested-field description to claim a target
        // column not present in the exact-match proposal (or an unmapped
        // column), even though the downstream denial is unconditional.
        $proposedFields = [];
        foreach ($preview->mappingPreview->positions as $pair) {
            $proposedFields[] = $targetLabels[$pair['target'] - 1];
        }

        $valid = is_array($requestedFields)
            && array_is_list($requestedFields)
            && count($requestedFields) >= 1
            && count($requestedFields) <= 8;
        if ($valid) {
            foreach ($requestedFields as $field) {
                if (!is_string($field)
                    || !in_array($field, $proposedFields, true)) {
                    $valid = false;
                    break;
                }
            }
            if ($valid && count(array_unique($requestedFields)) !== count($requestedFields)) {
                $valid = false;
            }
        }

        // An invalid field list forces CONTRACT_INVALID through the existing
        // denial gate. Matching names are never a grant and are not returned.
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            $unverifiedContext,
            $preview->csvPreview,
            $targetTenantId,
            'import', // fixed server-side lab operation; never an input grant.
            $valid ? $requestedFields : [],
        );

        return new SyntheticCsvImportPreflightSummary($preview, $decision);
    }
}
