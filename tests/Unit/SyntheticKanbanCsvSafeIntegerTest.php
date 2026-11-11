<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticKanbanCsvPreviewBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticKanbanCsvSafeIntegerTest extends TestCase
{
    private const JS_MAX_SAFE_INTEGER = 9007199254740991;

    /** @return array{id:string,stage:int,priority:string,followup:string,value:int,score:int} */
    private static function row(int $amount): array
    {
        return [
            'id' => 'F01',
            'stage' => 0,
            'priority' => 'high',
            'followup' => 'pending',
            'value' => $amount,
            'score' => 75,
        ];
    }

    public function test_maximum_exact_javascript_integer_is_preserved_without_import_permission(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $csv = $builder->render([self::row(self::JS_MAX_SAFE_INTEGER)]);
        self::assertStringContainsString('F01;0;high;pending;9007199254740991;75', $csv);

        $preview = (new SyntheticCsvImportPreview())->inspect($csv, ';');
        self::assertSame(1, $preview->rowsInspected);
        self::assertSame(6, $preview->columnsInspected);
        self::assertSame([], $preview->potentialFormulaPositions);
        self::assertFalse($preview->canImport());
        self::assertFalse($builder->canExportContacts());
    }

    public function test_total_fictional_value_must_also_be_exact_in_javascript(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $second = self::row(1);
        $second['id'] = 'F02';

        // Two valid individual numeric cells, and an exactly representable total.
        $csv = $builder->render([self::row(self::JS_MAX_SAFE_INTEGER - 1), $second]);
        self::assertStringContainsString("F02;0;high;pending;1;75\n", $csv);
        self::assertSame(2, (new SyntheticCsvImportPreview())->inspect($csv, ';')->rowsInspected);

        // Both records individually pass, but their SUM cannot be represented
        // exactly by the browser's Number type. The entire preview fails closed.
        try {
            $builder->render([self::row(self::JS_MAX_SAFE_INTEGER), $second]);
            self::fail('Synthetic aggregate exceeded Number.MAX_SAFE_INTEGER.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Previa CSV ficticia invalida.', $exception->getMessage());
            self::assertStringNotContainsString('9007199254740991', $exception->getMessage());
        }
        self::assertFalse($builder->canExportContacts());
        self::assertFalse($builder->canAuthorizeImport());
        self::assertFalse($builder->canPersistLead());
    }

    public function test_unsafe_integer_is_denied_even_when_other_fields_are_valid(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();

        foreach ([self::JS_MAX_SAFE_INTEGER + 1, PHP_INT_MAX] as $amount) {
            try {
                $builder->render([self::row($amount)]);
                self::fail('Unsafe synthetic numeric cell accepted');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Previa CSV ficticia invalida.', $exception->getMessage());
                self::assertStringNotContainsString((string) $amount, $exception->getMessage());
            }
        }
    }
}
