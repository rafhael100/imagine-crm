<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticSalesAggregatePrivacyPreview;
use App\Core\Sales\SyntheticSalesEventUniquenessInspector;
use App\Core\Sales\SyntheticStageAggregateCsvPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticStageAggregateCsvPreviewTest extends TestCase
{
    /** @return array{stage:string,claimed_value_cents:int} */
    private static function row(string $stage, int $cents): array
    {
        return ['stage' => $stage, 'claimed_value_cents' => $cents];
    }

    public function test_csv_text_contains_only_visible_groups_and_excludes_suppressed_totals(): void
    {
        $rows = [
            self::row('new', 100),
            self::row('new', 200),
            self::row('new', 300),
            self::row('proposal', 555555),
            self::row('won', 10000),
            self::row('won', 20000),
        ];
        $original = $rows;
        $preview = (new SyntheticStageAggregateCsvPreview())->preview($rows);

        self::assertSame("stage,count,claimed_sum_cents\r\nnew,3,600\r\n", $preview['csv_preview_utf8']);
        self::assertSame(1, $preview['visible_line_count']);
        self::assertSame(2, $preview['suppressed_bucket_count']);
        self::assertSame(3, $preview['minimum_group_size']);
        self::assertSame(0, $preview['recognized_revenue_cents']);
        self::assertStringNotContainsString('555555', $preview['csv_preview_utf8']);
        self::assertStringNotContainsString('30000', $preview['csv_preview_utf8']);
        self::assertStringNotContainsString('total', $preview['csv_preview_utf8']);
        self::assertTrue($preview['informational_only']);
        foreach ([
            'privacy_policy_verified', 'tenant_scope_verified',
            'recipient_permissions_verified', 'authorized',
            'can_read_customers', 'can_export', 'can_write_file',
            'can_download', 'can_email', 'can_persist',
        ] as $key) {
            self::assertFalse($preview[$key], $key);
        }
        self::assertSame($original, $rows);
        self::assertSame($preview, (new SyntheticStageAggregateCsvPreview())->preview($rows));
    }

    public function test_csv_parity_with_upstream_privacy_suppression_for_all_stages(): void
    {
        $rows = [
            ...array_fill(0, 3, self::row('new', 100)),
            ...array_fill(0, 3, self::row('qualified', 200)),
            self::row('proposal', 777),
            ...array_fill(0, 3, self::row('won', 300)),
            ...array_fill(0, 3, self::row('lost', 400)),
        ];
        $summary = (new SyntheticSalesAggregatePrivacyPreview())->summarize($rows, 3);
        $report = (new SyntheticStageAggregateCsvPreview())->preview($rows, 3);

        self::assertSame(4, $report['visible_line_count']);
        self::assertSame(1, $report['suppressed_bucket_count']);
        self::assertSame(
            "stage,count,claimed_sum_cents\r\n"
            ."new,3,300\r\n"
            ."qualified,3,600\r\n"
            ."won,3,900\r\n"
            ."lost,3,1200\r\n",
            $report['csv_preview_utf8'],
        );

        foreach (explode("\r\n", trim($report['csv_preview_utf8'])) as $index => $line) {
            if ($index === 0) {
                self::assertSame('stage,count,claimed_sum_cents', $line);
                continue;
            }
            $cells = str_getcsv($line);
            self::assertCount(3, $cells);
            self::assertContains($cells[0], ['new', 'qualified', 'won', 'lost']);
            $source = array_values(array_filter(
                $summary['cells'],
                static fn (array $bucket): bool => $bucket['stage'] === $cells[0],
            ));
            self::assertCount(1, $source);
            self::assertFalse($source[0]['suppressed']);
            self::assertSame($source[0]['count'], (int) $cells[1]);
            self::assertSame($source[0]['claimed_sum_cents'], (int) $cells[2]);
        }
        self::assertStringNotContainsString('proposal', $report['csv_preview_utf8']);
        self::assertFalse($report['can_download']);
    }

    public function test_empty_and_all_suppressed_cases_emit_only_the_fixed_header(): void
    {
        $inspector = new SyntheticStageAggregateCsvPreview();
        $empty = $inspector->preview([]);
        self::assertSame("stage,count,claimed_sum_cents\r\n", $empty['csv_preview_utf8']);
        self::assertSame(0, $empty['visible_line_count']);
        self::assertSame(0, $empty['suppressed_bucket_count']);

        $hidden = $inspector->preview([
            self::row('new', 10),
            self::row('proposal', 20),
            self::row('won', 30),
        ]);
        self::assertSame("stage,count,claimed_sum_cents\r\n", $hidden['csv_preview_utf8']);
        self::assertSame(3, $hidden['suppressed_bucket_count']);
        self::assertFalse($hidden['can_export']);
    }

    public function test_full_safe_integer_is_serialized_exactly_in_decimal_without_roundoff(): void
    {
        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $rows = [
            self::row('won', $max - 2),
            self::row('won', 1),
            self::row('won', 1),
        ];
        $out = (new SyntheticStageAggregateCsvPreview())->preview($rows, 3);
        self::assertSame(
            "stage,count,claimed_sum_cents\r\nwon,3,".$max."\r\n",
            $out['csv_preview_utf8'],
        );
        self::assertSame(0, $out['recognized_revenue_cents']);
        self::assertFalse($out['can_write_file']);
    }

    public function test_invalid_rows_thresholds_spoofed_stage_and_hidden_pii_fail_closed(): void
    {
        $row = self::row('new', 100);
        $max = SyntheticSalesEventUniquenessInspector::MAX_EXACT_CENTS;
        $bad = [
            [null, 3], ['rows', 3], [[null], 3],
            [[$row], 1], [[$row], 6], [[$row], '3'], [[$row], 3.0],
            [[self::row('=2+2', 100)], 3],
            [[self::row('+SUM(A1)', 100)], 3],
            [[self::row('@cmd', 100)], 3],
            [[self::row('new', -1)], 3],
            [[self::row('new', PHP_INT_MAX)], 3],
            [[array_merge($row, ['email' => 'private@example.test'])], 3],
            [[array_merge($row, ['tenant' => 'private@example.test'])], 3],
            [[['stage' => 'new', 'claimed_value_cents' => '100']], 3],
            [array_fill(0, 21, $row), 3],
            [[self::row('proposal', $max), self::row('won', 1)], 3],
        ];
        foreach ($bad as [$rows, $threshold]) {
            try {
                (new SyntheticStageAggregateCsvPreview())->preview($rows, $threshold);
                self::fail('Unsafe aggregate CSV data or leaked hidden bucket accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Previa CSV comercial sintetica invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_output_is_bounded_fixed_schema_with_no_export_permissions(): void
    {
        $report = (new SyntheticStageAggregateCsvPreview())->preview([
            self::row('new', 111),
            self::row('new', 222),
            self::row('new', 333),
        ]);
        self::assertSame([
            'csv_preview_utf8', 'visible_line_count',
            'suppressed_bucket_count', 'minimum_group_size',
            'recognized_revenue_cents', 'privacy_policy_verified',
            'tenant_scope_verified', 'recipient_permissions_verified',
            'informational_only', 'authorized', 'can_read_customers',
            'can_export', 'can_write_file', 'can_download', 'can_email',
            'can_persist',
        ], array_keys($report));
        self::assertLessThan(257, strlen($report['csv_preview_utf8']));
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['tenant', 'customer', 'email', 'phone', 'lead_id',
            'opportunity_id', 'event_reference', 'actor_id', 'filename',
            'download_url', 'recipient', 'row_count', 'grand_total'] as $forbidden) {
            self::assertStringNotContainsString('"'.$forbidden.'"', $json);
        }
        foreach (explode("\r\n", trim($report['csv_preview_utf8'])) as $i => $line) {
            $cells = explode(',', $line);
            if ($i === 0) {
                self::assertSame(['stage', 'count', 'claimed_sum_cents'], $cells);
                continue;
            }
            self::assertCount(3, $cells);
            self::assertMatchesRegularExpression('/\A[a-z_]+\z/D', $cells[0]);
            self::assertMatchesRegularExpression('/\A[0-9]+\z/D', $cells[1]);
            self::assertMatchesRegularExpression('/\A[0-9]+\z/D', $cells[2]);
            self::assertDoesNotMatchRegularExpression('/\A[=+\-@]/', $line);
        }
    }
}
