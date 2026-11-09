<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-31: strictly synthetic in-memory counts for an offline pipeline mock.
 *
 * No lead IDs, tenant, principal, source, persistence, API, authorization,
 * export or transition effects. The stages are fixture labels, not a
 * proposed production workflow.
 */
final class SyntheticPipelineSummary
{
    private const STAGES = ['entrada', 'em-contato', 'qualificacao'];
    private const TEMPERATURES = ['cold', 'warm', 'hot'];

    /**
     * @param list<array{stage: string, score: int}> $fixtures
     * @return array{total: int, stages: array<string, int>, temperatures: array<string, int>, hot_by_stage: array<string, int>, authorized: false}
     */
    public function summarize(array $fixtures): array
    {
        if (count($fixtures) > 20 || !array_is_list($fixtures)) {
            throw new InvalidArgumentException('Invalid synthetic pipeline fixtures.');
        }

        $stages = array_fill_keys(self::STAGES, 0);
        $temperatures = array_fill_keys(self::TEMPERATURES, 0);
        $hotByStage = array_fill_keys(self::STAGES, 0);
        $bands = new LeadScoringBands();

        foreach ($fixtures as $fixture) {
            if (!is_array($fixture) || array_keys($fixture) !== ['stage', 'score']
                || !is_string($fixture['stage']) || !in_array($fixture['stage'], self::STAGES, true)
                || !is_int($fixture['score'])) {
                throw new InvalidArgumentException('Invalid synthetic pipeline fixtures.');
            }

            $stage = $fixture['stage'];
            $temperature = $bands->classify($fixture['score'])->value;
            $stages[$stage]++;
            $temperatures[$temperature]++;
            if ($temperature === 'hot') {
                $hotByStage[$stage]++;
            }
        }

        return [
            'total' => count($fixtures),
            'stages' => $stages,
            'temperatures' => $temperatures,
            'hot_by_stage' => $hotByStage,
            'authorized' => false,
        ];
    }
}
