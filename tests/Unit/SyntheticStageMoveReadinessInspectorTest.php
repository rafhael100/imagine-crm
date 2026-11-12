<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticStageMoveReadinessInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticStageMoveReadinessInspectorTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function event(int $revision, string $action, int $stage, string $second, bool $confirmed = false): array
    {
        return [
            'revision' => $revision,
            'action' => $action,
            'stage' => $stage,
            'confirmed' => $confirmed,
            'loss_reason_code' => null,
            'reopen_reason_code' => null,
            'occurred_at_utc' => '2026-11-01T00:00:'.$second.'Z',
        ];
    }

    /** @return array<string, bool> */
    private static function presence(bool $assignee = false): array
    {
        return [
            'amount' => true, 'assignee' => $assignee, 'contact' => true,
            'expected_close' => false, 'source' => true,
        ];
    }

    /** @return list<list<string>> */
    private static function policy(): array
    {
        return [[], ['contact'], ['contact', 'assignee', 'amount'], ['expected_close', 'contact']];
    }

    public function test_composes_stage_fields_and_revision_without_granting_any_authority(): void
    {
        $events = [self::event(1, 'create', 0, '00')];
        $copy = $events;
        $inspector = new SyntheticStageMoveReadinessInspector();

        $missing = $inspector->inspect($events, 1, 2, self::presence(), self::policy());
        self::assertSame('FIELDS_MISSING', $missing['reason_code']);
        self::assertSame(1, $missing['observed_revision']);
        self::assertSame(0, $missing['source_stage']);
        self::assertSame(2, $missing['target_stage']);
        self::assertSame(3, $missing['required_count']);
        self::assertSame(1, $missing['missing_count']);
        self::assertSame(['assignee'], $missing['missing_field_codes']);
        self::assertFalse($missing['fields_present_claimed']);

        $complete = $inspector->inspect($events, 1, 2, self::presence(true), self::policy());
        self::assertSame('FIELDS_PRESENT_UNVERIFIED', $complete['reason_code']);
        self::assertTrue($complete['fields_present_claimed']);
        self::assertSame(0, $complete['missing_count']);
        foreach ([
            'history_verified', 'field_values_verified', 'policy_verified',
            'tenant_verified', 'concurrency_verified', 'authorization_verified',
            'authorized', 'can_move', 'can_persist',
        ] as $deny) {
            self::assertFalse($complete[$deny], $deny);
        }
        self::assertTrue($complete['informational_only']);
        self::assertSame($copy, $events);
        self::assertSame($complete, $inspector->inspect($events, 1, 2, self::presence(true), self::policy()));
    }

    public function test_stale_closed_uncreated_and_same_stage_never_release_fields(): void
    {
        $inspector = new SyntheticStageMoveReadinessInspector();
        $created = [self::event(1, 'create', 0, '00')];
        $won = [...$created, self::event(2, 'win', 0, '01', true)];
        foreach ([
            [[], 0, 2, 'NO_OPPORTUNITY'],
            [$created, 0, 2, 'STALE_REVISION'],
            [$won, 1, 2, 'STALE_REVISION'],
            [$won, 2, 2, 'CLOSED_CLAIM'],
            [$created, 1, 0, 'SAME_STAGE'],
        ] as [$events, $revision, $target, $reason]) {
            $out = $inspector->inspect($events, $revision, $target, self::presence(true), self::policy());
            self::assertSame($reason, $out['reason_code']);
            self::assertSame(0, $out['required_count']);
            self::assertSame(0, $out['missing_count']);
            self::assertSame([], $out['missing_field_codes']);
            self::assertFalse($out['fields_present_claimed']);
            self::assertFalse($out['authorized']);
            self::assertFalse($out['can_move']);
        }
    }

    public function test_maximum_ledger_length_prevents_another_synthetic_revision(): void
    {
        $events = [self::event(1, 'create', 0, '00')];
        for ($revision = 2; $revision <= 24; ++$revision) {
            $events[] = self::event($revision, 'move', $revision % 2 === 0 ? 1 : 0, sprintf('%02d', $revision - 1));
        }
        $out = (new SyntheticStageMoveReadinessInspector())
            ->inspect($events, 24, 3, self::presence(true), self::policy());
        self::assertSame('EVENT_LIMIT_REACHED', $out['reason_code']);
        self::assertSame(24, $out['observed_revision']);
        self::assertFalse($out['fields_present_claimed']);
        self::assertFalse($out['can_persist']);
    }

    public function test_malformed_claims_are_rejected_even_when_history_is_stale(): void
    {
        $events = [self::event(1, 'create', 0, '00')];
        $bad = [
            [$events, '1', 2, self::presence(), self::policy()],
            [$events, -1, 2, self::presence(), self::policy()],
            [$events, 25, 2, self::presence(), self::policy()],
            [$events, 0, '2', self::presence(), self::policy()],
            [$events, 0, 4, self::presence(), self::policy()],
            [$events, 0, 2, ['email' => 'private@example.test'], self::policy()],
            [$events, 0, 2, array_merge(self::presence(), ['amount' => 1]), self::policy()],
            [$events, 0, 2, self::presence(), [[], ['unknown'], [], []]],
            [[self::event(1, 'win', 0, '00', true)], 1, 2, self::presence(), self::policy()],
        ];
        foreach ($bad as [$history, $revision, $target, $presence, $policy]) {
            try {
                (new SyntheticStageMoveReadinessInspector())
                    ->inspect($history, $revision, $target, $presence, $policy);
                self::fail('Malformed or sensitive synthetic stage claim was accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertStringNotContainsString('private@example.test', $e->getMessage());
            }
        }
    }

    public function test_public_projection_contains_no_input_values_and_no_permissions(): void
    {
        $out = (new SyntheticStageMoveReadinessInspector())->inspect(
            [self::event(1, 'create', 0, '00')], 1, 2, self::presence(), self::policy(),
        );
        self::assertSame([
            'reason_code', 'observed_revision', 'requested_revision', 'source_stage',
            'target_stage', 'required_count', 'missing_count', 'missing_field_codes',
            'fields_present_claimed', 'history_verified', 'field_values_verified',
            'policy_verified', 'tenant_verified', 'concurrency_verified',
            'authorization_verified', 'informational_only', 'authorized',
            'can_move', 'can_persist',
        ], array_keys($out));
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['email', 'customer', 'actor', 'tenant_id', 'principal',
            'opportunity_id', 'occurred_at_utc', 'description', 'phone'] as $secret) {
            self::assertStringNotContainsString('"'.$secret.'"', $json);
        }
        self::assertFalse($out['can_move']);
        self::assertFalse($out['can_persist']);
    }
}
