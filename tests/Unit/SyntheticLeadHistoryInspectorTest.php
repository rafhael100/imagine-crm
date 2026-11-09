<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadHistoryAssessment;
use App\Core\Leads\SyntheticLeadHistoryClaim;
use App\Core\Leads\SyntheticLeadHistoryInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadHistoryInspectorTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const OTHER_TENANT = '99999999-9999-4999-8999-999999999999';
    private const LEAD = '22222222-2222-4222-8222-222222222222';
    private const OTHER_LEAD = '88888888-8888-4888-8888-888888888888';
    private const ACTOR = '77777777-7777-4777-8777-777777777777';

    private static function claim(
        int $position = 1,
        int $event = 1,
        string $tenant = self::TENANT,
        string $lead = self::LEAD,
        ?string $actor = self::ACTOR,
        string $kind = 'note_added',
    ): SyntheticLeadHistoryClaim {
        return new SyntheticLeadHistoryClaim(
            $tenant,
            $lead,
            sprintf('33333333-3333-4333-8333-%012x', $event),
            $actor,
            $position,
            $kind,
        );
    }

    private static function inspect(mixed $claims, string $tenant = self::TENANT, string $lead = self::LEAD): SyntheticLeadHistoryAssessment
    {
        return (new SyntheticLeadHistoryInspector())->inspect($tenant, $lead, $claims);
    }

    private static function assertClosed(SyntheticLeadHistoryAssessment $assessment, string $reason): void
    {
        self::assertSame($reason, $assessment->reasonCode);
        self::assertSame(['code' => $reason], $assessment->signal());
        self::assertFalse($assessment->canReadHistory());
        self::assertFalse($assessment->canExportHistory());
        self::assertFalse($assessment->canWriteHistory());
        self::assertFalse($assessment->canProveActorOrChronology());
        self::assertSame(['code'], array_keys($assessment->signal()));
    }

    public function test_even_an_ordered_slice_with_actors_is_only_unverified_provenance(): void
    {
        $slice = [
            self::claim(position: 40, event: 1, kind: 'created'),
            self::claim(position: 41, event: 2, kind: 'assigned'),
            self::claim(position: 42, event: 3, kind: 'note_added'),
            self::claim(position: 43, event: 4, kind: 'stage_changed'),
            self::claim(position: 44, event: 5, kind: 'archived'),
            self::claim(position: 45, event: 6, kind: 'restored'),
        ];
        self::assertClosed(self::inspect($slice), 'PROVENANCE_UNVERIFIED');
        self::assertSame([40, 41, 42, 43, 44, 45], array_map(
            static fn (SyntheticLeadHistoryClaim $claim): int => $claim->claimedPosition,
            $slice,
        ));
    }

    public function test_cross_tenant_actor_claim_cannot_transfer_a_history(): void
    {
        self::assertClosed(
            self::inspect([self::claim(), self::claim(position: 2, event: 2, tenant: self::OTHER_TENANT)]),
            'HISTORY_SCOPE_MISMATCH',
        );
        self::assertClosed(self::inspect([self::claim(tenant: self::OTHER_TENANT)]), 'HISTORY_SCOPE_MISMATCH');
        self::assertClosed(self::inspect([self::claim(lead: self::OTHER_LEAD)]), 'HISTORY_SCOPE_MISMATCH');
        // Even when the caller changes their expected tenant, the claim is
        // not independently authenticated or authorized.
        self::assertClosed(
            self::inspect([self::claim(tenant: self::OTHER_TENANT)], self::OTHER_TENANT),
            'PROVENANCE_UNVERIFIED',
        );
    }

    public function test_event_reuse_and_position_conflicts_are_never_automatically_repaired(): void
    {
        self::assertClosed(
            self::inspect([self::claim(), self::claim(position: 2, event: 1)]),
            'EVENT_REFERENCE_REUSED',
        );
        self::assertClosed(
            self::inspect([self::claim(), self::claim(position: 1, event: 2)]),
            'HISTORY_ORDER_UNVERIFIED',
        );
        self::assertClosed(
            self::inspect([self::claim(), self::claim(position: 3, event: 2)]),
            'HISTORY_ORDER_UNVERIFIED',
        );
        self::assertClosed(
            self::inspect([self::claim(position: 2), self::claim(position: 1, event: 2)]),
            'HISTORY_ORDER_UNVERIFIED',
        );
    }

    public function test_claimed_actor_is_not_automatically_invented(): void
    {
        self::assertClosed(
            self::inspect([self::claim(actor: null)]),
            'ACTOR_REFERENCE_MISSING',
        );
        self::assertClosed(
            self::inspect([self::claim(), self::claim(position: 2, event: 2, actor: null)]),
            'ACTOR_REFERENCE_MISSING',
        );
    }

    public function test_empty_or_non_list_or_oversized_samples_are_closed(): void
    {
        self::assertClosed(self::inspect([]), 'HISTORY_EVIDENCE_MISSING');
        self::assertClosed(self::inspect(null), 'CONTRACT_INVALID');
        self::assertClosed(self::inspect([['tenant' => self::TENANT]]), 'CONTRACT_INVALID');
        self::assertClosed(self::inspect(['event' => self::claim()]), 'CONTRACT_INVALID');

        $claims = [];
        for ($i = 1; $i <= 33; $i++) {
            $claims[] = self::claim(position: $i, event: $i);
        }
        self::assertClosed(self::inspect(array_slice($claims, 0, 32)), 'PROVENANCE_UNVERIFIED');
        self::assertClosed(self::inspect($claims), 'CONTRACT_INVALID');
    }

    public function test_invalid_expected_ids_are_closed_and_never_echoed(): void
    {
        foreach ([
            '', ' ', 'customer@example.invalid', '00000000-0000-0000-0000-000000000000',
            self::TENANT."\n",
        ] as $invalid) {
            self::assertClosed(self::inspect([self::claim()], $invalid), 'CONTRACT_INVALID');
            self::assertClosed(self::inspect([self::claim()], self::TENANT, $invalid), 'CONTRACT_INVALID');
        }
    }

    public function test_claim_types_reject_injected_values_with_a_generic_exception(): void
    {
        $badClaims = [
            [self::TENANT, self::LEAD, '', self::ACTOR, 1, 'created'],
            [self::TENANT, self::LEAD, '00000000-0000-0000-0000-000000000000', self::ACTOR, 1, 'created'],
            [self::TENANT, self::LEAD, '33333333-3333-4333-8333-000000000001', 'contact@example.invalid', 1, 'created'],
            [self::TENANT, self::LEAD, '33333333-3333-4333-8333-000000000001', self::ACTOR, 0, 'created'],
            [self::TENANT, self::LEAD, '33333333-3333-4333-8333-000000000001', self::ACTOR, 1000001, 'created'],
            [self::TENANT, self::LEAD, '33333333-3333-4333-8333-000000000001', self::ACTOR, 1, 'grant_admin'],
            [self::TENANT, self::LEAD, '33333333-3333-4333-8333-000000000001', self::ACTOR, 1, "note_added\nsecret"],
        ];
        foreach ($badClaims as $args) {
            try {
                new SyntheticLeadHistoryClaim(...$args);
                self::fail('The untrusted history claim was accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Invalid synthetic history claim.', $exception->getMessage());
                self::assertStringNotContainsString('contact@example.invalid', $exception->getMessage());
                self::assertStringNotContainsString('secret', $exception->getMessage());
            }
        }
    }

    public function test_metadata_signal_never_contains_ids_or_event_fields(): void
    {
        $a = self::claim();
        $b = self::claim(position: 2, event: 2);
        self::assertSame(strtolower(self::TENANT), $a->tenantReference);
        self::assertSame(strtolower(self::LEAD), $a->leadReference);
        self::assertSame(self::ACTOR, $a->claimedActorReference);
        self::assertSame('PROVENANCE_UNVERIFIED', self::inspect([$a, $b])->reasonCode);

        foreach ([
            self::inspect([$a, $b]),
            self::inspect([$a, self::claim(position: 2, event: 3, tenant: self::OTHER_TENANT)]),
            self::inspect([$a, self::claim(position: 2, event: 1)]),
        ] as $result) {
            $signal = json_encode($result, JSON_THROW_ON_ERROR);
            foreach ([
                self::TENANT, self::LEAD, self::ACTOR, 'note_added', 'claimedPosition',
                'tenantReference', 'leadReference', 'eventReference',
            ] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $signal);
            }
            self::assertFalse($result->canReadHistory());
            self::assertFalse($result->canWriteHistory());
        }
    }

    public function test_casing_of_claim_references_does_not_create_provenance(): void
    {
        $claim = new SyntheticLeadHistoryClaim(
            strtoupper(self::TENANT),
            strtoupper(self::LEAD),
            strtoupper('33333333-3333-4333-8333-000000000001'),
            strtoupper(self::ACTOR),
            1,
            'created',
        );
        self::assertClosed(
            self::inspect([$claim], strtoupper(self::TENANT), strtoupper(self::LEAD)),
            'PROVENANCE_UNVERIFIED',
        );
    }
}
