<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticLeadReconciliationInspector;
use App\Core\Leads\SyntheticReceiverObservation;
use App\Core\Leads\SyntheticReceiverStatus as Status;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadReconciliationInspectorTest extends TestCase
{
    private const ORG = '11111111-1111-4111-8111-111111111111';
    private const OTHER_ORG = '22222222-2222-4222-8222-222222222222';
    private const BINDING = '33333333-3333-4333-8333-333333333333';
    private const OTHER_BINDING = '44444444-4444-4444-8444-444444444444';
    private const SUBMISSION = '55555555-5555-4555-8555-555555555555';
    private const OTHER_SUBMISSION = '66666666-6666-4666-8666-666666666666';

    private static function key(
        string $org = self::ORG,
        string $binding = self::BINDING,
        string $submission = self::SUBMISSION,
    ): LeadSubmissionIdentity {
        return new LeadSubmissionIdentity($org, $binding, $submission);
    }

    private static function observation(
        string $route,
        Status $status,
        ?LeadSubmissionIdentity $identity = null,
    ): SyntheticReceiverObservation {
        return new SyntheticReceiverObservation($route, $identity ?? self::key(), $status);
    }

    public static function diagnosticCases(): array
    {
        return [
            'no observation' => [Status::Unknown, Status::Unknown, false, false, 'NO_VERIFIED_RECEIPT'],
            'only primary durable' => [Status::DurableClaimed, Status::Unknown, false, false, 'DURABLE_CLAIM_UNVERIFIED'],
            'only contingency durable' => [Status::Unknown, Status::DurableClaimed, false, false, 'DURABLE_CLAIM_UNVERIFIED'],
            'dual durable claims' => [Status::DurableClaimed, Status::DurableClaimed, false, false, 'DUAL_RECEIVER_CLAIMS_REVIEW'],
            'only primary failed' => [Status::FailureClaimed, Status::Unknown, false, false, 'RECEIVER_FAILURE_CLAIMED'],
            'both failed' => [Status::FailureClaimed, Status::FailureClaimed, false, false, 'BOTH_RECEIVERS_FAILED_CLAIMED'],
            'failure with durable claim' => [Status::FailureClaimed, Status::DurableClaimed, false, false, 'DURABLE_CLAIM_UNVERIFIED'],
            'expired claim alone' => [Status::ExpiredClaimed, Status::Unknown, false, false, 'EXPIRY_RECONCILIATION_REQUIRED'],
            'both expired claims' => [Status::ExpiredClaimed, Status::ExpiredClaimed, false, false, 'EXPIRY_RECONCILIATION_REQUIRED'],
            'expiry against durable' => [Status::ExpiredClaimed, Status::DurableClaimed, false, false, 'RECEIVER_EXPIRY_CONFLICT'],
            'CRM delivered claimed' => [Status::DurableClaimed, Status::Unknown, true, false, 'CRM_PERSISTENCE_PROOF_REQUIRED'],
            'CRM delivered with no receiver proof' => [Status::Unknown, Status::Unknown, true, false, 'CRM_PERSISTENCE_PROOF_REQUIRED'],
            'expiry against delivered' => [Status::ExpiredClaimed, Status::Unknown, true, false, 'DELIVERY_EXPIRY_CONFLICT'],
            'late reply after expiry' => [Status::ExpiredClaimed, Status::Unknown, true, true, 'LATE_REPLY_EXPIRY_CONFLICT'],
            'late reply against pending' => [Status::DurableClaimed, Status::Unknown, false, true, 'LATE_REPLY_REVIEW_REQUIRED'],
            'late reply with both receivers' => [Status::DurableClaimed, Status::DurableClaimed, true, true, 'LATE_REPLY_REVIEW_REQUIRED'],
        ];
    }

    #[DataProvider('diagnosticCases')]
    public function test_observations_never_grant_operations(
        Status $primaryStatus,
        Status $contingencyStatus,
        bool $crmClaim,
        bool $lateClaim,
        string $expectedCode,
    ): void {
        $result = (new SyntheticLeadReconciliationInspector())->inspect(
            self::key(),
            self::observation('primary', $primaryStatus),
            self::observation('contingency', $contingencyStatus),
            $crmClaim,
            $lateClaim,
        );

        self::assertSame($expectedCode, $result->reasonCode);
        self::assertFalse($result->canMarkDelivered());
        self::assertFalse($result->canScheduleRetry());
        self::assertFalse($result->canDeleteTemporaryPayload());
        self::assertFalse($result->canAcknowledgeVisitor());
    }

    public static function forgedIdentityCases(): array
    {
        return [
            'other org' => [self::OTHER_ORG, self::BINDING, self::SUBMISSION],
            'binding changed' => [self::ORG, self::OTHER_BINDING, self::SUBMISSION],
            'different submission' => [self::ORG, self::BINDING, self::OTHER_SUBMISSION],
        ];
    }

    #[DataProvider('forgedIdentityCases')]
    public function test_cross_tenant_binding_and_submission_mismatch_are_not_reconciled(
        string $org,
        string $binding,
        string $submission,
    ): void {
        $result = (new SyntheticLeadReconciliationInspector())->inspect(
            self::key(),
            self::observation('primary', Status::DurableClaimed),
            self::observation('contingency', Status::DurableClaimed, self::key($org, $binding, $submission)),
            true,
            true,
        );

        self::assertSame('SUBMISSION_IDENTITY_CONFLICT', $result->reasonCode);
        self::assertFalse($result->canMarkDelivered());
    }

    public function test_primary_cannot_impersonate_contingency_or_duplicate_receiver(): void
    {
        $result = (new SyntheticLeadReconciliationInspector())->inspect(
            self::key(),
            self::observation('primary', Status::DurableClaimed),
            self::observation('primary', Status::DurableClaimed),
        );
        self::assertSame('RECEIVER_ROLE_CONFLICT', $result->reasonCode);
        self::assertFalse($result->canMarkDelivered());
    }

    public function test_unrecognized_receiver_route_is_rejected_at_creation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SyntheticReceiverObservation('unknown-api', self::key(), Status::Unknown);
    }

    public function test_no_result_contains_org_submission_or_record_payload(): void
    {
        $result = (new SyntheticLeadReconciliationInspector())->inspect(
            self::key(),
            self::observation('primary', Status::DurableClaimed),
            self::observation('contingency', Status::DurableClaimed),
            true,
        );

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::ORG, $json);
        self::assertStringNotContainsString(self::BINDING, $json);
        self::assertStringNotContainsString(self::SUBMISSION, $json);
        self::assertStringNotContainsString('Lead Exemplo', $json);
        self::assertFalse($result->canMarkDelivered());
    }

    public function test_two_distinct_submissions_are_not_silently_merged(): void
    {
        $a = self::key();
        $b = self::key(self::ORG, self::BINDING, self::OTHER_SUBMISSION);
        self::assertFalse($a->sameLogicalDeliveryAs($b));

        $result = (new SyntheticLeadReconciliationInspector())->inspect(
            $a,
            self::observation('primary', Status::DurableClaimed, $a),
            self::observation('contingency', Status::DurableClaimed, $b),
        );
        self::assertSame('SUBMISSION_IDENTITY_CONFLICT', $result->reasonCode);
    }
}
