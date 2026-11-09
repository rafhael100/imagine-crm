<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadHistoryAssessment;
use App\Core\Leads\SyntheticLeadHistoryClaim;
use App\Core\Leads\SyntheticLeadHistoryContinuationInspector;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadHistoryContinuationInspectorTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const OTHER_TENANT = '99999999-9999-4999-8999-999999999999';
    private const LEAD = '22222222-2222-4222-8222-222222222222';
    private const OTHER_LEAD = '88888888-8888-4888-8888-888888888888';
    private const ACTOR = '77777777-7777-4777-8777-777777777777';

    private static function claim(
        int $position,
        int $event,
        string $tenant = self::TENANT,
        string $lead = self::LEAD,
        ?string $actor = self::ACTOR,
    ): SyntheticLeadHistoryClaim {
        return new SyntheticLeadHistoryClaim(
            $tenant,
            $lead,
            sprintf('33333333-3333-4333-8333-%012x', $event),
            $actor,
            $position,
            'stage_changed',
        );
    }

    private static function inspect(
        mixed $first,
        mixed $second,
        string $tenant = self::TENANT,
        string $lead = self::LEAD,
    ): SyntheticLeadHistoryAssessment {
        return (new SyntheticLeadHistoryContinuationInspector())->inspect($tenant, $lead, $first, $second);
    }

    private static function assertClosed(string $reason, SyntheticLeadHistoryAssessment $result): void
    {
        self::assertSame($reason, $result->reasonCode);
        self::assertSame(['code' => $reason], $result->signal());
        self::assertFalse($result->canReadHistory());
        self::assertFalse($result->canExportHistory());
        self::assertFalse($result->canWriteHistory());
        self::assertFalse($result->canProveActorOrChronology());
        self::assertSame(['code'], array_keys($result->signal()));
    }

    public function test_two_adjacent_consistent_slices_still_do_not_prove_history(): void
    {
        self::assertClosed('PROVENANCE_UNVERIFIED', self::inspect(
            [self::claim(40, 1), self::claim(41, 2)],
            [self::claim(42, 3), self::claim(43, 4)],
        ));
        self::assertClosed('PROVENANCE_UNVERIFIED', self::inspect(
            [self::claim(1, 1)], [self::claim(2, 2)],
        ));
    }

    public function test_duplicates_across_slices_are_detected_without_merging_records(): void
    {
        self::assertClosed('EVENT_REFERENCE_REUSED', self::inspect(
            [self::claim(1, 1), self::claim(2, 2)],
            [self::claim(3, 2), self::claim(4, 4)],
        ));
        self::assertClosed('EVENT_REFERENCE_REUSED', self::inspect(
            [self::claim(1, 1)],
            [self::claim(2, 1)],
        ));
    }

    public function test_case_normalization_cannot_hide_replayed_event_identifiers(): void
    {
        $replayed = new SyntheticLeadHistoryClaim(
            strtoupper(self::TENANT),
            strtoupper(self::LEAD),
            strtoupper('33333333-3333-4333-8333-000000000001'),
            strtoupper(self::ACTOR),
            2,
            'stage_changed',
        );
        self::assertClosed('EVENT_REFERENCE_REUSED', self::inspect(
            [self::claim(1, 1)],
            [$replayed],
        ));
    }

    public function test_gaps_and_overlaps_across_slice_boundary_do_not_pass(): void
    {
        self::assertClosed('HISTORY_ORDER_UNVERIFIED', self::inspect(
            [self::claim(10, 1), self::claim(11, 2)],
            [self::claim(13, 3)],
        ));
        self::assertClosed('HISTORY_ORDER_UNVERIFIED', self::inspect(
            [self::claim(10, 1), self::claim(11, 2)],
            [self::claim(11, 3)],
        ));
        self::assertClosed('HISTORY_ORDER_UNVERIFIED', self::inspect(
            [self::claim(1000000, 1)],
            [self::claim(1, 2)],
        ));
    }

    public function test_each_slice_is_independently_validated_for_tenant_lead_and_actor(): void
    {
        self::assertClosed('HISTORY_SCOPE_MISMATCH', self::inspect(
            [self::claim(1, 1)], [self::claim(2, 2, tenant: self::OTHER_TENANT)],
        ));
        self::assertClosed('HISTORY_SCOPE_MISMATCH', self::inspect(
            [self::claim(1, 1, tenant: self::OTHER_TENANT)], [self::claim(2, 2)],
        ));
        self::assertClosed('HISTORY_SCOPE_MISMATCH', self::inspect(
            [self::claim(1, 1)], [self::claim(2, 2, lead: self::OTHER_LEAD)],
        ));
        self::assertClosed('ACTOR_REFERENCE_MISSING', self::inspect(
            [self::claim(1, 1)], [self::claim(2, 2, actor: null)],
        ));
        self::assertClosed('ACTOR_REFERENCE_MISSING', self::inspect(
            [self::claim(1, 1, actor: null)], [self::claim(2, 2)],
        ));
    }

    public function test_empty_untrusted_or_malformed_slices_fail_closed(): void
    {
        self::assertClosed('HISTORY_EVIDENCE_MISSING', self::inspect([], [self::claim(2, 2)]));
        self::assertClosed('HISTORY_EVIDENCE_MISSING', self::inspect([self::claim(1, 1)], []));
        self::assertClosed('CONTRACT_INVALID', self::inspect(null, [self::claim(2, 2)]));
        self::assertClosed('CONTRACT_INVALID', self::inspect([self::claim(1, 1)], 'not a list'));
        self::assertClosed('CONTRACT_INVALID', self::inspect(['one' => self::claim(1, 1)], [self::claim(2, 2)]));
        self::assertClosed('CONTRACT_INVALID', self::inspect([self::claim(1, 1)], [['event' => 'fake']]));
        self::assertClosed('CONTRACT_INVALID', self::inspect(
            [self::claim(1, 1)],
            [self::claim(2, 2)],
            'unverified-tenant',
        ));
    }

    public function test_two_maximum_slices_are_bounded_independently_to_32_claims(): void
    {
        $first = [];
        $second = [];
        for ($i = 1; $i <= 32; $i++) {
            $first[] = self::claim($i, $i);
            $second[] = self::claim($i + 32, $i + 32);
        }
        self::assertClosed('PROVENANCE_UNVERIFIED', self::inspect($first, $second));
        self::assertClosed('CONTRACT_INVALID', self::inspect([...$first, self::claim(33, 65)], $second));
        self::assertClosed('CONTRACT_INVALID', self::inspect($first, [...$second, self::claim(65, 66)]));
    }

    public function test_claims_from_an_unverified_actor_and_tenant_never_become_access_rights(): void
    {
        self::assertClosed('PROVENANCE_UNVERIFIED', self::inspect(
            [self::claim(1, 1, tenant: self::OTHER_TENANT)],
            [self::claim(2, 2, tenant: self::OTHER_TENANT)],
            self::OTHER_TENANT,
        ));
        self::assertClosed('PROVENANCE_UNVERIFIED', self::inspect(
            [self::claim(1, 1, lead: self::OTHER_LEAD)],
            [self::claim(2, 2, lead: self::OTHER_LEAD)],
            self::TENANT,
            self::OTHER_LEAD,
        ));
    }

    public function test_serialized_signal_never_contains_claimed_identity_or_record(): void
    {
        foreach ([
            self::inspect([self::claim(1, 1)], [self::claim(2, 2)]),
            self::inspect([self::claim(1, 1)], [self::claim(2, 1)]),
            self::inspect([self::claim(1, 1)], [self::claim(3, 3, tenant: self::OTHER_TENANT)]),
        ] as $result) {
            $json = json_encode($result->signal(), JSON_THROW_ON_ERROR);
            foreach ([self::TENANT, self::OTHER_TENANT, self::LEAD, self::ACTOR, 'eventReference', 'claimedPosition'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $json);
            }
            self::assertFalse($result->canReadHistory());
            self::assertFalse($result->canExportHistory());
        }
    }
}
