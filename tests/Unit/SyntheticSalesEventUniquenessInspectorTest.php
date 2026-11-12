<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticSalesEventUniquenessInspector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticSalesEventUniquenessInspectorTest extends TestCase
{
    private const A = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const B = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    private const C = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    public function test_three_unique_fabricated_event_claims_do_not_recognize_revenue(): void
    {
        $claims = [
            ['event_reference' => self::A, 'amount_cents' => 10000],
            ['event_reference' => self::B, 'amount_cents' => 12999],
            ['event_reference' => self::C, 'amount_cents' => 0],
        ];
        $original = $claims;
        $r = (new SyntheticSalesEventUniquenessInspector())->inspect($claims);

        self::assertSame(3, $r['claim_count']);
        self::assertSame(3, $r['unique_reference_count']);
        self::assertSame(22999, $r['claimed_sum_cents']);
        self::assertSame(0, $r['recognized_revenue_cents']);
        self::assertSame(0, $r['verified_event_count']);
        self::assertSame('BRL', $r['currency_code']);
        self::assertTrue($r['informational_only']);
        foreach ([
            'provenance_verified', 'tenant_verified', 'authorized',
            'can_recognize_revenue', 'can_update_targets', 'can_invoice', 'can_persist',
        ] as $flag) {
            self::assertFalse($r[$flag]);
        }
        self::assertSame($original, $claims);
        self::assertSame($r, (new SyntheticSalesEventUniquenessInspector())->inspect($claims));
    }

    public function test_case_insensitive_duplicate_references_are_rejected_not_silently_counted(): void
    {
        $input = [
            ['event_reference' => self::A, 'amount_cents' => 15000],
            ['event_reference' => strtoupper(self::A), 'amount_cents' => 30000],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Referencias sinteticas de venda invalidas.');
        (new SyntheticSalesEventUniquenessInspector())->inspect($input);
    }

    public function test_empty_and_safe_integer_upper_bound_are_allowed_as_unverified_claims(): void
    {
        $inspector = new SyntheticSalesEventUniquenessInspector();
        $empty = $inspector->inspect([]);
        self::assertSame(0, $empty['claim_count']);
        self::assertSame(0, $empty['claimed_sum_cents']);

        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $result = $inspector->inspect([
            ['event_reference' => self::A, 'amount_cents' => $max - 1],
            ['event_reference' => self::B, 'amount_cents' => 1],
        ]);
        self::assertSame($max, $result['claimed_sum_cents']);
        self::assertSame(0, $result['recognized_revenue_cents']);
    }

    public function test_invalid_shapes_nil_uuids_and_overflow_fail_closed_without_echo(): void
    {
        $good = ['event_reference' => self::A, 'amount_cents' => 1234];
        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $invalid = [
            null, 1, false, 'invoice',
            [1 => $good],
            [null],
            [$good, $good],
            [array_merge($good, ['tenant' => 'private@example.test'])],
            [['event_reference' => '', 'amount_cents' => 1]],
            [['event_reference' => '00000000-0000-0000-0000-000000000000', 'amount_cents' => 1]],
            [['event_reference' => 'not-a-uuid', 'amount_cents' => 1]],
            [['event_reference' => true, 'amount_cents' => 1]],
            [['event_reference' => self::A, 'amount_cents' => -1]],
            [['event_reference' => self::A, 'amount_cents' => '1']],
            [['event_reference' => self::A, 'amount_cents' => 1.5]],
            [['event_reference' => self::A, 'amount_cents' => PHP_INT_MAX]],
            [['event_reference' => self::A]],
            [['amount_cents' => 1]],
            [['event_reference' => self::A, 'amount_cents' => $max], ['event_reference' => self::B, 'amount_cents' => 1]],
            array_fill(0, 21, $good),
        ];
        foreach ($invalid as $claims) {
            try {
                (new SyntheticSalesEventUniquenessInspector())->inspect($claims);
                self::fail('Invalid or duplicate sales event references were accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Referencias sinteticas de venda invalidas.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_only_aggregate_numbers_and_fixed_no_authority_flags_are_exposed(): void
    {
        $result = (new SyntheticSalesEventUniquenessInspector())->inspect([
            ['amount_cents' => 2000, 'event_reference' => self::A],
        ]);
        self::assertSame([
            'claim_count', 'unique_reference_count', 'claimed_sum_cents',
            'recognized_revenue_cents', 'verified_event_count',
            'currency_code', 'provenance_verified', 'tenant_verified',
            'informational_only', 'authorized', 'can_recognize_revenue',
            'can_update_targets', 'can_invoice', 'can_persist',
        ], array_keys($result));
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::A, $json);
        foreach ([
            'tenant', 'email', 'phone', 'customer', 'event_reference',
            'lead_id', 'opportunity_id', 'payment', 'invoice_id', 'actor',
        ] as $field) {
            // Match a complete JSON key, not part of the permitted
            // metadata flag "tenant_verified": that flag must remain false.
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
    }
}
