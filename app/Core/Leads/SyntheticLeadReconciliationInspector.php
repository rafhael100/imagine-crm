<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * An intentionally fail-closed pure classifier of synthetic receiver claims.
 *
 * It never verifies durable storage, CRM persistence, tenant membership,
 * signatures, chronology or the idempotency ledger. Even unanimous receiver
 * claims NEVER grant delivery, retry, visitor acknowledgement or deletion.
 *
 * This is a unit-test lab, not a reconciliation worker or authorization API.
 */
final class SyntheticLeadReconciliationInspector
{
    public function inspect(
        LeadSubmissionIdentity $expected,
        SyntheticReceiverObservation $primary,
        SyntheticReceiverObservation $contingency,
        bool $crmDeliveredClaimed = false,
        bool $lateCrmReplyClaimed = false,
    ): SyntheticReconciliationAssessment {
        if (!$primary->identity->sameLogicalDeliveryAs($expected)
            || !$contingency->identity->sameLogicalDeliveryAs($expected)
            || !$primary->identity->sameLogicalDeliveryAs($contingency->identity)) {
            return new SyntheticReconciliationAssessment('SUBMISSION_IDENTITY_CONFLICT');
        }

        if ($primary->route !== 'primary' || $contingency->route !== 'contingency') {
            return new SyntheticReconciliationAssessment('RECEIVER_ROLE_CONFLICT');
        }

        $primaryStatus = $primary->status;
        $secondaryStatus = $contingency->status;
        $anyExpired = $primaryStatus === SyntheticReceiverStatus::ExpiredClaimed
            || $secondaryStatus === SyntheticReceiverStatus::ExpiredClaimed;
        $anyDurable = $primaryStatus === SyntheticReceiverStatus::DurableClaimed
            || $secondaryStatus === SyntheticReceiverStatus::DurableClaimed;
        $anyFailed = $primaryStatus === SyntheticReceiverStatus::FailureClaimed
            || $secondaryStatus === SyntheticReceiverStatus::FailureClaimed;

        // A late or ambiguous CRM response must stop any conclusion that
        // expired temporary payloads are safe to delete.
        if ($lateCrmReplyClaimed && $anyExpired) {
            return new SyntheticReconciliationAssessment('LATE_REPLY_EXPIRY_CONFLICT');
        }

        if ($lateCrmReplyClaimed) {
            return new SyntheticReconciliationAssessment('LATE_REPLY_REVIEW_REQUIRED');
        }

        if ($anyExpired && $crmDeliveredClaimed) {
            return new SyntheticReconciliationAssessment('DELIVERY_EXPIRY_CONFLICT');
        }

        if ($anyExpired && $anyDurable) {
            return new SyntheticReconciliationAssessment('RECEIVER_EXPIRY_CONFLICT');
        }

        if ($anyExpired) {
            return new SyntheticReconciliationAssessment('EXPIRY_RECONCILIATION_REQUIRED');
        }

        // A reported CRM confirmation is not independent proof of a record.
        if ($crmDeliveredClaimed) {
            return new SyntheticReconciliationAssessment('CRM_PERSISTENCE_PROOF_REQUIRED');
        }

        if ($primaryStatus === SyntheticReceiverStatus::DurableClaimed
            && $secondaryStatus === SyntheticReceiverStatus::DurableClaimed) {
            return new SyntheticReconciliationAssessment('DUAL_RECEIVER_CLAIMS_REVIEW');
        }

        if ($anyDurable) {
            return new SyntheticReconciliationAssessment('DURABLE_CLAIM_UNVERIFIED');
        }

        if ($primaryStatus === SyntheticReceiverStatus::FailureClaimed
            && $secondaryStatus === SyntheticReceiverStatus::FailureClaimed) {
            return new SyntheticReconciliationAssessment('BOTH_RECEIVERS_FAILED_CLAIMED');
        }

        if ($anyFailed) {
            return new SyntheticReconciliationAssessment('RECEIVER_FAILURE_CLAIMED');
        }

        return new SyntheticReconciliationAssessment('NO_VERIFIED_RECEIPT');
    }
}
