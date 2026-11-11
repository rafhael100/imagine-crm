<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticSubmissionClaimBatchInspector;
use App\Core\Leads\SyntheticSubmissionClaimBatchSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSubmissionClaimBatchInspectorTest extends TestCase
{
    private const A = '11111111-1111-4111-8111-111111111111';
    private const B = '22222222-2222-4222-8222-222222222222';
    private const S1 = '33333333-3333-4333-8333-333333333333';
    private const S2 = '44444444-4444-4444-8444-444444444444';

    /** @return array{organization_reference: string, submission_reference: string} */
    private static function claim(string $org, string $submission): array
    {
        return ['organization_reference' => $org, 'submission_reference' => $submission];
    }

    private static function assertDeniedAndNonLeaking(SyntheticSubmissionClaimBatchSummary $r): void
    {
        self::assertFalse($r->canTreatAsAuthorizedRetry());
        self::assertFalse($r->canMergeContacts());
        self::assertFalse($r->canPersistLead());
        self::assertFalse($r->canAcknowledgeDelivery());
        $serialized = json_encode($r, JSON_THROW_ON_ERROR);
        foreach ([self::A, self::B, self::S1, self::S2, 'PRIVATE_PERSON', 'private@example.test'] as $text) {
            self::assertStringNotContainsString($text, $serialized);
        }
    }

    public function test_repeated_claims_and_cross_organization_conflict_produce_only_positions(): void
    {
        $report = (new SyntheticSubmissionClaimBatchInspector())->inspect([
            self::claim(self::A, self::S1),
            self::claim(self::A, self::S2),
            self::claim(strtoupper(self::A), strtoupper(self::S1)),
            self::claim(self::B, self::S1),
            self::claim(self::B, self::S2),
        ]);

        self::assertSame(5, $report->claimsInspected);
        self::assertSame([['first' => 1, 'repeated' => 3]], $report->sameOrganizationClaimPositions);
        self::assertSame([
            ['first' => 1, 'conflicting' => 4],
            ['first' => 2, 'conflicting' => 5],
            ['first' => 3, 'conflicting' => 4],
        ], $report->crossOrganizationClaimPositions);
        self::assertTrue($report->hasRepeatedSubmissionClaims());
        self::assertTrue($report->hasCrossOrganizationConflicts());
        self::assertDeniedAndNonLeaking($report);
    }

    public function test_distinct_submissions_do_not_trigger_contact_deduplication(): void
    {
        $report = (new SyntheticSubmissionClaimBatchInspector())->inspect([
            self::claim(self::A, self::S1),
            self::claim(self::A, self::S2),
        ]);
        self::assertSame(2, $report->claimsInspected);
        self::assertSame([], $report->sameOrganizationClaimPositions);
        self::assertSame([], $report->crossOrganizationClaimPositions);
        self::assertFalse($report->hasRepeatedSubmissionClaims());
        self::assertFalse($report->hasCrossOrganizationConflicts());
        self::assertDeniedAndNonLeaking($report);
    }

    public function test_one_claim_and_maximum_fifty_identical_claims_remain_denied(): void
    {
        $inspector = new SyntheticSubmissionClaimBatchInspector();
        $single = $inspector->inspect([self::claim(self::A, self::S1)]);
        self::assertSame(1, $single->claimsInspected);
        self::assertSame([], $single->sameOrganizationClaimPositions);
        self::assertDeniedAndNonLeaking($single);

        $fifty = $inspector->inspect(array_fill(0, 50, self::claim(self::A, self::S1)));
        self::assertSame(50, $fifty->claimsInspected);
        self::assertCount(1225, $fifty->sameOrganizationClaimPositions);
        self::assertSame(['first' => 1, 'repeated' => 2], $fifty->sameOrganizationClaimPositions[0]);
        self::assertSame(['first' => 49, 'repeated' => 50], $fifty->sameOrganizationClaimPositions[1224]);
        self::assertSame([], $fifty->crossOrganizationClaimPositions);
        self::assertDeniedAndNonLeaking($fifty);
    }

    public function test_every_cross_organization_pair_is_reported_even_for_triples(): void
    {
        $batch = [
            self::claim(self::A, self::S1),
            self::claim(self::B, self::S1),
            self::claim(self::A, self::S1),
        ];
        $report = (new SyntheticSubmissionClaimBatchInspector())->inspect($batch);
        self::assertSame([['first' => 1, 'repeated' => 3]], $report->sameOrganizationClaimPositions);
        self::assertSame([
            ['first' => 1, 'conflicting' => 2],
            ['first' => 2, 'conflicting' => 3],
        ], $report->crossOrganizationClaimPositions);
        self::assertDeniedAndNonLeaking($report);
    }

    public function test_malformed_batch_is_rejected_whole_without_echoing_input(): void
    {
        $valid = self::claim(self::A, self::S1);
        foreach ([
            null, [], 'list', [$valid, null], [$valid, 'PRIVATE_PERSON'],
            ['first' => $valid],
            [$valid, array_merge($valid, ['email' => 'private@example.test'])],
            [$valid, self::claim('bad tenant', self::S1)],
            [$valid, self::claim(self::A, '00000000-0000-0000-0000-000000000000')],
            array_fill(0, 51, $valid),
        ] as $batch) {
            try {
                (new SyntheticSubmissionClaimBatchInspector())->inspect($batch);
                self::fail('Invalid untrusted submission batch accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Lote de referencias sinteticas invalido.', $e->getMessage());
                self::assertStringNotContainsString('PRIVATE_PERSON', $e->getMessage());
                self::assertStringNotContainsString('private@example.test', $e->getMessage());
            }
        }
    }
}
