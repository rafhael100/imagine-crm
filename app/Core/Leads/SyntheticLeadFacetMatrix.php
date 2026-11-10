<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-35: synthetic-only stage/temperature facets for the offline prototype.
 *
 * No individual lead details, identities, tenant context, persisted state,
 * query execution, permission or real business entitlement.
 */
final class SyntheticLeadFacetMatrix
{
    /**
     * @param list<array{stage:string,score:int}> $fixtures
     * @return array{
     *   total:int,
     *   cells:array<string,array<string,int>>,
     *   stage_totals:array<string,int>,
     *   temperature_totals:array<string,int>,
     *   authorized:false
     * }
     */
    public function summarize(array $fixtures): array
    {
        // Validate all records (including ones a UI filter might later hide)
        // with the existing bounded closed-vocabulary contract first.
        $summary = (new SyntheticPipelineSummary())->summarize($fixtures);
        $cells = [];
        foreach (array_keys($summary['stages']) as $stage) {
            $cells[$stage] = array_fill_keys(array_keys($summary['temperatures']), 0);
        }

        $bands = new LeadScoringBands();
        foreach ($fixtures as $fixture) {
            $cells[$fixture['stage']][$bands->classify($fixture['score'])->value]++;
        }

        return [
            'total' => $summary['total'],
            'cells' => $cells,
            'stage_totals' => $summary['stages'],
            'temperature_totals' => $summary['temperatures'],
            'authorized' => false,
        ];
    }
}
