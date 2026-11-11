<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreview;
use App\Core\Portability\SyntheticKanbanCsvPreviewBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticKanbanCsvPreviewBuilderTest extends TestCase
{
    private const HEADER = "id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao\n";

    /** @return list<array{id:string,stage:int,priority:string,followup:string,value:int,score:int}> */
    private static function htmlFixtures(): array
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html',
        );
        self::assertIsString($html);
        self::assertStringContainsString('function csvFromVisible(items){', $html);
        self::assertStringContainsString(
            "return [item.id,item.stage,item.priority,item.followup,item.value,item.score].join(';');",
            $html,
        );
        self::assertStringContainsString(
            'id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao',
            $html,
        );

        self::assertSame(8, preg_match_all(
            "/Object\\.freeze\\(\\{id:'(F\\d\\d)',label:'[^']*',stage:(\\d),priority:'(high|normal)',value:(\\d+),score:(\\d+),followup:'(pending|done)'\\}\\)/",
            $html, $matches, PREG_SET_ORDER,
        ));

        $records = [];
        foreach ($matches as $match) {
            $records[] = [
                'id' => $match[1],
                'stage' => (int) $match[2],
                'priority' => $match[3],
                'followup' => $match[6],
                'value' => (int) $match[4],
                'score' => (int) $match[5],
            ];
        }

        return $records;
    }

    public function test_php_serialization_agrees_with_exact_eight_actual_html_fixture_rows(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $records = self::htmlFixtures();
        $csv = $builder->render($records);

        self::assertSame(self::HEADER.
            "F01;0;high;pending;1800;92\n".
            "F02;0;normal;done;960;53\n".
            "F03;1;high;pending;3900;86\n".
            "F04;1;normal;done;1700;64\n".
            "F05;2;high;pending;5300;90\n".
            "F06;2;normal;pending;2450;68\n".
            "F07;3;high;pending;7600;95\n".
            "F08;3;normal;pending;1100;57\n",
            $csv,
        );
        self::assertFalse($builder->canExportContacts());
        self::assertFalse($builder->canAuthorizeImport());
        self::assertFalse($builder->canPersistLead());
        self::assertStringNotContainsString('Oportunidade fictícia', $csv);
        self::assertStringNotContainsString('private@example.test', $csv);
        self::assertStringNotContainsString('nome', $csv);
    }

    public function test_php_generated_csv_is_structurally_accepted_by_canonical_csv_preview_without_import_authority(): void
    {
        $csv = (new SyntheticKanbanCsvPreviewBuilder())->render(self::htmlFixtures());
        $parsed = (new SyntheticCsvImportPreview())->inspect($csv, ';');
        self::assertSame(6, $parsed->columnsInspected);
        self::assertSame(8, $parsed->rowsInspected);
        self::assertSame(0, $parsed->emptyCells);
        self::assertSame([], $parsed->potentialFormulaPositions);
        self::assertFalse($parsed->canImport());
    }

    public function test_empty_filtered_subset_has_only_fixed_header_and_is_not_export_permission(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        self::assertSame(self::HEADER, $builder->render([]));
        $parsed = (new SyntheticCsvImportPreview())->inspect(self::HEADER, ';');
        self::assertSame(0, $parsed->rowsInspected);
        self::assertSame(6, $parsed->columnsInspected);
        self::assertFalse($builder->canExportContacts());
    }

    public function test_priority_filter_preserves_only_matching_fixture_order_and_generated_F09(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $records = self::htmlFixtures();
        $high = array_values(array_filter(
            $records,
            static fn (array $record): bool => $record['priority'] === 'high',
        ));
        self::assertSame(self::HEADER.
            "F01;0;high;pending;1800;92\n".
            "F03;1;high;pending;3900;86\n".
            "F05;2;high;pending;5300;90\n".
            "F07;3;high;pending;7600;95\n",
            $builder->render($high),
        );

        $records[] = [
            'id' => 'F09', 'stage' => 0, 'priority' => 'high',
            'followup' => 'pending', 'value' => 1850, 'score' => 59,
        ];
        $result = $builder->render($records);
        self::assertStringEndsWith("F09;0;high;pending;1850;59\n", $result);
        self::assertSame(9, (new SyntheticCsvImportPreview())->inspect($result, ';')->rowsInspected);
    }

    public function test_invalid_records_reject_whole_preview_without_reflecting_untrusted_cells(): void
    {
        $builder = new SyntheticKanbanCsvPreviewBuilder();
        $good = self::htmlFixtures()[0];
        $bad = [
            null, 'data', true, new \stdClass(), [0 => $good, 2 => $good],
            array_fill(0, 13, $good),
            [$good, $good],
            [array_merge($good, ['email' => 'private@example.test'])],
            [array_merge($good, ['name' => 'PRIVATE_PERSON'])],
            [array_merge($good, ['consent' => true])],
            [array_diff_key($good, ['value' => 1])],
            [array_merge($good, ['id' => '=CMD(1)'])],
            [array_merge($good, ['id' => 'F01'."\n"])],
            [array_merge($good, ['id' => 'F13'])],
            [array_merge($good, ['priority' => 'High'])],
            [array_merge($good, ['followup' => 'planned'])],
            [array_merge($good, ['stage' => -1])],
            [array_merge($good, ['stage' => 4])],
            [array_merge($good, ['stage' => '1'])],
            [array_merge($good, ['score' => 101])],
            [array_merge($good, ['score' => true])],
            [array_merge($good, ['value' => -1])],
            [array_merge($good, ['value' => 1.4])],
        ];
        foreach ($bad as $records) {
            try {
                $builder->render($records);
                self::fail('Invalid synthetic Kanban CSV preview record accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Previa CSV ficticia invalida.', $e->getMessage());
                self::assertStringNotContainsString('private@example.test', $e->getMessage());
                self::assertStringNotContainsString('PRIVATE_PERSON', $e->getMessage());
            }
        }
    }
}
