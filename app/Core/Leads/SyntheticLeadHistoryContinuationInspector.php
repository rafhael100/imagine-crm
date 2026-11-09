<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Compare adjacent, untrusted history slices without conferring any authority.
 *
 * This is NOT pagination of real data, an event ledger or an audit API.
 * Both input slices are subject to the existing bounded, deny-only inspector.
 */
final class SyntheticLeadHistoryContinuationInspector
{
    /**
     * @param mixed $earlierSlice List of <=32 SyntheticLeadHistoryClaim objects.
     * @param mixed $laterSlice List of <=32 SyntheticLeadHistoryClaim objects.
     */
    public function inspect(
        string $expectedTenant,
        string $expectedLead,
        mixed $earlierSlice,
        mixed $laterSlice,
    ): SyntheticLeadHistoryAssessment {
        $inspector = new SyntheticLeadHistoryInspector();

        // Shape, row limits, tenant, lead, actor, duplicate and order
        // validation are kept in one existing, bounded contract per slice.
        $earlier = $inspector->inspect($expectedTenant, $expectedLead, $earlierSlice);
        if ($earlier->reasonCode !== 'PROVENANCE_UNVERIFIED') {
            return $earlier;
        }

        $later = $inspector->inspect($expectedTenant, $expectedLead, $laterSlice);
        if ($later->reasonCode !== 'PROVENANCE_UNVERIFIED') {
            return $later;
        }

        // At this point, both inputs are nonempty lists of tested claim types.
        // Duplicate references across the two slices are never merged or
        // repaired, regardless of the claimed ordinal positions.
        $seen = [];
        foreach ($earlierSlice as $claim) {
            $seen[$claim->eventReference] = true;
        }
        foreach ($laterSlice as $claim) {
            if (isset($seen[$claim->eventReference])) {
                return new SyntheticLeadHistoryAssessment('EVENT_REFERENCE_REUSED');
            }
        }

        $last = $earlierSlice[count($earlierSlice) - 1];
        $first = $laterSlice[0];
        if ($first->claimedPosition !== $last->claimedPosition + 1) {
            return new SyntheticLeadHistoryAssessment('HISTORY_ORDER_UNVERIFIED');
        }

        // Consecutive claimed positions are NOT authenticated provenance.
        // In particular, this never exposes content nor grants read/export.
        return new SyntheticLeadHistoryAssessment('PROVENANCE_UNVERIFIED');
    }
}
