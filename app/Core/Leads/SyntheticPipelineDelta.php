<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-32 — Comparison of two independently supplied synthetic fixture sets.
 *
 * There is no record identity, transition history, matching, conversion rate,
 * tenant membership, persistence or permission. Differences in aggregate
 * counts MUST NOT be presented as observed individual lead movements.
 */
final class SyntheticPipelineDelta
{
    /**
     * @param list<array{stage:string, score:int}> $before
     * @param list<array{stage:string, score:int}> $after
     * @return array{before_total:int, after_total:int, total_delta:int, stage_deltas:array<string,int>, temperature_deltas:array<string,int>, authorized:false, matched_records:false}
     */
    public function compare(array $before, array $after): array
    {
        $summary = new SyntheticPipelineSummary();
        $a = $summary->summarize($before);
        $b = $summary->summarize($after);

        $stages = [];
        foreach ($a['stages'] as $stage => $count) {
            $stages[$stage] = $b['stages'][$stage] - $count;
        }

        $temperatures = [];
        foreach ($a['temperatures'] as $temperature => $count) {
            $temperatures[$temperature] = $b['temperatures'][$temperature] - $count;
        }

        return [
            'before_total' => $a['total'],
            'after_total' => $b['total'],
            'total_delta' => $b['total'] - $a['total'],
            'stage_deltas' => $stages,
            'temperature_deltas' => $temperatures,
            'authorized' => false,
            'matched_records' => false,
        ];
    }
}
