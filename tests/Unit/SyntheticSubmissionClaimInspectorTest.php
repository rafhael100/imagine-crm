<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticSubmissionClaimAssessment;
use App\Core\Leads\SyntheticSubmissionClaimInspector;
use PHPUnit\Framework\TestCase;

final class SyntheticSubmissionClaimInspectorTest extends TestCase
{
    private const ORG_A = '11111111-1111-4111-8111-111111111111';
    private const ORG_B = '22222222-2222-4222-8222-222222222222';
    private const SUBMISSION_A = '33333333-3333-4333-8333-333333333333';
    private const SUBMISSION_B = '44444444-4444-4444-8444-444444444444';

    /** @return array{organization_reference:string,submission_reference:string} */
    private static function claim(string $org, string $submission): array
    {
        return [
            'organization_reference' => $org,
            'submission_reference' => $submission,
        ];
    }

    private static function assertUntrustedAndDenied(
        SyntheticSubmissionClaimAssessment $assessment,
        string $reason,
    ): void {
        self::assertSame($reason, $assessment->reasonCode);
        self::assertFalse($assessment->canTreatAsAuthorizedRetry());
        self::assertFalse($assessment->canInferContactDuplicate());
        self::assertFalse($assessment->canMergeContacts());
        self::assertFalse($assessment->canPersistLead());
        self::assertFalse($assessment->canAcknowledgeDelivery());

        $json = json_encode($assessment, JSON_THROW_ON_ERROR);
        foreach ([self::ORG_A, self::ORG_B, self::SUBMISSION_A, self::SUBMISSION_B] as $private) {
            self::assertStringNotContainsString($private, $json);
        }
    }

    public function test_matching_technical_ids_are_only_unverified_submission_claims(): void
    {
        $inspector = new SyntheticSubmissionClaimInspector();
        $a = self::claim(self::ORG_A, self::SUBMISSION_A);
        $b = [
            'submission_reference' => strtoupper(self::SUBMISSION_A),
            'organization_reference' => strtoupper(self::ORG_A),
        ];

        self::assertUntrustedAndDenied(
            $inspector->compare($a, $b),
            'SAME_SUBMISSION_CLAIM_UNVERIFIED',
        );
        self::assertSame($a, self::claim(self::ORG_A, self::SUBMISSION_A));
    }

    public function test_distinct_logical_submissions_do_not_become_same_person_or_retry(): void
    {
        self::assertUntrustedAndDenied(
            (new SyntheticSubmissionClaimInspector())->compare(
                self::claim(self::ORG_A, self::SUBMISSION_A),
                self::claim(self::ORG_A, self::SUBMISSION_B),
            ),
            'DISTINCT_SUBMISSIONS_UNVERIFIED',
        );
    }

    public function test_matching_submission_id_with_another_organization_claim_is_conflict(): void
    {
        self::assertUntrustedAndDenied(
            (new SyntheticSubmissionClaimInspector())->compare(
                self::claim(self::ORG_A, self::SUBMISSION_A),
                self::claim(self::ORG_B, self::SUBMISSION_A),
            ),
            'SUBMISSION_ORGANIZATION_CLAIM_CONFLICT',
        );
    }

    public function test_unrelated_submission_and_organization_claims_remain_unverified(): void
    {
        self::assertUntrustedAndDenied(
            (new SyntheticSubmissionClaimInspector())->compare(
                self::claim(self::ORG_A, self::SUBMISSION_A),
                self::claim(self::ORG_B, self::SUBMISSION_B),
            ),
            'UNRELATED_SUBMISSION_CLAIMS',
        );
    }

    public function test_bad_shapes_extra_payload_and_contact_data_are_rejected_without_echo(): void
    {
        $valid = self::claim(self::ORG_A, self::SUBMISSION_A);
        $invalid = [
            null, '', 0, true, new \stdClass(), [],
            [self::ORG_A, self::SUBMISSION_A],
            ['organization_reference' => self::ORG_A],
            ['submission_reference' => self::SUBMISSION_A],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => self::SUBMISSION_A,
                'name' => 'PRIVATE_PERSON'],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => self::SUBMISSION_A,
                'email' => 'private@example.test'],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => self::SUBMISSION_A,
                'phone' => '123456789'],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => [self::SUBMISSION_A]],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => null],
            ['organization_reference' => 'not-a-uuid',
                'submission_reference' => self::SUBMISSION_A],
            ['organization_reference' => '00000000-0000-0000-0000-000000000000',
                'submission_reference' => self::SUBMISSION_A],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => '00000000-0000-0000-0000-000000000000'],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => strtoupper(str_replace('-', '', self::SUBMISSION_A))],
            ['organization_reference' => self::ORG_A,
                'submission_reference' => self::SUBMISSION_A."\n"],
        ];
        $inspector = new SyntheticSubmissionClaimInspector();

        foreach ($invalid as $value) {
            self::assertUntrustedAndDenied(
                $inspector->compare($value, $valid),
                'CLAIM_INVALID',
            );
            self::assertUntrustedAndDenied(
                $inspector->compare($valid, $value),
                'CLAIM_INVALID',
            );
        }
    }

    public function test_same_unverified_claim_remains_non_operational_under_repetition(): void
    {
        $inspector = new SyntheticSubmissionClaimInspector();
        $claim = self::claim(self::ORG_A, self::SUBMISSION_A);
        for ($i = 0; $i < 10; ++$i) {
            self::assertUntrustedAndDenied(
                $inspector->compare($claim, $claim),
                'SAME_SUBMISSION_CLAIM_UNVERIFIED',
            );
        }
    }
}
