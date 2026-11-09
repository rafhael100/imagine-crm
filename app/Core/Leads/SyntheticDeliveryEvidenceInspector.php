<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Categorizes UNVERIFIED transport observations with synthetic IDs only.
 *
 * A 200/201 response, a claimed CRM UUID, or matching identifiers can never
 * establish durable API receipt, authoritative tenant binding or actual CRM
 * persistence. F3-07 creates no HTTP endpoints, queues, persistence or ALLOW.
 */
final class SyntheticDeliveryEvidenceInspector
{
    public function inspect(
        LeadSubmissionIdentity $expected,
        LeadSubmissionIdentity $reported,
        string $route,
        ?int $apiHttpStatus,
        ?int $crmHttpStatus,
        ?string $claimedCrmRecordId = null,
    ): SyntheticDeliveryEvidenceAssessment {
        if (!$expected->sameLogicalDeliveryAs($reported)) {
            // A changed CRM binding or tenant must not reroute old submissions.
            return new SyntheticDeliveryEvidenceAssessment('SUBMISSION_CONTEXT_MISMATCH');
        }

        if (!in_array($route, ['primary', 'contingency'], true)) {
            return new SyntheticDeliveryEvidenceAssessment('RECEIVER_UNRECOGNIZED');
        }

        if ($apiHttpStatus === null) {
            return new SyntheticDeliveryEvidenceAssessment('RECEIPT_OUTCOME_UNKNOWN');
        }

        if (!in_array($apiHttpStatus, [200, 201], true)) {
            return new SyntheticDeliveryEvidenceAssessment('DURABLE_RECEIPT_NOT_PROVEN');
        }

        if ($crmHttpStatus === null) {
            return new SyntheticDeliveryEvidenceAssessment('CRM_OUTCOME_UNKNOWN');
        }

        if (!in_array($crmHttpStatus, [200, 201], true)) {
            return new SyntheticDeliveryEvidenceAssessment('CRM_PERSISTENCE_NOT_PROVEN');
        }

        // This is only a format check, NOT lookup in the destination CRM.
        if ($claimedCrmRecordId === null
            || preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $claimedCrmRecordId) !== 1
            || strtolower($claimedCrmRecordId) === '00000000-0000-0000-0000-000000000000') {
            return new SyntheticDeliveryEvidenceAssessment('CRM_RECORD_UNVERIFIED');
        }

        // Even all green-looking claims are not independent proofs.
        return new SyntheticDeliveryEvidenceAssessment('TRUSTED_EVIDENCE_REQUIRED');
    }
}
