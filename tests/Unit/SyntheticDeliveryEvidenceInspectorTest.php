<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticDeliveryEvidenceInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticDeliveryEvidenceInspectorTest extends TestCase
{
    private const ORG = '11111111-1111-4111-8111-111111111111';
    private const OTHER_ORG = '22222222-2222-4222-8222-222222222222';
    private const BINDING = '33333333-3333-4333-8333-333333333333';
    private const OTHER_BINDING = '44444444-4444-4444-8444-444444444444';
    private const SUBMISSION = '55555555-5555-4555-8555-555555555555';
    private const OTHER_SUBMISSION = '66666666-6666-4666-8666-666666666666';
    private const CRM_RECORD = '77777777-7777-4777-8777-777777777777';

    private static function identity(
        string $org = self::ORG,
        string $binding = self::BINDING,
        string $submission = self::SUBMISSION,
    ): LeadSubmissionIdentity {
        return new LeadSubmissionIdentity($org, $binding, $submission);
    }

    public static function claimsNeverAuthorize(): array
    {
        return [
            'accepted by API does not mean durable' => ['primary', 202, null, null, 'DURABLE_RECEIPT_NOT_PROVEN'],
            'HTTP 204 does not mean durable' => ['primary', 204, null, null, 'DURABLE_RECEIPT_NOT_PROVEN'],
            'API timeout uncertain' => ['primary', null, null, null, 'RECEIPT_OUTCOME_UNKNOWN'],
            'API failure' => ['contingency', 500, 200, self::CRM_RECORD, 'DURABLE_RECEIPT_NOT_PROVEN'],
            'CRM async 202 not a delivery' => ['primary', 201, 202, self::CRM_RECORD, 'CRM_PERSISTENCE_NOT_PROVEN'],
            'CRM unknown after lost response' => ['contingency', 201, null, self::CRM_RECORD, 'CRM_OUTCOME_UNKNOWN'],
            'CRM error' => ['primary', 200, 500, self::CRM_RECORD, 'CRM_PERSISTENCE_NOT_PROVEN'],
            'CRM accepted but no record proof' => ['primary', 201, 201, null, 'CRM_RECORD_UNVERIFIED'],
            'CRM fake record ID' => ['primary', 201, 200, 'not-a-record-id', 'CRM_RECORD_UNVERIFIED'],
            'CRM nil record' => ['primary', 201, 200, '00000000-0000-0000-0000-000000000000', 'CRM_RECORD_UNVERIFIED'],
            'claims 200 with ID still untrusted' => ['primary', 200, 200, self::CRM_RECORD, 'TRUSTED_EVIDENCE_REQUIRED'],
            'claims 201 with ID still untrusted' => ['contingency', 201, 201, self::CRM_RECORD, 'TRUSTED_EVIDENCE_REQUIRED'],
            'unrecognized receiver' => ['other-service', 201, 201, self::CRM_RECORD, 'RECEIVER_UNRECOGNIZED'],
        ];
    }

    #[DataProvider('claimsNeverAuthorize')]
    public function test_unverified_responses_never_establish_receipt_or_delivery(
        string $route,
        ?int $apiStatus,
        ?int $crmStatus,
        ?string $record,
        string $expectedCode,
    ): void {
        $result = (new SyntheticDeliveryEvidenceInspector())->inspect(
            self::identity(), self::identity(), $route, $apiStatus, $crmStatus, $record,
        );

        self::assertSame($expectedCode, $result->reasonCode);
        self::assertFalse($result->canAcknowledgeVisitor());
        self::assertFalse($result->canMarkDeliveredToCrm());
        self::assertFalse($result->canRetryAutomatically());
    }

    public static function wrongContexts(): array
    {
        return [
            'different org' => [self::OTHER_ORG, self::BINDING, self::SUBMISSION],
            'different binding' => [self::ORG, self::OTHER_BINDING, self::SUBMISSION],
            'different submission' => [self::ORG, self::BINDING, self::OTHER_SUBMISSION],
        ];
    }

    #[DataProvider('wrongContexts')]
    public function test_mismatched_org_binding_or_submission_blocks_delivery(
        string $org,
        string $binding,
        string $submission,
    ): void {
        $result = (new SyntheticDeliveryEvidenceInspector())->inspect(
            self::identity(), self::identity($org, $binding, $submission),
            'contingency', 201, 201, self::CRM_RECORD,
        );
        self::assertSame('SUBMISSION_CONTEXT_MISMATCH', $result->reasonCode);
        self::assertFalse($result->canMarkDeliveredToCrm());
    }

    public function test_primary_and_contingency_may_report_same_logical_submission(): void
    {
        $inspector = new SyntheticDeliveryEvidenceInspector();
        $key = self::identity();

        $primary = $inspector->inspect($key, $key, 'primary', 201, 201, self::CRM_RECORD);
        $contingency = $inspector->inspect($key, $key, 'contingency', 201, 201, self::CRM_RECORD);

        self::assertSame($primary->reasonCode, $contingency->reasonCode);
        self::assertFalse($primary->canRetryAutomatically());
        self::assertFalse($contingency->canMarkDeliveredToCrm());
    }

    public function test_independent_logical_submissions_are_not_deduplicated_by_contact(): void
    {
        $inspector = new SyntheticDeliveryEvidenceInspector();
        $key = self::identity();

        $other = self::identity(self::ORG, self::BINDING, self::OTHER_SUBMISSION);
        $result = $inspector->inspect($key, $other, 'primary', 201, 201, self::CRM_RECORD);

        self::assertSame('SUBMISSION_CONTEXT_MISMATCH', $result->reasonCode);
        self::assertFalse($key->sameLogicalDeliveryAs($other));
    }

    public function test_no_raw_ids_or_customer_content_are_present_in_result(): void
    {
        $result = (new SyntheticDeliveryEvidenceInspector())->inspect(
            self::identity(), self::identity(), 'primary', 201, 201, self::CRM_RECORD,
        );
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::ORG, $json);
        self::assertStringNotContainsString(self::BINDING, $json);
        self::assertStringNotContainsString(self::CRM_RECORD, $json);
        self::assertStringNotContainsString('Lead Exemplo', $json);
        self::assertFalse($result->canMarkDeliveredToCrm());
    }
}
