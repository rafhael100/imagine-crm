<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticSubmissionBindingClaimInspector;
use App\Core\Leads\SyntheticSubmissionClaimAssessment;
use PHPUnit\Framework\TestCase;

final class SyntheticSubmissionBindingClaimInspectorTest extends TestCase
{
    private const ORG_A = '11111111-1111-4111-8111-111111111111';
    private const ORG_B = '22222222-2222-4222-8222-222222222222';
    private const SUB_A = '33333333-3333-4333-8333-333333333333';
    private const SUB_B = '44444444-4444-4444-8444-444444444444';
    private const BINDING_A = '55555555-5555-4555-8555-555555555555';
    private const BINDING_B = '66666666-6666-4666-8666-666666666666';

    /** @return array{organization_reference:string,submission_reference:string,binding_reference:string} */
    private static function claim(string $organization, string $submission, string $binding): array
    {
        return [
            'organization_reference' => $organization,
            'submission_reference' => $submission,
            'binding_reference' => $binding,
        ];
    }

    private static function assertDenied(
        SyntheticSubmissionClaimAssessment $result,
        string $reason,
    ): void {
        self::assertSame($reason, $result->reasonCode);
        self::assertFalse($result->canTreatAsAuthorizedRetry());
        self::assertFalse($result->canInferContactDuplicate());
        self::assertFalse($result->canMergeContacts());
        self::assertFalse($result->canPersistLead());
        self::assertFalse($result->canAcknowledgeDelivery());
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach ([self::ORG_A, self::ORG_B, self::SUB_A, self::SUB_B,
            self::BINDING_A, self::BINDING_B, 'private@example.test'] as $sensitive) {
            self::assertStringNotContainsString($sensitive, $json);
        }
    }

    public function test_same_three_technical_claims_are_still_not_authenticated(): void
    {
        $inspector = new SyntheticSubmissionBindingClaimInspector();
        $a = self::claim(self::ORG_A, self::SUB_A, self::BINDING_A);
        $b = [
            'binding_reference' => strtoupper(self::BINDING_A),
            'organization_reference' => strtoupper(self::ORG_A),
            'submission_reference' => strtoupper(self::SUB_A),
        ];
        self::assertDenied(
            $inspector->compare($a, $b),
            'SAME_SUBMISSION_SAME_BINDING_CLAIM_UNVERIFIED',
        );
    }

    public function test_same_submission_but_different_binding_claim_is_conflict_not_new_lead(): void
    {
        $i = new SyntheticSubmissionBindingClaimInspector();
        $a = self::claim(self::ORG_A, self::SUB_A, self::BINDING_A);
        $b = self::claim(self::ORG_A, self::SUB_A, self::BINDING_B);
        self::assertDenied($i->compare($a, $b), 'SUBMISSION_BINDING_CLAIM_CONFLICT');
        self::assertDenied($i->compare($b, $a), 'SUBMISSION_BINDING_CLAIM_CONFLICT');
    }

    public function test_cross_organization_conflict_has_precedence_over_binding_match(): void
    {
        $i = new SyntheticSubmissionBindingClaimInspector();
        foreach ([self::BINDING_A, self::BINDING_B] as $otherBinding) {
            self::assertDenied(
                $i->compare(
                    self::claim(self::ORG_A, self::SUB_A, self::BINDING_A),
                    self::claim(self::ORG_B, self::SUB_A, $otherBinding),
                ),
                'SUBMISSION_ORGANIZATION_CLAIM_CONFLICT',
            );
        }
    }

    public function test_distinct_submissions_same_binding_do_not_imply_same_person(): void
    {
        $i = new SyntheticSubmissionBindingClaimInspector();
        self::assertDenied(
            $i->compare(
                self::claim(self::ORG_A, self::SUB_A, self::BINDING_A),
                self::claim(self::ORG_A, self::SUB_B, self::BINDING_A),
            ),
            'DISTINCT_SUBMISSIONS_UNVERIFIED',
        );
        self::assertDenied(
            $i->compare(
                self::claim(self::ORG_A, self::SUB_A, self::BINDING_A),
                self::claim(self::ORG_B, self::SUB_B, self::BINDING_A),
            ),
            'UNRELATED_SUBMISSION_CLAIMS',
        );
    }

    public function test_bad_shapes_untrusted_person_fields_and_invalid_ids_fail_closed(): void
    {
        $inspector = new SyntheticSubmissionBindingClaimInspector();
        $valid = self::claim(self::ORG_A, self::SUB_A, self::BINDING_A);
        $invalid = [
            null, '', true, 0, new \stdClass(), [],
            [self::ORG_A, self::SUB_A, self::BINDING_A],
            ['organization_reference' => self::ORG_A, 'submission_reference' => self::SUB_A],
            array_merge($valid, ['name' => 'PRIVATE_PERSON']),
            array_merge($valid, ['email' => 'private@example.test']),
            array_merge($valid, ['consent' => true]),
            array_merge($valid, ['nonce' => self::SUB_B]),
            array_merge($valid, ['payload' => ['email' => 'private@example.test']]),
            self::claim(self::ORG_A, self::SUB_A, ''),
            self::claim(self::ORG_A, self::SUB_A, '00000000-0000-0000-0000-000000000000'),
            self::claim(self::ORG_A, self::SUB_A, str_replace('-', '', self::BINDING_A)),
            self::claim(self::ORG_A, self::SUB_A, self::BINDING_A."\n"),
            self::claim(self::ORG_A, self::SUB_A, self::BINDING_A.' '),
        ];
        foreach ($invalid as $value) {
            self::assertDenied($inspector->compare($valid, $value), 'CLAIM_INVALID');
            self::assertDenied($inspector->compare($value, $valid), 'CLAIM_INVALID');
        }
    }
}
