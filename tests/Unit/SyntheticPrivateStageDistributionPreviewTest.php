<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticPrivateStageDistributionPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticPrivateStageDistributionPreviewTest extends TestCase
{
    /** @return array{stage:string,claimed_value_cents:int} */
    private static function row(string $stage, int $cents = 1): array
    {
        return ['stage' => $stage, 'claimed_value_cents' => $cents];
    }

    public function test_complete_groups_show_exact_basis_point_distribution_without_business_rights(): void
    {
        $rows = [
            ...array_fill(0, 3, self::row('new')),
            ...array_fill(0, 3, self::row('won')),
        ];
        $original = $rows;
        $out = (new SyntheticPrivateStageDistributionPreview())->preview($rows);
        self::assertFalse($out['ratios_withheld']);
        self::assertSame([5000, 0, 0, 5000, 0], array_column($out['stages'], 'share_basis_points'));
        self::assertSame([3, 0, 0, 3, 0], array_column($out['stages'], 'count'));
        self::assertSame(0, $out['recognized_revenue_cents']);
        self::assertFalse($out['can_export']);
        self::assertFalse($out['can_download']);
        self::assertFalse($out['authorized']);
        self::assertSame($original, $rows);
    }

    public function test_a_hidden_singleton_withholds_every_ratio_even_when_other_groups_are_visible(): void
    {
        $rows = [
            ...array_fill(0, 3, self::row('new', 100)),
            self::row('won', 987654),
        ];
        $out = (new SyntheticPrivateStageDistributionPreview())->preview($rows);
        self::assertTrue($out['ratios_withheld']);
        self::assertSame([null, null, null, null, null], array_column($out['stages'], 'share_basis_points'));
        self::assertSame([3, 0, 0, null, 0], array_column($out['stages'], 'count'));
        self::assertSame([false, false, false, true, false], array_column($out['stages'], 'suppressed'));
        self::assertSame(1, $out['suppressed_bucket_count']);
        self::assertStringNotContainsString('987654', json_encode($out, JSON_THROW_ON_ERROR));
    }

    public function test_empty_input_has_no_denominator_or_ratios(): void
    {
        $out = (new SyntheticPrivateStageDistributionPreview())->preview([]);
        self::assertTrue($out['ratios_withheld']);
        self::assertSame([null, null, null, null, null], array_column($out['stages'], 'share_basis_points'));
        self::assertSame(0, $out['suppressed_bucket_count']);
        self::assertFalse($out['can_read_customers']);
    }

    public function test_invalid_rows_and_thresholds_are_rejected_with_generic_message(): void
    {
        $good = self::row('new');
        $cases = [
            [null, 3], ['rows', 3], [[self::row('other')], 3],
            [[[ 'stage' => 'new', 'claimed_value_cents' => 1, 'email' => 'secret@example.test' ]], 3],
            [[self::row('new', -1)], 3], [[self::row('new')], '3'],
            [[self::row('new')], 1], [[self::row('new')], 6],
            [array_fill(0, 21, $good), 3],
        ];
        foreach ($cases as [$rows, $k]) {
            try {
                (new SyntheticPrivateStageDistributionPreview())->preview($rows, $k);
                self::fail('Invalid or identifying input was accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Distribuicao sintetica de funil invalida.', $exception->getMessage());
                self::assertStringNotContainsString('secret@example.test', $exception->getMessage());
            }
        }
    }

    public function test_output_is_closed_and_does_not_expose_hidden_amounts_or_total(): void
    {
        $out = (new SyntheticPrivateStageDistributionPreview())->preview([self::row('proposal', 789)]);
        self::assertSame([
            'stages', 'ratios_withheld', 'suppressed_bucket_count',
            'minimum_group_size', 'recognized_revenue_cents',
            'privacy_policy_verified', 'tenant_scope_verified',
            'source_data_verified', 'informational_only',
            'authorized', 'can_read_customers', 'can_export',
            'can_download', 'can_persist',
        ], array_keys($out));
        self::assertSame(['stage', 'count', 'suppressed', 'share_basis_points'], array_keys($out['stages'][0]));
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['claimed_value_cents', 'claimed_sum_cents', 'total_count', 'grand_total',
            'email', 'tenant_id', 'recipient', '789'] as $hidden) {
            self::assertStringNotContainsString('"'.$hidden.'"', $json);
        }
        foreach (['privacy_policy_verified','tenant_scope_verified','source_data_verified',
            'authorized','can_read_customers','can_export','can_download','can_persist'] as $field) {
            self::assertFalse($out[$field]);
        }
    }
}
