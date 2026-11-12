<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticOpportunityTransitionLedger;
use App\Core\Sales\SyntheticStageFieldRequirementsInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticStageFieldRequirementsInspectorTest extends TestCase
{
    /** @return array<string,bool> */
    private static function presence(): array
    {
        return [
            'amount' => true,
            'assignee' => false,
            'contact' => true,
            'expected_close' => false,
            'source' => true,
        ];
    }

    /** @return list<list<string>> */
    private static function requirements(): array
    {
        return [
            [],
            ['contact', 'source'],
            ['contact', 'assignee', 'amount'],
            ['source', 'expected_close', 'contact', 'assignee', 'amount'],
        ];
    }

    public function test_missing_required_destination_fields_are_reported_without_values_or_access(): void
    {
        $inspector = new SyntheticStageFieldRequirementsInspector();
        $fields = self::presence();
        $policy = self::requirements();
        $originalFields = $fields;
        $originalPolicy = $policy;

        $preview = $inspector->inspect(0, 2, $fields, $policy);
        self::assertSame(0, $preview['from_stage']);
        self::assertSame(2, $preview['to_stage']);
        self::assertSame(3, $preview['required_count']);
        self::assertSame(1, $preview['missing_count']);
        self::assertSame(['assignee'], $preview['missing_field_codes']);
        self::assertFalse($preview['requirements_met']);
        self::assertTrue($preview['informational_only']);
        foreach ([
            'policy_verified', 'fields_verified', 'authorization_verified',
            'authorized', 'can_move', 'can_override_required_fields',
            'can_persist',
        ] as $flag) {
            self::assertFalse($preview[$flag], $flag);
        }
        self::assertSame($originalFields, $fields);
        self::assertSame($originalPolicy, $policy);
        self::assertSame($preview, $inspector->inspect(0, 2, $fields, $policy));
    }

    public function test_satisfied_or_empty_requirements_do_not_authorize_movement(): void
    {
        $inspector = new SyntheticStageFieldRequirementsInspector();
        $good = $inspector->inspect(2, 1, self::presence(), self::requirements());
        self::assertSame(0, $good['missing_count']);
        self::assertTrue($good['requirements_met']);
        self::assertFalse($good['policy_verified']);
        self::assertFalse($good['can_move']);

        $empty = $inspector->inspect(2, 0, self::presence(), self::requirements());
        self::assertSame(0, $empty['required_count']);
        self::assertTrue($empty['requirements_met']);
        self::assertFalse($empty['authorized']);

        // Even after a fictional reducer accepts an invented move, neither
        // component grants the right to mutate an actual opportunity.
        $ledger = (new SyntheticOpportunityTransitionLedger())->inspect([
            [
                'revision' => 1, 'action' => 'create', 'stage' => 2,
                'confirmed' => false, 'loss_reason_code' => null,
                'reopen_reason_code' => null,
                'occurred_at_utc' => '2026-11-01T10:00:00Z',
            ],
            [
                'revision' => 2, 'action' => 'move', 'stage' => 1,
                'confirmed' => false, 'loss_reason_code' => null,
                'reopen_reason_code' => null,
                'occurred_at_utc' => '2026-11-01T10:00:01Z',
            ],
        ]);
        self::assertSame(1, $ledger['latest_stage']);
        self::assertFalse($ledger['can_move']);
        self::assertFalse($ledger['concurrency_verified']);
    }

    public function test_different_input_policy_order_produces_fixed_missing_code_order(): void
    {
        $result = (new SyntheticStageFieldRequirementsInspector())->inspect(
            0,
            3,
            [
                'source' => false, 'expected_close' => false,
                'contact' => true, 'assignee' => false, 'amount' => false,
            ],
            self::requirements(),
        );
        self::assertSame(5, $result['required_count']);
        self::assertSame(4, $result['missing_count']);
        self::assertSame([
            'amount', 'assignee', 'expected_close', 'source',
        ], $result['missing_field_codes']);
        self::assertFalse($result['requirements_met']);
    }

    public function test_invalid_policy_hidden_fields_and_private_values_fail_closed(): void
    {
        $stagePolicy = self::requirements();
        $present = self::presence();
        $bad = [
            [null, 2, $present, $stagePolicy],
            ['0', 2, $present, $stagePolicy],
            [0, '2', $present, $stagePolicy],
            [-1, 2, $present, $stagePolicy],
            [4, 2, $present, $stagePolicy],
            [0, 4, $present, $stagePolicy],
            [1, 1, $present, $stagePolicy],
            [0, 2, null, $stagePolicy],
            [0, 2, 'fields', $stagePolicy],
            [0, 2, [], $stagePolicy],
            [0, 2, array_merge($present, ['email' => 'private@example.test']), $stagePolicy],
            [0, 2, array_merge($present, ['contact' => 'private@example.test']), $stagePolicy],
            [0, 2, array_merge($present, ['contact' => 1]), $stagePolicy],
            [0, 2, array_merge($present, ['contact' => null]), $stagePolicy],
            [0, 2, $present, null],
            [0, 2, $present, []],
            [0, 2, $present, [0 => [], 2 => []]],
            [0, 2, $present, [[], [], []]],
            [0, 2, $present, [[], [], [], [], []]],
            [0, 2, $present, [[], [], ['contact', 'contact'], []]],
            [0, 2, $present, [[], [], ['email'], []]],
            [0, 2, $present, [[], [], [false], []]],
            [0, 2, $present, [[], [], [1], []]],
            [0, 2, $present, [[], [], 'contact', []]],
            [0, 2, $present, [[], [], ['amount', 'assignee', 'contact', 'expected_close', 'source', 'extra'], []]],
        ];
        foreach ($bad as [$from, $to, $values, $rules]) {
            try {
                (new SyntheticStageFieldRequirementsInspector())->inspect($from, $to, $values, $rules);
                self::fail('Malformed required field policy or personal data was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Campos obrigatorios sinteticos invalidos.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_readout_is_closed_and_includes_only_whitelisted_missing_codes(): void
    {
        $result = (new SyntheticStageFieldRequirementsInspector())->inspect(
            1, 3, self::presence(), self::requirements(),
        );
        self::assertSame([
            'from_stage', 'to_stage', 'required_count',
            'missing_count', 'missing_field_codes', 'requirements_met',
            'policy_verified', 'fields_verified', 'authorization_verified',
            'informational_only', 'authorized', 'can_move',
            'can_override_required_fields', 'can_persist',
        ], array_keys($result));
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['email', 'phone', 'tenant', 'customer', 'lead_id', 'note', 'amount_cents', 'value'] as $forbidden) {
            self::assertStringNotContainsString('"'.$forbidden.'"', $json);
        }
    }
}
