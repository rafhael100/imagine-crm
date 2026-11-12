<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticOpportunityOutcomeInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticOpportunityOutcomeInspectorTest extends TestCase
{
    public function test_fictional_open_won_lost_counts_require_a_loss_reason(): void
    {
        $records = [
            ['outcome' => 'open', 'loss_reason_code' => null],
            ['outcome' => 'won', 'loss_reason_code' => null],
            ['outcome' => 'lost', 'loss_reason_code' => 'price'],
            ['outcome' => 'lost', 'loss_reason_code' => 'competition'],
            ['outcome' => 'lost', 'loss_reason_code' => 'price'],
        ];
        $original = $records;
        $report = (new SyntheticOpportunityOutcomeInspector())->inspect($records);

        self::assertSame(5, $report['samples']);
        self::assertSame(['open' => 1, 'won' => 1, 'lost' => 3], $report['claimed_outcome_counts']);
        self::assertSame([
            'price' => 2, 'timing' => 0, 'competition' => 1,
            'no_response' => 0, 'other' => 0,
        ], $report['claimed_lost_by_reason']);
        self::assertSame($report['claimed_outcome_counts']['lost'], array_sum($report['claimed_lost_by_reason']));
        self::assertSame(5, array_sum($report['claimed_outcome_counts']));
        self::assertTrue($report['loss_reason_required']);
        self::assertFalse($report['outcomes_verified']);
        self::assertFalse($report['loss_reason_policy_verified']);
        self::assertSame(0, $report['recognized_wins']);
        self::assertTrue($report['informational_only']);
        foreach ([
            'authorized', 'can_mark_won', 'can_mark_lost', 'can_reopen',
            'can_count_revenue', 'can_notify', 'can_persist',
        ] as $ability) {
            self::assertFalse($report[$ability]);
        }
        self::assertSame($original, $records);
        self::assertSame($report, (new SyntheticOpportunityOutcomeInspector())->inspect($records));
    }

    public function test_empty_and_bounded_fictional_outcomes_are_never_sales_evidence(): void
    {
        $empty = (new SyntheticOpportunityOutcomeInspector())->inspect([]);
        self::assertSame(0, $empty['samples']);
        self::assertSame(0, array_sum($empty['claimed_outcome_counts']));
        self::assertSame(0, array_sum($empty['claimed_lost_by_reason']));

        $records = array_fill(0, 20, ['outcome' => 'won', 'loss_reason_code' => null]);
        $report = (new SyntheticOpportunityOutcomeInspector())->inspect($records);
        self::assertSame(20, $report['claimed_outcome_counts']['won']);
        self::assertSame(0, $report['recognized_wins']);
        self::assertFalse($report['can_count_revenue']);
    }

    public function test_missing_reason_on_lost_and_conflicting_reason_on_won_are_denied(): void
    {
        $good = ['outcome' => 'lost', 'loss_reason_code' => 'timing'];
        $invalid = [
            null, true, 1, 'loss',
            [1 => $good], [null], array_fill(0, 21, $good),
            [['outcome' => 'lost', 'loss_reason_code' => null]],
            [['outcome' => 'lost', 'loss_reason_code' => '']],
            [['outcome' => 'lost', 'loss_reason_code' => 'custom free text']],
            [['outcome' => 'lost', 'loss_reason_code' => 'other: private@example.test']],
            [['outcome' => 'lost', 'loss_reason_code' => 42]],
            [['outcome' => 'lost', 'loss_reason_code' => false]],
            [['outcome' => 'open', 'loss_reason_code' => 'price']],
            [['outcome' => 'won', 'loss_reason_code' => 'timing']],
            [['outcome' => 'WON', 'loss_reason_code' => null]],
            [['outcome' => 'paid', 'loss_reason_code' => null]],
            [['outcome' => false, 'loss_reason_code' => null]],
            [['outcome' => 'lost', 'loss_reason_code' => 'price', 'email' => 'private@example.test']],
            [['outcome' => 'lost']],
            [['loss_reason_code' => 'price']],
        ];

        foreach ($invalid as $input) {
            try {
                (new SyntheticOpportunityOutcomeInspector())->inspect($input);
                self::fail('Unsafe or contradictory opportunity outcome accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Resultado sintetico de oportunidade invalido.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_closed_numeric_projection_and_fixed_reason_vocabulary(): void
    {
        $report = (new SyntheticOpportunityOutcomeInspector())->inspect([
            ['loss_reason_code' => 'other', 'outcome' => 'lost'],
            ['loss_reason_code' => 'no_response', 'outcome' => 'lost'],
        ]);
        self::assertSame([
            'samples', 'claimed_outcome_counts', 'claimed_lost_by_reason',
            'loss_reason_required', 'outcomes_verified',
            'loss_reason_policy_verified', 'recognized_wins',
            'informational_only', 'authorized', 'can_mark_won',
            'can_mark_lost', 'can_reopen', 'can_count_revenue',
            'can_notify', 'can_persist',
        ], array_keys($report));
        self::assertSame(1, $report['claimed_lost_by_reason']['other']);
        self::assertSame(1, $report['claimed_lost_by_reason']['no_response']);
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['tenant', 'email', 'phone', 'opportunity_id', 'contact', 'actor', 'history', 'customer', 'revenue_cents'] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
    }
}
