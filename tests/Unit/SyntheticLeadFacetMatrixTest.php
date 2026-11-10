<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadFacetMatrix;
use App\Core\Leads\SyntheticLeadFilterCounts;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadFacetMatrixTest extends TestCase
{
    private const FIXTURES = [
        ['stage' => 'entrada', 'score' => 30],
        ['stage' => 'em-contato', 'score' => 55],
        ['stage' => 'qualificacao', 'score' => 85],
        ['stage' => 'entrada', 'score' => 70],
    ];

    public function test_matrix_has_expected_cells_and_non_disclosing_response(): void
    {
        $facets = (new SyntheticLeadFacetMatrix())->summarize(self::FIXTURES);

        self::assertSame(4, $facets['total']);
        self::assertSame([
            'entrada' => ['frio' => 1, 'morno' => 0, 'quente' => 1],
            'em-contato' => ['frio' => 0, 'morno' => 1, 'quente' => 0],
            'qualificacao' => ['frio' => 0, 'morno' => 0, 'quente' => 1],
        ], $facets['cells']);
        self::assertSame(['entrada' => 2, 'em-contato' => 1, 'qualificacao' => 1], $facets['stage_totals']);
        self::assertSame(['frio' => 1, 'morno' => 1, 'quente' => 2], $facets['temperature_totals']);
        self::assertSame(['total', 'cells', 'stage_totals', 'temperature_totals', 'authorized'], array_keys($facets));
        self::assertFalse($facets['authorized']);

        foreach ($facets['cells'] as $stage => $temperatures) {
            self::assertSame($facets['stage_totals'][$stage], array_sum($temperatures));
        }
        foreach ($facets['temperature_totals'] as $temperature => $count) {
            self::assertSame($count, array_sum(array_column(array_values($facets['cells']), $temperature)));
        }
    }

    public function test_all_sixteen_filter_combinations_use_the_same_matrix(): void
    {
        $facets = (new SyntheticLeadFacetMatrix())->summarize(self::FIXTURES);
        $filters = new SyntheticLeadFilterCounts();

        foreach (['all', 'entrada', 'em-contato', 'qualificacao'] as $stage) {
            foreach (['all', 'frio', 'morno', 'quente'] as $temperature) {
                $expected = $stage === 'all'
                    ? ($temperature === 'all' ? $facets['total'] : $facets['temperature_totals'][$temperature])
                    : ($temperature === 'all' ? $facets['stage_totals'][$stage] : $facets['cells'][$stage][$temperature]);
                $result = $filters->count(self::FIXTURES, $stage, $temperature);
                self::assertSame($expected, $result['matched']);
                self::assertSame($expected === 0, $result['empty']);
                self::assertFalse($result['authorized']);
            }
        }
    }

    public function test_zero_and_threshold_boundaries_are_counted_consistently(): void
    {
        $fixtures = [
            ['stage' => 'entrada', 'score' => 0],
            ['stage' => 'entrada', 'score' => 39],
            ['stage' => 'entrada', 'score' => 40],
            ['stage' => 'entrada', 'score' => 69],
            ['stage' => 'entrada', 'score' => 70],
            ['stage' => 'entrada', 'score' => 100],
        ];
        $facets = (new SyntheticLeadFacetMatrix())->summarize($fixtures);
        self::assertSame(['frio' => 2, 'morno' => 2, 'quente' => 2], $facets['temperature_totals']);
        self::assertSame($facets, (new SyntheticLeadFacetMatrix())->summarize(array_reverse($fixtures)));
    }

    public function test_empty_input_keeps_complete_zero_matrix(): void
    {
        $result = (new SyntheticLeadFacetMatrix())->summarize([]);
        self::assertSame(0, $result['total']);
        self::assertSame(['entrada' => 0, 'em-contato' => 0, 'qualificacao' => 0], $result['stage_totals']);
        self::assertSame(['frio' => 0, 'morno' => 0, 'quente' => 0], $result['temperature_totals']);
        foreach ($result['cells'] as $row) {
            self::assertSame([0, 0, 0], array_values($row));
        }
        self::assertFalse($result['authorized']);
    }

    public function test_rejects_forged_fields_unknown_stages_scores_and_oversized_input(): void
    {
        $invalid = [
            [['stage' => 'entrada', 'score' => 30, 'tenant_id' => 'unverified']],
            [['stage' => 'entrada', 'score' => 30, 'lead_id' => 'unverified']],
            [['stage' => 'closed', 'score' => 30]],
            [['stage' => 'entrada', 'score' => -1]],
            [['stage' => 'entrada', 'score' => 101]],
            [['stage' => 'entrada', 'score' => '90']],
            [3 => ['stage' => 'entrada', 'score' => 30]],
            array_fill(0, 21, ['stage' => 'entrada', 'score' => 30]),
        ];
        foreach ($invalid as $fixtures) {
            try {
                (new SyntheticLeadFacetMatrix())->summarize($fixtures);
                self::fail('Invalid synthetic fixture unexpectedly accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }
}
