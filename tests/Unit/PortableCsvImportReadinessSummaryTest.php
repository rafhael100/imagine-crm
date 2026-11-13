<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvImportReadinessSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvImportReadinessSummaryTest extends TestCase
{
    private static function binding(int $index, string $field): array
    {
        return ['source_position' => $index, 'target_field' => $field];
    }

    public function test_composes_header_and_content_risks_without_exposing_values(): void
    {
        $csv = "alias,alias,,secret\nAliasA,=SUM(1),,PRIVATE_FIXTURE";
        $out = (new PortableCsvImportReadinessSummary())->inspect(
            $csv, 'comma', [self::binding(0, 'name'), self::binding(1, 'notes')], true,
        );
        self::assertSame(4, $out['local_source_column_count']);
        self::assertSame(1, $out['local_data_row_count']);
        self::assertSame(1, $out['local_duplicate_exact_header_count']);
        self::assertSame(1, $out['local_empty_header_count']);
        self::assertSame(1, $out['local_source_formula_refused_cell_count']);
        self::assertSame(1, $out['local_mapped_formula_refused_cell_count']);
        self::assertSame(count($out['warning_codes']), $out['warning_count']);
        self::assertCount(count(array_unique($out['warning_codes'])), $out['warning_codes']);
        self::assertStringNotContainsString('PRIVATE_FIXTURE', json_encode($out, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('SUM(1)', json_encode($out, JSON_THROW_ON_ERROR));
        foreach (['can_import', 'can_export', 'can_merge', 'can_persist', 'authorization_verified'] as $key) {
            self::assertFalse($out[$key], $key);
        }
    }

    public function test_headerless_sample_does_not_count_header_risks(): void
    {
        $out = (new PortableCsvImportReadinessSummary())->inspect(
            "x,x\ny,z", 'comma', [self::binding(0, 'origin')], false,
        );
        self::assertSame(2, $out['local_data_row_count']);
        self::assertSame(0, $out['local_duplicate_exact_header_count']);
        self::assertContains('NO_HEADER_DECLARED', $out['warning_codes']);
    }

    public function test_invalid_input_is_rejected_without_echo(): void
    {
        try {
            (new PortableCsvImportReadinessSummary())->inspect(
                "SECRET_FIXTURE", 'comma', [self::binding(9, 'name')], true,
            );
            self::fail('Invalid binding accepted');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Revisao agregada CSV local invalida.', $exception->getMessage());
            self::assertStringNotContainsString('SECRET_FIXTURE', $exception->getMessage());
        }
    }
}
