<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticOpportunityTransitionLedger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticOpportunityTransitionLedgerTest extends TestCase
{
    /** @return array<string,mixed> */
    private static function step(
        int $revision,
        string $action,
        int $stage,
        string $time,
        bool $confirmed = false,
        ?string $loss = null,
        ?string $reopen = null,
    ): array {
        return [
            'revision' => $revision,
            'action' => $action,
            'stage' => $stage,
            'confirmed' => $confirmed,
            'loss_reason_code' => $loss,
            'reopen_reason_code' => $reopen,
            'occurred_at_utc' => $time,
        ];
    }

    public function test_open_move_win_reopen_lose_reopen_win_does_not_count_verified_revenue(): void
    {
        $records = [
            self::step(1, 'create', 0, '2026-11-01T10:00:00Z'),
            self::step(2, 'move', 1, '2026-11-01T10:00:01Z'),
            self::step(3, 'win', 1, '2026-11-01T10:00:02Z', true),
            self::step(4, 'reopen', 1, '2026-11-01T10:00:03Z', false, null, 'correction'),
            self::step(5, 'move', 2, '2026-11-01T10:00:04Z'),
            self::step(6, 'lose', 2, '2026-11-01T10:00:05Z', true, 'timing'),
            self::step(7, 'reopen', 2, '2026-11-01T10:00:06Z', false, null, 'customer_returned'),
            self::step(8, 'win', 2, '2026-11-01T10:00:07Z', true),
        ];
        $copy = $records;
        $report = (new SyntheticOpportunityTransitionLedger())->inspect($records);

        self::assertSame(8, $report['event_count']);
        self::assertSame(8, $report['latest_revision']);
        self::assertSame(2, $report['latest_stage']);
        self::assertSame('won', $report['latest_claimed_status']);
        self::assertSame(2, $report['claimed_moves']);
        self::assertSame(2, $report['claimed_wins']);
        self::assertSame(1, $report['claimed_losses']);
        self::assertSame(2, $report['claimed_reopenings']);
        self::assertSame([
            'price' => 0, 'timing' => 1, 'competition' => 0,
            'no_response' => 0, 'other' => 0,
        ], $report['claimed_loss_reasons']);
        self::assertSame(1, array_sum($report['claimed_loss_reasons']));
        self::assertSame(0, $report['recognized_wins']);
        self::assertSame(0, $report['recognized_revenue_cents']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'history_verified', 'tenant_policy_verified',
            'concurrency_verified', 'confirmation_verified',
            'authorized', 'can_move', 'can_mark_won', 'can_mark_lost',
            'can_reopen', 'can_update_targets', 'can_invoice', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag], $flag);
        }
        self::assertSame($copy, $records);
        self::assertSame($report, (new SyntheticOpportunityTransitionLedger())->inspect($records));
    }

    public function test_empty_and_24_consecutive_state_movements_are_bounded(): void
    {
        $reducer = new SyntheticOpportunityTransitionLedger();
        $empty = $reducer->inspect([]);
        self::assertSame(0, $empty['event_count']);
        self::assertNull($empty['latest_stage']);
        self::assertNull($empty['latest_claimed_status']);
        self::assertSame(0, $empty['recognized_wins']);

        $steps = [self::step(1, 'create', 0, '2026-11-01T00:00:00Z')];
        for ($revision = 2; $revision <= 24; ++$revision) {
            $steps[] = self::step(
                $revision,
                'move',
                $revision % 2 === 0 ? 1 : 0,
                sprintf('2026-11-01T00:00:%02dZ', $revision - 1),
            );
        }
        $report = $reducer->inspect($steps);
        self::assertSame(24, $report['event_count']);
        self::assertSame(23, $report['claimed_moves']);
        self::assertSame(0, $report['claimed_wins']);
        self::assertSame(0, $report['recognized_revenue_cents']);

        $steps[] = self::step(25, 'move', 0, '2026-11-01T00:00:24Z');
        $this->expectException(InvalidArgumentException::class);
        $reducer->inspect($steps);
    }

    public function test_stale_revisions_unconfirmed_outcomes_and_missing_justifications_are_refused(): void
    {
        $create = self::step(1, 'create', 0, '2026-11-01T00:00:00Z');
        $move = self::step(2, 'move', 1, '2026-11-01T00:00:01Z');
        $won = self::step(3, 'win', 1, '2026-11-01T00:00:02Z', true);
        $lost = self::step(3, 'lose', 1, '2026-11-01T00:00:02Z', true, 'price');
        $bad = [
            null, true, 17, 'move', [1 => $create], [null],
            [self::step(2, 'create', 0, '2026-11-01T00:00:00Z')],
            [self::step(1, 'move', 0, '2026-11-01T00:00:00Z')],
            [$create, $create],
            [$create, self::step(2, 'create', 0, '2026-11-01T00:00:01Z')],
            [$create, self::step(1, 'move', 1, '2026-11-01T00:00:01Z')],
            [$create, self::step(3, 'move', 1, '2026-11-01T00:00:01Z')],
            [$create, self::step(2, 'move', 0, '2026-11-01T00:00:01Z')],
            [$create, self::step(2, 'move', 1, '2026-11-01T00:00:00Z')],
            [$create, self::step(2, 'win', 0, '2026-11-01T00:00:01Z')],
            [$create, self::step(2, 'lose', 0, '2026-11-01T00:00:01Z', true)],
            [$create, self::step(2, 'lose', 0, '2026-11-01T00:00:01Z', true, 'private@example.test')],
            [$create, self::step(2, 'win', 1, '2026-11-01T00:00:01Z', true)],
            [$create, self::step(2, 'reopen', 0, '2026-11-01T00:00:01Z', false, null, 'correction')],
            [$create, $move, $won, self::step(4, 'move', 2, '2026-11-01T00:00:03Z')],
            [$create, $move, $won, self::step(4, 'reopen', 1, '2026-11-01T00:00:03Z')],
            [$create, $move, $won, self::step(4, 'reopen', 1, '2026-11-01T00:00:03Z', true, null, 'correction')],
            [$create, $move, $won, self::step(4, 'reopen', 2, '2026-11-01T00:00:03Z', false, null, 'correction')],
            [$create, $move, $won, self::step(4, 'reopen', 1, '2026-11-01T00:00:03Z', false, null, 'unverified')],
            [$create, $move, $won, self::step(4, 'win', 1, '2026-11-01T00:00:03Z', true)],
            [$create, $move, $won, self::step(4, 'lose', 1, '2026-11-01T00:00:03Z', true, 'timing')],
            [$create, $move, $lost, self::step(4, 'lose', 1, '2026-11-01T00:00:03Z', true, 'price')],
            [$create, self::step(2, 'win', 0, '2026-11-01T00:00:01Z', true, 'price')],
            [$create, self::step(2, 'move', 1, '2026-11-01T00:00:01Z', false, null, 'correction')],
            [array_merge($create, ['tenant' => 'private@example.test'])],
            [['revision' => 1, 'action' => 'create', 'stage' => 0, 'confirmed' => false, 'loss_reason_code' => null, 'reopen_reason_code' => null]],
            [array_merge($create, ['stage' => 4])],
            [array_merge($create, ['stage' => -1])],
            [array_merge($create, ['stage' => '0'])],
            [array_merge($create, ['revision' => '1'])],
            [array_merge($create, ['confirmed' => 'false'])],
            [array_merge($create, ['action' => 'pay'])],
            [array_merge($create, ['occurred_at_utc' => '2026-02-30T00:00:00Z'])],
            [array_merge($create, ['occurred_at_utc' => '2026-11-01 00:00:00Z'])],
            [array_merge($create, ['occurred_at_utc' => '2026-11-01T00:00:00+00:00'])],
        ];

        foreach ($bad as $steps) {
            try {
                (new SyntheticOpportunityTransitionLedger())->inspect($steps);
                self::fail('Malformed or stale synthetic opportunity transition was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Historico sintetico de oportunidade invalido.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_aggregate_projection_does_not_expose_event_time_reasons_or_tenant(): void
    {
        $out = (new SyntheticOpportunityTransitionLedger())->inspect([
            self::step(1, 'create', 0, '2026-11-01T00:00:00Z'),
            self::step(2, 'lose', 0, '2026-11-01T00:00:01Z', true, 'other'),
        ]);
        self::assertSame([
            'event_count', 'latest_revision', 'latest_stage',
            'latest_claimed_status', 'claimed_moves', 'claimed_wins',
            'claimed_losses', 'claimed_reopenings', 'claimed_loss_reasons',
            'recognized_wins', 'recognized_revenue_cents',
            'history_verified', 'tenant_policy_verified',
            'concurrency_verified', 'confirmation_verified',
            'informational_only', 'authorized', 'can_move', 'can_mark_won',
            'can_mark_lost', 'can_reopen', 'can_update_targets',
            'can_invoice', 'can_persist',
        ], array_keys($out));
        self::assertSame('lost', $out['latest_claimed_status']);
        self::assertSame(1, $out['claimed_loss_reasons']['other']);
        self::assertSame(0, $out['recognized_wins']);
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('2026-11-01T', $json);
        foreach ([
            'tenant', 'email', 'phone', 'customer', 'event_reference',
            'opportunity_id', 'note', 'actor_id', 'user_id', 'payment',
        ] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
    }
}
