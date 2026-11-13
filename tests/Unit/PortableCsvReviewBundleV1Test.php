<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvReviewBundleV1;
use App\Core\Portability\PortableCsvReviewReportV1;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvReviewBundleV1Test extends TestCase
{
    private static function bind(int $position, string $target): array
    {
        return ['source_position' => $position, 'target_field' => $target];
    }

    public function test_bundle_merges_control_signals_with_unchanged_structural_v1(): void
    {
        $csv = "label,hidden\nSENSITIVE_A,\u{202E}PRIVATE\nSENSITIVE_A,\u{200D}text";
        $bindings = [self::bind(0, 'name')];
        $bundle = new PortableCsvReviewBundleV1();
        $out = $bundle->build($csv, 'comma', $bindings, true, ['name', 'email']);
        self::assertSame('imagine.crm.portable_csv_review_bundle', $out['protocol']);
        self::assertSame(1, $out['schema_version']);
        self::assertSame(
            (new PortableCsvReviewReportV1())->build($csv, 'comma', $bindings, true, ['name', 'email']),
            $out['structural_review'],
        );
        self::assertSame(2, $out['control_signals']['counts']['source_format_mark_cells']);
        self::assertSame(1, $out['control_signals']['counts']['source_bidi_control_cells']);
        self::assertSame(0, $out['control_signals']['counts']['mapped_format_mark_cells']);
        self::assertSame(1, $out['structural_review']['counts']['projection_collision_extra_rows']);
        self::assertContains('SOURCE_CELLS_HAVE_BIDI_DIRECTIONAL_CONTROLS', $out['control_signals']['warning_codes']);
        $wire = $bundle->encode($csv, 'comma', $bindings, true, ['name', 'email']);
        self::assertSame($out, json_decode($wire, true, 512, JSON_THROW_ON_ERROR));
        self::assertLessThanOrEqual(PortableCsvReviewBundleV1::MAX_JSON_BYTES, strlen($wire));
        foreach (['SENSITIVE_A', 'PRIVATE', '"name"', '"email"', '"person"', '"file_path"', '"tenant_id"'] as $secret) {
            self::assertStringNotContainsString($secret, $wire);
        }
        foreach (['authorized', 'can_import', 'can_export', 'can_merge', 'can_deduplicate', 'can_persist'] as $key) {
            self::assertFalse($out[$key]);
            self::assertFalse($out['structural_review'][$key]);
        }
    }

    public function test_clean_headerless_sample_has_no_control_signal_but_still_denies_everything(): void
    {
        $bundle = new PortableCsvReviewBundleV1();
        $args = ["Alpha,Beta\nGamma,Delta", 'comma', [self::bind(1, 'notes')], false];
        $out = $bundle->build(...$args);
        self::assertSame(2, $out['structural_review']['counts']['data_rows']);
        self::assertFalse($out['structural_review']['header_claimed']);
        self::assertSame(
            ['SPREADSHEET_CONTROL_POLICY_NOT_VERIFIED'],
            $out['control_signals']['warning_codes'],
        );
        foreach ($out['control_signals']['counts'] as $count) {
            self::assertSame(0, $count);
        }
        self::assertSame($out, $bundle->build(...[...$args, null]));
        self::assertFalse($out['can_import']);
    }

    public function test_newlines_inside_quoted_cells_are_signalled_even_when_parsed_correctly(): void
    {
        $out = (new PortableCsvReviewBundleV1())->build(
            "a,b\nAlpha,\"line\nbreak\"",
            'comma', [self::bind(1, 'notes')], true,
        );
        self::assertSame(1, $out['control_signals']['counts']['mapped_line_separator_cells']);
        self::assertSame(1, $out['control_signals']['counts']['mapped_ascii_control_cells']);
        self::assertFalse($out['control_signals']['spreadsheet_client_behavior_verified']);
    }

    public function test_invalid_csv_and_field_list_fail_closed_with_fixed_error(): void
    {
        foreach ([
            ['SECRET_FIXTURE', 'pipe', [self::bind(0, 'name')], false, null],
            ['SECRET_FIXTURE', 'comma', [self::bind(10, 'name')], false, null],
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], false, ['password']],
            ['', 'comma', [self::bind(0, 'name')], false, null],
        ] as $arguments) {
            try {
                (new PortableCsvReviewBundleV1())->encode(...$arguments);
                self::fail('Invalid review unexpectedly encoded.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Pacote de revisao CSV V1 invalido.', $e->getMessage());
                self::assertStringNotContainsString('SECRET_FIXTURE', $e->getMessage());
            }
        }
    }
}
