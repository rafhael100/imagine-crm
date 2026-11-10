<?php

declare(strict_types=1);

namespace App\Core\Leads;

use LogicException;

/**
 * F3-37: offline-only composition of synthetic filter and stable-sort mocks.
 *
 * Positions refer exclusively to elements of the caller's artificial input
 * (maximum 20). No rows, identities, tenant IDs, personal data, authorization,
 * query execution, persistence or API are emitted.
 */
final class SyntheticLeadListPreview
{
    /**
     * @param list<array{stage:string,score:int}> $fixtures
     * @return array{positions:list<int>,matched:int,total:int,empty:bool,authorized:false}
     */
    public function preview(
        array $fixtures,
        string $stage = 'all',
        string $temperature = 'all',
        string $sortMode = 'original',
    ): array {
        $counts = (new SyntheticLeadFilterCounts())->count($fixtures, $stage, $temperature);
        $sorted = (new SyntheticLeadSortPreview())->order($fixtures, $sortMode);

        $bands = new LeadScoringBands();
        $positions = [];
        foreach ($sorted['positions'] as $position) {
            $fixture = $fixtures[$position];
            if (($stage === 'all' || $fixture['stage'] === $stage)
                && ($temperature === 'all' || $bands->classify($fixture['score'])->value === $temperature)) {
                $positions[] = $position;
            }
        }

        // Internal consistency must fail closed, never silently reveal rows.
        if (count($positions) !== $counts['matched'] || $sorted['total'] !== $counts['total']) {
            throw new LogicException('Synthetic preview count mismatch.');
        }

        return [
            'positions' => $positions,
            'matched' => $counts['matched'],
            'total' => $counts['total'],
            'empty' => $counts['empty'],
            'authorized' => false,
        ];
    }
}
