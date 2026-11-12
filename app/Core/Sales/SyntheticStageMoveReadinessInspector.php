<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R02/R03/R04: compose existing invented stage-history and field-presence
 * contracts into one DENY-ONLY preview. It does not load any CRM record,
 * authenticate a principal, lock a version or authorize a stage change.
 *
 * The revision, stages, events, requirements and booleans are caller claims.
 * A "complete" preview must NEVER be interpreted as permission to persist.
 */
final class SyntheticStageMoveReadinessInspector
{
    /**
     * @param mixed $events An untrusted 0..24-event synthetic ledger.
     * @param mixed $expectedRevision Untrusted integer optimistic-revision claim.
     * @param mixed $targetStage One of four invented stages (0..3).
     * @param mixed $fieldPresence Five boolean field-presence flags; no values.
     * @param mixed $requirements Exactly four synthetic lists of field codes.
     * @return array<string, mixed> Numeric and allowlisted status-only projection.
     */
    public function inspect(
        mixed $events,
        mixed $expectedRevision,
        mixed $targetStage,
        mixed $fieldPresence,
        mixed $requirements,
    ): array {
        if (!is_int($expectedRevision) || $expectedRevision < 0
            || $expectedRevision > 24 || !is_int($targetStage)
            || $targetStage < 0 || $targetStage > 3) {
            throw new InvalidArgumentException('Previa sintetica de etapa invalida.');
        }

        $ledger = (new SyntheticOpportunityTransitionLedger())->inspect($events);
        $source = $ledger['latest_stage'];

        // Validate *all* supplied presence and policy data, even if the
        // opportunity is stale/closed/uncreated. This prevents malformed
        // caller-supplied fields from bypassing the contract via short-circuit.
        $checkFrom = is_int($source) && $source !== $targetStage
            ? $source : ($targetStage === 0 ? 1 : 0);
        $policy = (new SyntheticStageFieldRequirementsInspector())->inspect(
            $checkFrom, $targetStage, $fieldPresence, $requirements,
        );

        $reason = 'FIELDS_PRESENT_UNVERIFIED';
        if ($source === null) {
            $reason = 'NO_OPPORTUNITY';
        } elseif ($expectedRevision !== $ledger['latest_revision']) {
            $reason = 'STALE_REVISION';
        } elseif ($ledger['latest_claimed_status'] !== 'open') {
            $reason = 'CLOSED_CLAIM';
        } elseif ($source === $targetStage) {
            $reason = 'SAME_STAGE';
        } elseif ($ledger['latest_revision'] === 24) {
            $reason = 'EVENT_LIMIT_REACHED';
        } elseif (!$policy['requirements_met']) {
            $reason = 'FIELDS_MISSING';
        }

        // A blocked history never releases even a field-completeness claim.
        $freshOpenChange = $ledger['latest_claimed_status'] === 'open'
            && $source !== $targetStage
            && $expectedRevision === $ledger['latest_revision']
            && $ledger['latest_revision'] < 24;

        return [
            'reason_code' => $reason,
            'observed_revision' => $ledger['latest_revision'],
            'requested_revision' => $expectedRevision,
            'source_stage' => $source,
            'target_stage' => $targetStage,
            'required_count' => $freshOpenChange ? $policy['required_count'] : 0,
            'missing_count' => $freshOpenChange ? $policy['missing_count'] : 0,
            'missing_field_codes' => $freshOpenChange ? $policy['missing_field_codes'] : [],
            'fields_present_claimed' => $freshOpenChange && $policy['requirements_met'],
            'history_verified' => false,
            'field_values_verified' => false,
            'policy_verified' => false,
            'tenant_verified' => false,
            'concurrency_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_move' => false,
            'can_persist' => false,
        ];
    }
}
