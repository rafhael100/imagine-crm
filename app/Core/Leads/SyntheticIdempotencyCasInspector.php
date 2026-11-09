<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-12: laboratory-only compare-and-swap PRECONDITION CLASSIFIER.
 *
 * Does not make reservations or connect to a durable ledger. Two independent
 * calls with the same snapshot may both report ATOMIC_STORE_REQUIRED; only
 * an approved, transactionally isolated DB/CAS can choose a winner.
 */
final class SyntheticIdempotencyCasInspector
{
    public function inspect(
        SyntheticIdempotencySnapshot $stored,
        SyntheticIdempotencyClaim $incoming,
        int $expectedVersion,
        string $operation = 'reserve',
    ): SyntheticIdempotencyCasAssessment {
        if (!$stored->original->sameLogicalLedgerScopeAs($incoming)) {
            return new SyntheticIdempotencyCasAssessment('LOGICAL_SCOPE_MISMATCH');
        }

        // Binding, site, form and CRM destination are pinned when the
        // logical submission first enters a trusted ledger; no re-routing.
        if (!$stored->original->samePinnedContextAs($incoming)) {
            return new SyntheticIdempotencyCasAssessment('PINNED_CONTEXT_CONFLICT');
        }

        if ($expectedVersion < 0) {
            return new SyntheticIdempotencyCasAssessment('VERSION_INVALID');
        }

        if ($stored->version !== $expectedVersion) {
            return new SyntheticIdempotencyCasAssessment('STALE_SNAPSHOT_RECONCILE');
        }

        if ($operation !== 'reserve') {
            return new SyntheticIdempotencyCasAssessment('OPERATION_NOT_SUPPORTED');
        }

        return match ($stored->phase) {
            SyntheticIdempotencyPhase::Reserved =>
                new SyntheticIdempotencyCasAssessment('IN_FLIGHT_REVIEW'),
            SyntheticIdempotencyPhase::OutcomeUnknown =>
                new SyntheticIdempotencyCasAssessment('UNKNOWN_OUTCOME_LOOKUP_REQUIRED'),
            SyntheticIdempotencyPhase::DeliveredClaimed =>
                new SyntheticIdempotencyCasAssessment('DELIVERED_CLAIM_REVIEW'),
            SyntheticIdempotencyPhase::ExpiredClaimed =>
                new SyntheticIdempotencyCasAssessment('EXPIRED_CLAIM_REVIEW'),
            SyntheticIdempotencyPhase::Open => $this->candidate($stored->version),
        };
    }

    private function candidate(int $version): SyntheticIdempotencyCasAssessment
    {
        if ($version === PHP_INT_MAX) {
            return new SyntheticIdempotencyCasAssessment('VERSION_OVERFLOW');
        }

        // No side effect occurs; this version requires a REAL atomic store.
        return new SyntheticIdempotencyCasAssessment('ATOMIC_STORE_REQUIRED', $version + 1);
    }
}
