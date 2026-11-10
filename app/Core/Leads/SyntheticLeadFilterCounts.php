<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-34: offline-only count of bounded, artificial lead fixtures.
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

        // Validate the whole input first, including hidden and unmatched rows.
        $summary = (new SyntheticPipelineSummary())->summarize($fixtures);
        $bands = new LeadScoringBands();
        $matched = 0;
        foreach ($fixtures as $fixture) {
            if (($stage === 'all' || $fixture['stage'] === $stage)
                && ($temperature === 'all' || $bands->classify($fixture['score'])->value === $temperature)) {
                ++$matched;
            }
        }

        return [
            'matched' => $matched,
            'total' => $summary['total'],
            'empty' => $matched === 0,
            'authorized' => false,
        ];
    }
}
