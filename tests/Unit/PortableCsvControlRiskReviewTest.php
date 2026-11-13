<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvControlRiskReview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvControlRiskReviewTest extends TestCase
{
    private static function bind(int $position, string $target): array
    {
        return ['source_position' => $position, 'target_field' => $target];
    }

    public function test_multiline_quoted_mapped_cell_is_not_silent_or_export_authorized(): void
    {
        $out = (new PortableCsvControlRiskReview())->inspect(
            "name,notes\nSafe,\"line\nbreak\"",
            'comma', [self::bind(1, 'notes')], true,
        );
        self::assertSame(1, $out['local_source_ascii_control_cell_count']);
        self::assertSame(1, $out['local_source_line_separator_cell_count']);
        self::assertSame(1, $out['local_mapped_line_separator_cell_count']);
        self::assertSame(0, $out['local_source_bidi_control_cell_count']);
        self::assertContains('SOURCE_CELLS_HAVE_LINE_SEPARATOR_SIGNALS', $out['warning_codes']);
        self::assertContains('MAPPED_VALUES_HAVE_CONTROL_SIGNALS', $out['warning_codes']);
        self::assertFalse($out['can_export']);
        self::assertFalse($out['spreadsheet_client_behavior_verified']);
    }

    public function test_unicode_bidi_and_format_marks_are_reported_separately(): void
    {
        $out = (new PortableCsvControlRiskReview())->inspect(
            "name,notes\nAlpha,\u{202E}PRIVATE\nBeta,\u{200D}text",
            'comma', [self::bind(1, 'notes')], true,
        );
        self::assertSame(2, $out['local_source_format_mark_cell_count']);
        self::assertSame(1, $out['local_source_bidi_control_cell_count']);
        self::assertSame(2, $out['local_mapped_format_mark_cell_count']);
        self::assertContains('SOURCE_CELLS_HAVE_BIDI_DIRECTIONAL_CONTROLS', $out['warning_codes']);
        self::assertStringNotContainsString('PRIVATE', json_encode($out, JSON_THROW_ON_ERROR));
    }

    public function test_unselected_control_column_is_flagged_without_marking_mapped_cells(): void
    {
        $out = (new PortableCsvControlRiskReview())->inspect(
            "name,hidden\nPlain,\u{0085}PRIVATE_FIXTURE",
            'comma', [self::bind(0, 'name')], true,
        );
        self::assertSame(1, $out['local_source_line_separator_cell_count']);
        self::assertSame(0, $out['local_mapped_line_separator_cell_count']);
        self::assertSame(0, $out['local_source_ascii_control_cell_count']);
        self::assertNotContains('MAPPED_VALUES_HAVE_CONTROL_SIGNALS', $out['warning_codes']);
        self::assertStringNotContainsString('PRIVATE_FIXTURE', json_encode($out, JSON_THROW_ON_ERROR));
        self::assertFalse($out['can_import']);
    }

    public function test_clean_and_header_only_samples_report_no_data_control_counts(): void
    {
        $inspector = new PortableCsvControlRiskReview();
        foreach ([
            ["h1,h2\nclean,value", 1],
            ["h1,h2", 0],
        ] as [$csv, $rows]) {
            $out = $inspector->inspect($csv, 'comma', [self::bind(0, 'name')], true);
            self::assertSame($rows, $out['local_data_row_count']);
            self::assertSame(0, $out['local_source_ascii_control_cell_count']);
            self::assertSame(0, $out['local_source_line_separator_cell_count']);
            self::assertSame(0, $out['local_source_format_mark_cell_count']);
            self::assertSame(0, $out['local_source_bidi_control_cell_count']);
            self::assertSame(['SPREADSHEET_CONTROL_POLICY_NOT_VERIFIED'], $out['warning_codes']);
        }
    }

    public function test_invalid_csv_and_mappings_are_rejected_without_echo(): void
    {
        $inspector = new PortableCsvControlRiskReview();
        foreach ([
            ['PRIVATE_VALUE', 'pipe', [self::bind(0, 'name')], false],
            ['PRIVATE_VALUE', 'comma', [self::bind(10, 'name')], false],
            ['PRIVATE_VALUE', 'comma', [self::bind(0, 'name')], 'true'],
            ['a,"unterminated', 'comma', [self::bind(0, 'name')], true],
        ] as $args) {
            try {
                $inspector->inspect(...$args);
                self::fail('Invalid input was accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Revisao de controles CSV local invalida.', $e->getMessage());
                self::assertStringNotContainsString('PRIVATE_VALUE', $e->getMessage());
            }
        }
    }
}
