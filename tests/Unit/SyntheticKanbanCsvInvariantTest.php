<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticKanbanCsvPreviewBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * F3-88: deterministic property-style checks across the bounded, entirely
 * synthetic F01..F12 CSV contract. No customer data or operational I/O.
 */
final class SyntheticKanbanCsvInvariantTest extends TestCase
{
    /** @return list<array{id:string,stage:int,priority:string,followup:string,value:int,score:int}> */
    private static function fixtures(): array
    {
        $rows = [];
        for ($i = 1; $i <= 12; ++$i) {
            $rows[] = [
                'id' => sprintf('F%02d', $i),
                'stage' => ($i - 1) % 4,
                'priority' => $i % 2 === 0 ? 'normal' : 'high',
                'followup' => $i % 3 === 0 ? 'done' : 'pending',
                'value' => $i * 125,
                'score' => ($i * 7) % 101,
            ];
        }

        return $rows;
    }

    public function test_all_prefix_sizes_and_reversed_order_preserve_canonical_six_column_rows(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $parser = new SyntheticCsvImportPreview();
        $records = self::fixtures();

        for ($count = 0; $count <= 12; ++$count) {
            foreach ([array_slice($records, 0, $count), array_reverse(array_slice($records, 0, $count))] as $subset) {
                $csv = $builder->render($subset);
                self::assertStringStartsWith(
                    "id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao\n",
                    $csv,
                );
                self::assertSame($count + 1, substr_count($csv, "\n"));
                self::assertSame($csv, $builder->render($subset));
                self::assertStringNotContainsString("\r", $csv);

                $result = $parser->inspect($csv, ';');
                self::assertSame(6, $result->columnsInspected);
                self::assertSame($count, $result->rowsInspected);
                self::assertSame(0, $result->emptyCells);
                self::assertSame([], $result->potentialFormulaPositions);
                self::assertFalse($result->canImport());
            }
        }

        self::assertFalse($builder->canExportContacts());
        self::assertFalse($builder->canAuthorizeImport());
        self::assertFalse($builder->canPersistLead());
    }

    public function test_every_single_field_mutation_fails_closed_without_reflecting_sensitive_values(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $valid = self::fixtures()[0];
        $mutations = [
            ['id', 'F13'],
            ['id', '=HYPERLINK("https://example.test")'],
            ['id', "F01\nINJECTED"],
            ['stage', '0'],
            ['stage', -1],
            ['stage', 4],
            ['priority', 'HIGH'],
            ['priority', '+SUM(1,1)'],
            ['followup', 'unknown'],
            ['followup', 'done;INJECTED'],
            ['value', -1],
            ['value', '125'],
            ['score', -1],
            ['score', 101],
            ['score', false],
        ];

        foreach ($mutations as [$key, $value]) {
            $invalid = $valid;
            $invalid[$key] = $value;
            try {
                $builder->render([$invalid]);
                self::fail('Invalid synthetic CSV row accepted: '.$key);
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Previa CSV ficticia invalida.', $exception->getMessage());
                self::assertStringNotContainsString('INJECTED', $exception->getMessage());
                self::assertStringNotContainsString('example.test', $exception->getMessage());
            }
        }
    }

    public function test_all_twelve_ids_are_unique_even_when_order_changes_and_duplicates_are_rejected(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $rows = self::fixtures();

        foreach ([0, 1, 3, 5, 8, 11] as $offset) {
            $rotated = array_merge(array_slice($rows, $offset), array_slice($rows, 0, $offset));
            $csv = $builder->render($rotated);
            $ids = array_map(
                static fn (string $line): string => explode(';', $line, 2)[0],
                array_slice(explode("\n", trim($csv)), 1),
            );
            self::assertCount(12, $ids);
            self::assertCount(12, array_unique($ids));
            self::assertSame($rotated[0]['id'], $ids[0]);
            self::assertStringNotContainsString('F13;', $csv);
        }

        foreach (['F01', 'F09', 'F12'] as $duplicate) {
            $bad = $rows;
            $bad[0]['id'] = $duplicate;
            if ($duplicate === 'F01') {
                $bad[1]['id'] = 'F01';
            }
            $this->expectExceptionForDuplicate($builder, $bad);
        }
    }

    /** @param list<array<string,mixed>> $rows */
    private function expectExceptionForDuplicate(SyntheticKanbanCsvPreviewBuilder $builder, array $rows): void
    {
        try {
            $builder->render($rows);
            self::fail('Duplicate synthetic fixture ID accepted.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Previa CSV ficticia invalida.', $exception->getMessage());
        }
    }
}
