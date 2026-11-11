<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvHeaderTemplate;
use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvHeaderTemplateTest extends TestCase
{
    public function test_default_template_is_comma_separated_header_only_and_not_importable(): void
    {
        $builder = new SyntheticCsvHeaderTemplate();
        $csv = $builder->render(['name', 'stage', 'source']);

        self::assertSame("name,stage,source\r\n", $csv);
        self::assertFalse($builder->canExportContacts());
        self::assertFalse($builder->canAuthorizeImport());

        $preview = (new SyntheticCsvImportPreview())->inspect($csv);
        self::assertSame(0, $preview->rowsInspected);
        self::assertSame(3, $preview->columnsInspected);
        self::assertSame(0, $preview->emptyCells);
        self::assertSame([], $preview->potentialFormulaPositions);
        self::assertFalse($preview->canImport());
        self::assertEquals($preview, (new SyntheticCsvPreviewDecoder())->preview($csv));
    }

    public function test_bom_semicolon_template_works_with_existing_mapping_and_duplicate_preview(): void
    {
        $labels = ['stage', 'name', 'contact_ref'];
        $builder = new SyntheticCsvHeaderTemplate();
        $csv = $builder->render($labels, ';', true);

        self::assertSame("\xEF\xBB\xBFstage;name;contact_ref\r\n", $csv);
        self::assertSame(0, substr_count($csv, 'SYNTHETIC_PERSON'));
        self::assertSame(1, substr_count($csv, "\xEF\xBB\xBF"));
        self::assertSame(0, substr_count($csv, '"'));
        $parser = new SyntheticCsvImportPreview();
        $preview = $parser->inspect($csv, ';');
        self::assertSame(0, $preview->rowsInspected);
        self::assertSame(3, $preview->columnsInspected);

        $mapping = $parser->inspectSuggested($csv, ['contact_ref', 'stage', 'name'], ';');
        self::assertTrue($mapping->isMappingComplete());
        self::assertFalse($mapping->canImport());
        self::assertSame([
            ['source' => 1, 'target' => 2],
            ['source' => 2, 'target' => 3],
            ['source' => 3, 'target' => 1],
        ], $mapping->mappingPreview->positions);

        $repeats = $parser->inspectRepeatedRows($csv, ';');
        self::assertFalse($repeats->hasRepeatedRows());
        self::assertFalse($repeats->canImport());
        self::assertFalse($repeats->canDeduplicateContacts());
    }

    public function test_one_column_and_exact_eight_column_templates_are_supported(): void
    {
        $builder = new SyntheticCsvHeaderTemplate();
        $one = $builder->render(['name'], ';');
        self::assertSame("name\r\n", $one);

        $eight = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $csv = $builder->render($eight, ';');
        $preview = (new SyntheticCsvImportPreview())->inspect($csv, ';');
        self::assertSame(8, $preview->columnsInspected);
        self::assertSame(0, $preview->rowsInspected);
        self::assertFalse($preview->canImport());
    }

    public function test_invalid_labels_or_delimiters_are_rejected_without_echoing_untrusted_fields(): void
    {
        $invalid = [
            [],
            ['name', 'name'],
            ['Name'],
            ['first name'],
            ['1name'],
            ['=HYPERLINK'],
            ['name;stage'],
            ['name,stage'],
            ["name\nstage"],
            ["name\u{2060}"],
            ['name', true],
            ['name', new \stdClass()],
            ['name' => 'stage'],
            array_fill(0, 9, 'name'),
            [str_repeat('a', 33)],
        ];
        $builder = new SyntheticCsvHeaderTemplate();
        foreach ($invalid as $labels) {
            try {
                $builder->render($labels);
                self::fail('Invalid technical field label accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Modelo CSV sintetico invalido.', $e->getMessage());
                self::assertStringNotContainsString('HYPERLINK', $e->getMessage());
            }
        }

        foreach (['', '|', "\t", ';;', "\n", 'auto', ' ,'] as $separator) {
            try {
                $builder->render(['name'], $separator);
                self::fail('Unapproved delimiter accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Modelo CSV sintetico invalido.', $e->getMessage());
            }
        }
    }

    public function test_template_emits_no_data_rows_or_private_content_even_with_bom(): void
    {
        $template = (new SyntheticCsvHeaderTemplate())->render(['name', 'stage'], ';', true);
        self::assertSame(1, substr_count($template, "\r\n"));
        self::assertStringNotContainsString('private@example.test', $template);
        self::assertStringNotContainsString('SYNTHETIC_LEAD', $template);
        self::assertFalse(str_contains($template, "\0"));
        self::assertFalse((new SyntheticCsvHeaderTemplate())->canExportContacts());
    }
}
