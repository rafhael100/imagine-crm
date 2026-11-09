<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticImportPreviewInspector;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticImportPreviewInspectorTest extends TestCase
{
    public function test_valid_synthetic_rows_only_return_aggregate_metadata(): void
    {
        $report = (new SyntheticImportPreviewInspector())->inspect(
            ['label', 'source', 'note'],
            [
                ['Lead Exemplo A', 'site-exemplo', ''],
                ['Lead Exemplo B', 'site-exemplo', '=1+1'],
                ['Lead Exemplo C', 'manual', '  +SUM(A1)'],
            ],
        );

        self::assertSame(3, $report->rowsInspected);
        self::assertSame(3, $report->columnsInspected);
        self::assertSame(1, $report->emptyCells);
        self::assertSame(2, $report->potentiallyExecutableCellCount());
        self::assertSame([
            ['row' => 2, 'column' => 3],
            ['row' => 3, 'column' => 3],
        ], $report->potentialFormulaPositions);

        // This component never returns a row, customer data or write grant.
        self::assertFalse($report->canImport());
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('Lead Exemplo', $json);
        self::assertStringNotContainsString('site-exemplo', $json);
        self::assertStringNotContainsString('=1+1', $json);
        self::assertStringNotContainsString('label', $json);
    }

    public function test_distinct_synthetic_rows_with_identical_contents_are_not_collapsed(): void
    {
        $report = (new SyntheticImportPreviewInspector())->inspect(
            ['display_label'],
            [['Lead Exemplo'], ['Lead Exemplo']],
        );

        self::assertSame(2, $report->rowsInspected);
        self::assertSame(0, $report->potentiallyExecutableCellCount());
        self::assertFalse($report->canImport());
    }

    public function test_empty_preview_is_allowed_but_never_importable(): void
    {
        $report = (new SyntheticImportPreviewInspector())->inspect(['example'], []);
        self::assertSame(0, $report->rowsInspected);
        self::assertSame(0, $report->emptyCells);
        self::assertFalse($report->canImport());
    }

    public function test_boundaries_accept_only_fifty_rows_and_eight_columns(): void
    {
        $columns = ['a','b','c','d','e','f','g','h'];
        $rows = array_fill(0, 50, array_fill(0, 8, 'Exemplo'));
        $report = (new SyntheticImportPreviewInspector())->inspect($columns, $rows);

        self::assertSame(50, $report->rowsInspected);
        self::assertSame(8, $report->columnsInspected);
        self::assertSame(0, $report->potentiallyExecutableCellCount());
        self::assertFalse($report->canImport());
    }

    public function test_invalid_utf8_and_control_prefix_are_flagged_without_echoing_cells(): void
    {
        $report = (new SyntheticImportPreviewInspector())->inspect(
            ['sample'],
            [["\xff"], ["\tcontent"], ["\u{FEFF}=1"]],
        );

        self::assertSame(3, $report->potentiallyExecutableCellCount());
        self::assertFalse($report->canImport());
        self::assertStringNotContainsString('content', json_encode($report, JSON_THROW_ON_ERROR));
    }

    public static function malformedMatrices(): array
    {
        return [
            'no columns' => [[], []],
            'too many columns' => [array_map(
                static fn (int $number): string => 'c'.$number, range(1, 9)
            ), []],
            'repeated labels' => [['label','label'], []],
            'non-ascii header' => [['nome completo'], []],
            'header leading number' => [['1name'], []],
            'header associative array' => [['foo' => 'label'], []],
            'too many rows' => [['label'], array_fill(0, 51, ['ok'])],
            'row is not a list' => [['label'], [['label' => 'ok']]],
            'mismatched columns' => [['label','source'], [['a']]],
            'extra cell' => [['label'], [['a','b']]],
            'non-string cell' => [['label'], [[123]]],
            'null cell' => [['label'], [[null]]],
            'oversized cell' => [['label'], [[str_repeat('x', 513)]]],
            'non-list row collection' => [['label'], ['a' => ['ok']]],
        ];
    }

    #[DataProvider('malformedMatrices')]
    public function test_invalid_matrix_structure_fails_closed_without_revealing_input(
        array $columns,
        array $rows,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estrutura de pre-visualizacao invalida.');

        (new SyntheticImportPreviewInspector())->inspect($columns, $rows);
    }

    public function test_formula_like_text_is_flagged_not_automatically_rewritten(): void
    {
        $report = (new SyntheticImportPreviewInspector())->inspect(
            ['description'],
            [['-10'], ['plain'], ['@SUM(1)']],
        );

        self::assertSame(2, $report->potentiallyExecutableCellCount());
        self::assertFalse($report->canImport());
    }
}
