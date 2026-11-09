<?php

declare(strict_types=1);

namespace App\Core\Portability;

use App\Core\Authorization\AuthorizationDecision;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;

/**
 * F3-24: negative-only diagnostic for the mapped CSV laboratory.
 *
 * Identifiers, positions, the TenantContext and preview are caller supplied
 * and NEVER prove identity, membership, field grants or a right to import.
 * This class only distinguishes malformed synthetic shape from an unverified
 * request. It has no ALLOW path, I/O, database calls or side effects.
 */
final class SyntheticMappedImportDenyGate
{
    /**
     * @param mixed $targetTenantId Unverified descriptive target organization
     * @param mixed $operation Unverified operation name (only "import" is in scope)
     * @param mixed $targetFieldPositions One-based technical target positions
     */
    public function evaluate(
        TenantContext $context,
        SyntheticImportMappedPreviewSummary $preview,
        mixed $targetTenantId,
        mixed $operation,
        mixed $targetFieldPositions,
    ): AuthorizationDecision {
        $correlation = $context->correlationId;

        // A syntactically matching UUID is NOT proof of the real tenant.
        if (!self::uuid($targetTenantId)
            || !self::uuid($context->tenantId)
            || !self::uuid($context->principalId)
            || strcasecmp($targetTenantId, $context->tenantId) !== 0
            || $operation !== 'import') {
            return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
        }

        try {
            $review = (new SyntheticImportReviewInspector())->assess($preview);
        } catch (InvalidArgumentException) {
            return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
        }

        // For this negative laboratory the request must exactly describe ALL
        // mapped targets, without a wildcard or implicit default. No label is
        // returned; position equality is still NOT a field entitlement.
        $targets = $preview->mappingPreview->targetColumns;
        if (!is_array($targetFieldPositions)
            || !array_is_list($targetFieldPositions)
            || count($targetFieldPositions) < 1
            || count($targetFieldPositions) > 8
            || count($targetFieldPositions) !== $review->mappedColumns) {
            return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
        }

        $requested = [];
        foreach ($targetFieldPositions as $index) {
            if (!is_int($index) || $index < 1 || $index > $targets || isset($requested[$index])) {
                return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
            }
            $requested[$index] = true;
        }

        $mappedTargets = [];
        foreach ($preview->mappingPreview->positions as $pair) {
            $mappedTargets[$pair['target']] = true;
        }

        if (array_keys($requested) !== array_keys($mappedTargets)) {
            // Ignore the caller's ordering, not mismatched field positions.
            $requestedSorted = array_keys($requested);
            $mappedSorted = array_keys($mappedTargets);
            sort($requestedSorted, SORT_NUMERIC);
            sort($mappedSorted, SORT_NUMERIC);
            if ($requestedSorted !== $mappedSorted) {
                return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
            }
        }

        if ($review->needsReview() || $review->rowsInspected === 0
            || !$preview->isMappingComplete() || $preview->canImport()) {
            return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
        }

        // Even clean, matching, syntactically complete fixtures cannot
        // establish trusted tenant, user membership or field-level rights.
        return AuthorizationDecision::deny('SCOPE_NOT_VERIFIED', $correlation);
    }

    private static function uuid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $value) === 1
            && strtolower($value) !== '00000000-0000-0000-0000-000000000000';
    }
}
