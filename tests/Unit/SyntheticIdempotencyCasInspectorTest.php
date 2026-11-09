<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyCasInspector;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticIdempotencyPhase as Phase;
use App\Core\Leads\SyntheticIdempotencySnapshot;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticIdempotencyCasInspectorTest extends TestCase
{
    private const ORG = '11111111-1111-4111-8111-111111111111';
    private const OTHER_ORG = '22222222-2222-4222-8222-222222222222';
    private const BINDING = '33333333-3333-4333-8333-333333333333';
    private const OTHER_BINDING = '44444444-4444-4444-8444-444444444444';
    private const SUBMISSION = '55555555-5555-4555-8555-555555555555';
    private const OTHER_SUBMISSION = '66666666-6666-4666-8666-666666666666';
    private const SITE = '77777777-7777-4777-8777-777777777777';
    private const FORM = '88888888-8888-4888-8888-888888888888';
    private const DESTINATION = '99999999-9999-4999-8999-999999999999';
    private const OTHER_DESTINATION = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private static function claim(
        string $org = self::ORG,
        string $binding = self::BINDING,
        string $submission = self::SUBMISSION,
        string $site = self::SITE,
        string $form = self::FORM,
        string $destination = self::DESTINATION,
    ): SyntheticIdempotencyClaim {
        return new SyntheticIdempotencyClaim(
            new LeadSubmissionIdentity($org, $binding, $submission),
            $site,
            $form,
            $destination,
        );
    }

    private static function snapshot(
        Phase $phase = Phase::Open,
        int $version = 0,
    ): SyntheticIdempotencySnapshot {
        return new SyntheticIdempotencySnapshot(self::claim(), $phase, $version);
    }

    public function test_untrusted_open_claim_yields_only_a_hypothetical_cas_candidate(): void
    {
        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot(), self::claim(), 0,
        );

        self::assertSame('ATOMIC_STORE_REQUIRED', $result->reasonCode);
        self::assertSame(1, $result->proposedNextVersion);
        self::assertFalse($result->canCommitReservation());
        self::assertFalse($result->canRetryDelivery());
        self::assertFalse($result->canCreateLead());
    }

    public function test_two_simultaneous_readers_cannot_claim_winner_from_pure_inspector(): void
    {
        $inspector = new SyntheticIdempotencyCasInspector();
        $snapshot = self::snapshot();

        $primary = $inspector->inspect($snapshot, self::claim(), 0);
        $contingency = $inspector->inspect($snapshot, self::claim(), 0);

        self::assertSame('ATOMIC_STORE_REQUIRED', $primary->reasonCode);
        self::assertSame('ATOMIC_STORE_REQUIRED', $contingency->reasonCode);
        self::assertSame($primary->proposedNextVersion, $contingency->proposedNextVersion);
        self::assertFalse($primary->canCommitReservation());
        self::assertFalse($contingency->canCommitReservation());
        // A shared transaction/unique constraint is mandatory to pick a winner.
    }

    public function test_stale_cas_version_is_rejected_without_a_retry(): void
    {
        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot(Phase::Open, 1), self::claim(), 0,
        );

        self::assertSame('STALE_SNAPSHOT_RECONCILE', $result->reasonCode);
        self::assertNull($result->proposedNextVersion);
        self::assertFalse($result->canRetryDelivery());
    }

    public static function historicalPhases(): array
    {
        return [
            'in flight' => [Phase::Reserved, 'IN_FLIGHT_REVIEW'],
            'unknown result' => [Phase::OutcomeUnknown, 'UNKNOWN_OUTCOME_LOOKUP_REQUIRED'],
            'delivered claim' => [Phase::DeliveredClaimed, 'DELIVERED_CLAIM_REVIEW'],
            'expired claim' => [Phase::ExpiredClaimed, 'EXPIRED_CLAIM_REVIEW'],
        ];
    }

    #[DataProvider('historicalPhases')]
    public function test_nonopen_phases_never_reserve_or_retry(Phase $phase, string $reason): void
    {
        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot($phase), self::claim(), 0,
        );

        self::assertSame($reason, $result->reasonCode);
        self::assertNull($result->proposedNextVersion);
        self::assertFalse($result->canCommitReservation());
        self::assertFalse($result->canRetryDelivery());
    }

    public static function invalidPinnedContexts(): array
    {
        return [
            'binding switched' => [self::ORG, self::OTHER_BINDING, self::SUBMISSION, self::SITE, self::FORM, self::DESTINATION],
            'site switched' => [self::ORG, self::BINDING, self::SUBMISSION, self::OTHER_DESTINATION, self::FORM, self::DESTINATION],
            'form switched' => [self::ORG, self::BINDING, self::SUBMISSION, self::SITE, self::OTHER_DESTINATION, self::DESTINATION],
            'CRM switched' => [self::ORG, self::BINDING, self::SUBMISSION, self::SITE, self::FORM, self::OTHER_DESTINATION],
        ];
    }

    #[DataProvider('invalidPinnedContexts')]
    public function test_changed_origin_or_destination_is_a_conflict_not_a_fresh_lead(
        string $org,
        string $binding,
        string $submission,
        string $site,
        string $form,
        string $destination,
    ): void {
        $original = self::claim();
        $incoming = self::claim($org, $binding, $submission, $site, $form, $destination);

        self::assertTrue($original->sameLogicalLedgerScopeAs($incoming));
        self::assertFalse($original->samePinnedContextAs($incoming));
        self::assertSame($original->syntheticLedgerFingerprint(), $incoming->syntheticLedgerFingerprint());

        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot(), $incoming, 0,
        );
        self::assertSame('PINNED_CONTEXT_CONFLICT', $result->reasonCode);
        self::assertFalse($result->canCreateLead());
    }

    public function test_different_organization_is_another_logical_scope(): void
    {
        $other = self::claim(self::OTHER_ORG);
        $original = self::claim();
        self::assertFalse($original->sameLogicalLedgerScopeAs($other));
        self::assertNotSame($original->syntheticLedgerFingerprint(), $other->syntheticLedgerFingerprint());
        $result = (new SyntheticIdempotencyCasInspector())->inspect(self::snapshot(), $other, 0);
        self::assertSame('LOGICAL_SCOPE_MISMATCH', $result->reasonCode);
    }

    public function test_distinct_submissions_even_with_same_contact_are_separate(): void
    {
        $original = self::claim();
        $other = self::claim(self::ORG, self::BINDING, self::OTHER_SUBMISSION);
        self::assertNotSame($original->syntheticLedgerFingerprint(), $other->syntheticLedgerFingerprint());
        self::assertFalse($original->sameLogicalLedgerScopeAs($other));
        // No personal contact field participates in the ledger key.
    }

    public function test_no_manual_force_release_or_delivery_operation_is_allowed(): void
    {
        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot(), self::claim(), 0, 'force_delivered',
        );
        self::assertSame('OPERATION_NOT_SUPPORTED', $result->reasonCode);
        self::assertFalse($result->canCreateLead());
    }

    public function test_negative_expected_version_is_rejected(): void
    {
        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot(), self::claim(), -1,
        );
        self::assertSame('VERSION_INVALID', $result->reasonCode);
    }

    public function test_version_overflow_does_not_wrap(): void
    {
        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot(Phase::Open, PHP_INT_MAX), self::claim(), PHP_INT_MAX,
        );
        self::assertSame('VERSION_OVERFLOW', $result->reasonCode);
        self::assertNull($result->proposedNextVersion);
    }

    public function test_invalid_synthetic_origin_uuid_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::claim(site: 'untrusted-site-id');
    }

    public function test_negative_ledger_snapshot_version_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::snapshot(version: -1);
    }

    public function test_assessment_does_not_echo_technical_ids_or_personal_payload(): void
    {
        $result = (new SyntheticIdempotencyCasInspector())->inspect(
            self::snapshot(), self::claim(), 0,
        );
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::ORG, $json);
        self::assertStringNotContainsString(self::SUBMISSION, $json);
        self::assertStringNotContainsString(self::DESTINATION, $json);
        self::assertStringNotContainsString('lead@example.invalid', $json);
        self::assertFalse($result->canCommitReservation());
    }
}
