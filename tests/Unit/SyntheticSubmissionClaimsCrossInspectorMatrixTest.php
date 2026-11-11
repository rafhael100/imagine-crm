<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticSubmissionBindingBatchInspector;
use App\Core\Leads\SyntheticSubmissionBindingClaimInspector;
use App\Core\Leads\SyntheticSubmissionClaimBatchInspector;
use App\Core\Leads\SyntheticSubmissionClaimInspector;
use PHPUnit\Framework\TestCase;

final class SyntheticSubmissionClaimsCrossInspectorMatrixTest extends TestCase
{
    private const ORGS = [
        '11111111-1111-4111-8111-111111111111',
        '22222222-2222-4222-8222-222222222222',
        '33333333-3333-4333-8333-333333333333',
    ];
    private const SUBMISSIONS = [
        '44444444-4444-4444-8444-444444444444',
        '55555555-5555-4555-8555-555555555555',
        '66666666-6666-4666-8666-666666666666',
    ];
    private const BINDINGS = [
        '77777777-7777-4777-8777-777777777777',
        '88888888-8888-4888-8888-888888888888',
    ];

    /** @return array{organization_reference:string,submission_reference:string,binding_reference:string} */
    private static function claim(string $org, string $submission, string $binding): array
    {
        return [
            'organization_reference' => $org,
            'submission_reference' => $submission,
            'binding_reference' => $binding,
        ];
    }

    /** @return array{organization_reference:string,submission_reference:string} */
    private static function basic(array $claim): array
    {
        return [
            'organization_reference' => $claim['organization_reference'],
            'submission_reference' => $claim['submission_reference'],
        ];
    }

    /** @return list<array{organization_reference:string,submission_reference:string,binding_reference:string}> */
    private static function fixtureMatrix(): array
    {
        $claims = [];
        foreach (self::ORGS as $organization) {
            foreach (self::SUBMISSIONS as $submission) {
                foreach (self::BINDINGS as $binding) {
                    $claims[] = self::claim($organization, $submission, $binding);
                }
            }
        }

        // Uppercase spellings represent the same technical UUID values,
        // while never implying authenticated ownership of an organization.
        $claims[] = self::claim(
            strtoupper(self::ORGS[0]), strtoupper(self::SUBMISSIONS[0]),
            strtoupper(self::BINDINGS[0]),
        );
        $claims[] = self::claim(
            strtoupper(self::ORGS[2]), strtoupper(self::SUBMISSIONS[2]),
            strtoupper(self::BINDINGS[1]),
        );

        return $claims;
    }

    public function test_every_pair_of_twenty_claims_agrees_with_both_batch_inspectors(): void
    {
        $claims = self::fixtureMatrix();
        self::assertCount(20, $claims);

        $basicInspector = new SyntheticSubmissionClaimInspector();
        $bindingInspector = new SyntheticSubmissionBindingClaimInspector();
        $basicBatch = (new SyntheticSubmissionClaimBatchInspector())->inspect(
            array_map(self::basic(...), $claims),
        );
        $bindingBatch = (new SyntheticSubmissionBindingBatchInspector())->inspect($claims);

        self::assertSame(20, $basicBatch->claimsInspected);
        self::assertSame(20, $bindingBatch->claimsInspected);

        $same = [];
        $bindingConflicts = [];
        $organizationConflicts = [];
        $basicSame = [];
        $basicOrganizationConflicts = [];
        $pairsChecked = 0;

        foreach ($claims as $right => $claim) {
            for ($left = 0; $left < $right; ++$left) {
                ++$pairsChecked;
                $b = $basicInspector->compare(
                    self::basic($claims[$left]), self::basic($claim),
                );
                $three = $bindingInspector->compare($claims[$left], $claim);
                $twoReason = $b->reasonCode;
                $threeReason = $three->reasonCode;

                // The extra binding dimension can only split a same-submission
                // claim into identical-binding or conflicting-binding cases.
                if ($twoReason === 'SAME_SUBMISSION_CLAIM_UNVERIFIED') {
                    self::assertContains($threeReason, [
                        'SAME_SUBMISSION_SAME_BINDING_CLAIM_UNVERIFIED',
                        'SUBMISSION_BINDING_CLAIM_CONFLICT',
                    ]);
                    $basicSame[] = ['first' => $left + 1, 'repeated' => $right + 1];
                } else {
                    self::assertSame($twoReason, $threeReason);
                }

                if ($twoReason === 'SUBMISSION_ORGANIZATION_CLAIM_CONFLICT') {
                    $basicOrganizationConflicts[] = [
                        'first' => $left + 1, 'conflicting' => $right + 1,
                    ];
                }

                if ($threeReason === 'SAME_SUBMISSION_SAME_BINDING_CLAIM_UNVERIFIED') {
                    $same[] = ['first' => $left + 1, 'repeated' => $right + 1];
                } elseif ($threeReason === 'SUBMISSION_BINDING_CLAIM_CONFLICT') {
                    $bindingConflicts[] = ['first' => $left + 1, 'conflicting' => $right + 1];
                } elseif ($threeReason === 'SUBMISSION_ORGANIZATION_CLAIM_CONFLICT') {
                    $organizationConflicts[] = ['first' => $left + 1, 'conflicting' => $right + 1];
                }

                foreach ([$b, $three] as $assessment) {
                    self::assertFalse($assessment->canTreatAsAuthorizedRetry());
                    self::assertFalse($assessment->canInferContactDuplicate());
                    self::assertFalse($assessment->canMergeContacts());
                    self::assertFalse($assessment->canPersistLead());
                    self::assertFalse($assessment->canAcknowledgeDelivery());
                }
            }
        }

        self::assertSame(190, $pairsChecked);
        self::assertSame($basicSame, $basicBatch->sameOrganizationClaimPositions);
        self::assertSame(
            $basicOrganizationConflicts,
            $basicBatch->crossOrganizationClaimPositions,
        );
        self::assertSame($same, $bindingBatch->sameClaimPositions);
        self::assertSame($bindingConflicts, $bindingBatch->bindingConflictPositions);
        self::assertSame(
            $organizationConflicts,
            $bindingBatch->organizationConflictPositions,
        );

        foreach ([$basicBatch, $bindingBatch] as $report) {
            self::assertFalse($report->canTreatAsAuthorizedRetry());
            self::assertFalse($report->canMergeContacts());
            self::assertFalse($report->canPersistLead());
            self::assertFalse($report->canAcknowledgeDelivery());
            $json = json_encode($report, JSON_THROW_ON_ERROR);
            foreach ([
                ...self::ORGS, ...self::SUBMISSIONS, ...self::BINDINGS,
                'PRIVATE_PERSON', 'private@example.test',
            ] as $source) {
                self::assertStringNotContainsString($source, $json);
            }
        }
    }

    public function test_a_single_malformed_claim_never_creates_partial_batch_diagnostics(): void
    {
        $valid = self::fixtureMatrix();
        $valid[8]['binding_reference'] = ['injected' => 'private@example.test'];

        try {
            (new SyntheticSubmissionBindingBatchInspector())->inspect($valid);
            self::fail('Nested untrusted binding accepted in batch');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Lote de vinculos tecnicos sinteticos invalido.', $exception->getMessage());
            self::assertStringNotContainsString('private@example.test', $exception->getMessage());
        }

        $basic = array_map(self::basic(...), self::fixtureMatrix());
        $basic[8]['submission_reference'] = ['injected' => 'PRIVATE_PERSON'];
        try {
            (new SyntheticSubmissionClaimBatchInspector())->inspect($basic);
            self::fail('Nested untrusted submission accepted in two-key batch');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Lote de referencias sinteticas invalido.', $exception->getMessage());
            self::assertStringNotContainsString('PRIVATE_PERSON', $exception->getMessage());
        }
    }
}
