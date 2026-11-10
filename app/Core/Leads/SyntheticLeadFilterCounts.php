<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-34/F3-35: offline-only count of bounded, artificial lead fixtures.
 * No lead identity, row exposure, tenant, SQL, authorization or API.
 */
final class SyntheticLeadFilterCounts
{
    /**
     * @param list<array{stage:string, score:int}> $fixtures
     * @return array{matched:int,total:int,empty:bool,authorized:false}
     */
    public function count(array $fixtures, string $stage = 'all', string $temperature = 'all'): array
    {
        if (!in_array($stage, ['all', 'entrada', 'em-contato', 'qualificacao'], true)
            || !in_array($temperature, ['all', 'frio', 'morno', 'quente'], true)) {
            throw new InvalidArgumentException('Invalid synthetic lead filters.');
        }

        // One validated source of counts for the UI badges and filtered totals.
        // Invalid, hidden, or unmatched fixtures are never silently skipped.
        $facets = (new SyntheticLeadFacetMatrix())->summarize($fixtures);

        if ($stage === 'all' && $temperature === 'all') {
            $matched = $facets['total'];
        } elseif ($stage === 'all') {
            $matched = $facets['temperature_totals'][$temperature];
        } elseif ($temperature === 'all') {
            $matched = $facets['stage_totals'][$stage];
        } else {
            $matched = $facets['cells'][$stage][$temperature];
        }

        return [
            'matched' => $matched,
            'total' => $facets['total'],
            'empty' => $matched === 0,
            'authorized' => false,
        ];
    }
}
