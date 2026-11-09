<?php

declare(strict_types=1);

namespace App\Core\Portability;

use App\Core\Authorization\AuthorizationDecision;
use App\Core\Tenancy\TenantContext;

/**
 * F3-05 lab: tests that import/export PREVIEW metadata cannot supply authority.
 *
 * This is a negative-only diagnostic contract, NOT an access-control service,
 * tenant resolver or authorization policy for actual import/export operations.
 * The caller-supplied TenantContext is merely descriptive and unverified.
 * It has no I/O, writes, side effects or path to an ALLOW decision.
 */
final class SyntheticPortabilityDenyGate
{
    /**
     * @param mixed $targetTenantId Unverified requested organization reference
     * @param mixed $operation Unverified "import" or "export" description
     * @param mixed $fields Unverified list of technical field labels
     */
    public function evaluate(
        TenantContext $context,
        SyntheticImportPreviewSummary $preview,
        mixed $targetTenantId,
        mixed $operation,
        mixed $fields,
    ): AuthorizationDecision {
        $correlation = $context->correlationId;

        if (!$this->isValidTenantId($targetTenantId)
            || !$this->isValidTenantId($context->tenantId)
            || strtolower($targetTenantId) !== strtolower($context->tenantId)
            || !is_string($operation)
            || !in_array($operation, ['import', 'export'], true)
            || !$this->validFields($fields)
            || $preview->canImport()) {
            return AuthorizationDecision::deny('CONTRACT_INVALID', $correlation);
        }

        // Equal strings and a well-formed preview are NOT evidence of identity,
        // tenant membership, field rights, approval or legitimate export.
        // Even the "happy path" fails closed until CORE/SEG are homologated.
        return AuthorizationDecision::deny('SCOPE_NOT_VERIFIED', $correlation);
    }

    private function isValidTenantId(mixed $id): bool
    {
        return is_string($id)
            && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $id) === 1
            && strtolower($id) !== '00000000-0000-0000-0000-000000000000';
    }

    private function validFields(mixed $fields): bool
    {
        if (!is_array($fields) || !array_is_list($fields)
            || count($fields) < 1 || count($fields) > 8) {
            return false;
        }

        $seen = [];
        foreach ($fields as $field) {
            if (!is_string($field)
                || preg_match('/\A[a-z][a-z0-9_]{0,31}\z/D', $field) !== 1
                || isset($seen[$field])) {
                return false;
            }

            $seen[$field] = true;
        }

        return true;
    }
}
