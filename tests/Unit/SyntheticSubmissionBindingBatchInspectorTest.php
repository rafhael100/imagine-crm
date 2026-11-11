<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticSubmissionBindingBatchInspector;
use App\Core\Leads\SyntheticSubmissionBindingBatchSummary;
use App\Core\Leads\SyntheticSubmissionClaimBatchInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSubmissionBindingBatchInspectorTest extends TestCase
{
    private const ORG_A = '11111111-1111-4111-8111-111111111111';
    private const ORG_B = '22222222-2222-4222-8222-222222222222';
    private const SUB_A = '33333333-3333-4333-8333-333333333333';
    private const SUB_B = '44444444-4444-4444-8444-444444444444';
    private const BIND_A = '55555555-5555-4555-8555-555555555555';
    private const BIND_B = '66666666-6666-4666-8666-666666666666';

    /** @return array{organization_reference:string,submission_reference:string,binding_reference:string} */
    private static function c(string $org, string $sub, string $binding): array
    {
        return [
            'organization_reference' => $org,
            'submission_reference' => $sub,
            'binding_reference' => $binding,
        ];
    }

    /** @return list<array{organization_reference:string,submission_reference:string,binding_reference:string}> */
    private static function sample(): array
    {
        return [
            self::c(self::ORG_A, self::SUB_A, self::BIND_A),
            self::c(strtoupper(self::ORG_A), strtoupper(self::SUB_A), strtoupper(self::BIND_A)),
            self::c(self::ORG_A, self::SUB_A, self::BIND_B),
            self::c(self::ORG_B, self::SUB_A, self::BIND_A),
            self::c(self::ORG_A, self::SUB_B, self::BIND_A),
        ];
    }

    private static function denied(SyntheticSubmissionBindingBatchSummary $report): void
    {
        self::assertFalse($report->canTreatAsAuthorizedRetry());
        self::assertFalse($report->canMergeContacts());
        self::assertFalse($report->canPersistLead());
        self::assertFalse($report->canAcknowledgeDelivery());
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach ([self::ORG_A, self::ORG_B, self::SUB_A, self::SUB_B,
            self::BIND_A, self::BIND_B, 'PRIVATE_PERSON', 'private@example.test'] as $raw) {
            self::assertStringNotContainsString($raw, $json);
        }
    }

    public function test_three_distinct_collision_categories_return_only_one_based_positions(): void
    {
        $report = (new SyntheticSubmissionBindingBatchInspector())->inspect(self::sample());
        self::assertSame(5, $report->claimsInspected);
        self::assertSame([['first' => 1, 'repeated' => 2]], $report->sameClaimPositions);
        self::assertSame([
            ['first' => 1, 'conflicting' => 3],
            ['first' => 2, 'conflicting' => 3],
        ], $report->bindingConflictPositions);
        self::assertSame([
            ['first' => 1, 'conflicting' => 4],
            ['first' => 2, 'conflicting' => 4],
            ['first' => 3, 'conflicting' => 4],
        ], $report->organizationConflictPositions);
        self::assertTrue($report->hasAnyCollisionWarnings());
        self::assertTrue($report->hasBindingConflicts());
        self::denied($report);
    }

    public function test_binding_aware_batch_preserves_two_key_batch_collision_projection(): void
    {
        $claims = self::sample();
        $report = (new SyntheticSubmissionBindingBatchInspector())->inspect($claims);
        $basic = (new SyntheticSubmissionClaimBatchInspector())->inspect(
            array_map(
                static fn (array $c): array => [
                    'organization_reference' => $c['organization_reference'],
                    'submission_reference' => $c['submission_reference'],
                ],
                $claims,
            ),
        );

        $projected = $report->sameClaimPositions;
        foreach ($report->bindingConflictPositions as $conflict) {
            $projected[] = [
                'first' => $conflict['first'],
                'repeated' => $conflict['conflicting'],
            ];
        }
        usort($projected, static fn (array $a, array $b): int =>
            ($a['repeated'] <=> $b['repeated']) ?: ($a['first'] <=> $b['first'])
        );

        self::assertSame($basic->sameOrganizationClaimPositions, $projected);
        self::assertSame($basic->crossOrganizationClaimPositions, $report->organizationConflictPositions);
        self::denied($report);
    }

    public function test_single_technical_claim_and_fifty_identical_claims_are_not_authorization(): void
    {
        $i = new SyntheticSubmissionBindingBatchInspector();
        $claim = self::c(self::ORG_A, self::SUB_A, self::BIND_A);
        $single = $i->inspect([$claim]);
        self::assertSame(1, $single->claimsInspected);
        self::assertFalse($single->hasAnyCollisionWarnings());
        self::denied($single);

        $fifty = $i->inspect(array_fill(0, 50, $claim));
        self::assertSame(50, $fifty->claimsInspected);
        self::assertCount(1225, $fifty->sameClaimPositions);
        self::assertSame(['first' => 1, 'repeated' => 2], $fifty->sameClaimPositions[0]);
        self::assertSame(['first' => 49, 'repeated' => 50], $fifty->sameClaimPositions[1224]);
        self::assertSame([], $fifty->bindingConflictPositions);
        self::assertSame([], $fifty->organizationConflictPositions);
        self::denied($fifty);
    }

    public function test_all_unique_submission_references_have_no_collision_rights(): void
    {
        $report = (new SyntheticSubmissionBindingBatchInspector())->inspect([
            self::c(self::ORG_A, self::SUB_A, self::BIND_A),
            self::c(self::ORG_A, self::SUB_B, self::BIND_A),
        ]);
        self::assertFalse($report->hasAnyCollisionWarnings());
        self::assertFalse($report->hasBindingConflicts());
        self::denied($report);
    }

    public function test_invalid_member_rejects_entire_batch_without_echoing_identifiers(): void
    {
        $valid = self::c(self::ORG_A, self::SUB_A, self::BIND_A);
        foreach ([
            null, [], 'text', [1 => $valid], [$valid, null], [$valid, 'PRIVATE_PERSON'],
            [$valid, array_merge($valid, ['email' => 'private@example.test'])],
            [$valid, array_merge($valid, ['consent' => true])],
            [$valid, self::c(self::ORG_A, self::SUB_A, 'invalid-binding')],
            [$valid, self::c(self::ORG_A, self::SUB_A, '00000000-0000-0000-0000-000000000000')],
            array_fill(0, 51, $valid),
        ] as $batch) {
            try {
                (new SyntheticSubmissionBindingBatchInspector())->inspect($batch);
                self::fail('Malformed binding batch accepted');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Lote de vinculos tecnicos sinteticos invalido.', $exception->getMessage());
                self::assertStringNotContainsString('PRIVATE_PERSON', $exception->getMessage());
                self::assertStringNotContainsString('private@example.test', $exception->getMessage());
            }
        }
    }
}
