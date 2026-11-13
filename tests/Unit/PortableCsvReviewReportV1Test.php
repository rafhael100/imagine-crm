<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvReviewReportV1;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvReviewReportV1Test extends TestCase
{
    private static function bind(int $position, string $target): array
    {
        return ['source_position' => $position, 'target_field' => $target];
    }

    public function test_versioned_report_aggregates_duplicates_and_claimed_required_fields_only(): void
    {
        $csv = "label,ignored\nPRIVATE_FIXTURE,one\nPRIVATE_FIXTURE,two";
        $bindings = [self::bind(0, 'name')];
        $review = new PortableCsvReviewReportV1();
        $report = $review->build($csv, 'comma', $bindings, true, ['name', 'email']);
        self::assertSame('imagine.crm.portable_csv_review', $report['protocol']);
        self::assertSame(1, $report['schema_version']);
        self::assertSame('REVIEW_ONLY_DENIED', $report['status']);
        self::assertSame(2, $report['counts']['data_rows']);
        self::assertSame(1, $report['counts']['projection_collision_extra_rows']);
        self::assertSame(0, $report['counts']['repeated_source_rows_exact']);
        self::assertSame(2, $report['counts']['claimed_required_fields']);
        self::assertSame(1, $report['counts']['unmapped_claimed_required_fields']);
        self::assertSame(0, $report['counts']['empty_claimed_required_values']);
        self::assertTrue($report['required_field_check_performed']);
        self::assertContains('CLAIMED_REQUIRED_FIELDS_NOT_MAPPED', $report['warning_codes']);
        self::assertCount(6, $report['unfulfilled_gate_codes']);
        self::assertSame($report, $review->build($csv, 'comma', $bindings, true, ['name', 'email']));
        self::assertSame($csv, "label,ignored\nPRIVATE_FIXTURE,one\nPRIVATE_FIXTURE,two");
        $wire = $review->encode($csv, 'comma', $bindings, true, ['name', 'email']);
        self::assertSame($report, json_decode($wire, true, 512, JSON_THROW_ON_ERROR));
        foreach (['PRIVATE_FIXTURE', 'label', '"name"', '"email"', '"rows"', '"fingerprint"', '"tenant_id"'] as $private) {
            self::assertStringNotContainsString($private, $wire);
        }
        self::assertLessThanOrEqual(PortableCsvReviewReportV1::MAX_JSON_BYTES, strlen($wire));
        foreach (['authorized', 'can_import', 'can_export', 'can_merge', 'can_deduplicate', 'can_persist'] as $flag) {
            self::assertFalse($report[$flag]);
        }
    }

    public function test_opt_in_is_optional_and_four_argument_behavior_remains_deterministic(): void
    {
        $engine = new PortableCsvReviewReportV1();
        $args = ["header,notes\nAlpha,plain", 'comma', [self::bind(0, 'name')], true];
        $a = $engine->build(...$args);
        $b = $engine->build(...[...$args, null]);
        self::assertSame($a, $b);
        self::assertFalse($a['required_field_check_performed']);
        self::assertArrayNotHasKey('claimed_required_fields', $a['counts']);
        self::assertSame($engine->encode(...$args), $engine->encode(...[...$args, null]));
        self::assertNotContains('CLAIMED_REQUIRED_FIELD_POLICY_NOT_VERIFIED', $a['warning_codes']);
    }

    public function test_formula_in_omitted_column_remains_visible_as_aggregate_only(): void
    {
        $wire = (new PortableCsvReviewReportV1())->encode(
            "a;b\nSample;\u{200A}=HYPERLINK(1)",
            'semicolon', [self::bind(0, 'origin')], true,
        );
        $report = json_decode($wire, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $report['counts']['source_formula_refusals']);
        self::assertSame(0, $report['counts']['mapped_formula_refusals']);
        self::assertContains('SOURCE_CONTAINS_CELLS_REFUSED_BY_STRICT_CSV_WRITER', $report['warning_codes']);
        self::assertStringNotContainsString('HYPERLINK', $wire);
        self::assertFalse($report['can_export']);
    }

    public function test_invalid_inputs_are_rejected_without_echoing_csv_or_fields(): void
    {
        $engine = new PortableCsvReviewReportV1();
        foreach ([
            ['SECRET_FIXTURE', 'pipe', [self::bind(0, 'name')], true, null],
            ['SECRET_FIXTURE', 'comma', [self::bind(12, 'name')], true, null],
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], false, ['password']],
            ['', 'comma', [self::bind(0, 'name')], false, null],
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], 'true', null],
        ] as $args) {
            try {
                $engine->encode(...$args);
                self::fail('Malformed review should fail closed.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Relatorio portatil CSV V1 invalido.', $e->getMessage());
                self::assertStringNotContainsString('SECRET_FIXTURE', $e->getMessage());
                self::assertStringNotContainsString('password', $e->getMessage());
            }
        }
    }
}
