<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Fail-closed classifier of up to 32 synthetic UNTRUSTED event descriptions.
 *
 * Bounded slices with coherent positions are not a source of historical truth.
 * No authentication, tenant binding, PostgreSQL read, audit or permission call.
 */
final class SyntheticLeadHistoryInspector
{
    private const MAX_CLAIMS = 32;

    /**
     * @param mixed $claims Strictly a PHP list of SyntheticLeadHistoryClaim.
     */
    public function inspect(string $expectedTenant, string $expectedLead, mixed $claims): SyntheticLeadHistoryAssessment
    {
        if (!self::uuid($expectedTenant) || !self::uuid($expectedLead)
            || !is_array($claims) || !array_is_list($claims)
            || count($claims) > self::MAX_CLAIMS) {
            return new SyntheticLeadHistoryAssessment('CONTRACT_INVALID');
        }

        if ($claims === []) {
            return new SyntheticLeadHistoryAssessment('HISTORY_EVIDENCE_MISSING');
        }

        $tenant = strtolower($expectedTenant);
        $lead = strtolower($expectedLead);
        $seenEvents = [];
        $lastPosition = null;

        foreach ($claims as $claim) {
            if (!$claim instanceof SyntheticLeadHistoryClaim) {
                return new SyntheticLeadHistoryAssessment('CONTRACT_INVALID');
            }

            if ($claim->tenantReference !== $tenant || $claim->leadReference !== $lead) {
                return new SyntheticLeadHistoryAssessment('HISTORY_SCOPE_MISMATCH');
            }

            if (isset($seenEvents[$claim->eventReference])) {
                return new SyntheticLeadHistoryAssessment('EVENT_REFERENCE_REUSED');
            }

            $seenEvents[$claim->eventReference] = true;

            if ($claim->claimedActorReference === null) {
                return new SyntheticLeadHistoryAssessment('ACTOR_REFERENCE_MISSING');
            }

            if ($lastPosition !== null && $claim->claimedPosition !== $lastPosition + 1) {
                return new SyntheticLeadHistoryAssessment('HISTORY_ORDER_UNVERIFIED');
            }

            $lastPosition = $claim->claimedPosition;
        }

        // A flawless synthetic list is not signed, persisted or independently
        // linked to a real principal/tenant/lead. Never call it verified.
        return new SyntheticLeadHistoryAssessment('PROVENANCE_UNVERIFIED');
    }

    private static function uuid(string $value): bool
    {
        return preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $value) === 1
            && strtolower($value) !== '00000000-0000-0000-0000-000000000000';
    }
}
