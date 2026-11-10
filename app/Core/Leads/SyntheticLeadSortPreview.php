<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-36: deterministic order of indices of artificial offline fixtures.
 *
 * Indices only refer to the caller's bounded in-memory list. No record ID,
 * persistence, tenant, personal field, SQL, access grant or actual list read.
 */
final class SyntheticLeadSortPreview
{
    /**
     * @param list<array{stage:string,score:int}> $fixtures
     * @return array{positions:list<int>,total:int,authorized:false}
     */
    public function order(array $fixtures, string $mode = 'original'): array
    {
        if (!in_array($mode, ['original', 'high', 'low'], true)) {
            throw new InvalidArgumentException('Invalid synthetic sort mode.');
        }

        // A closed, bounded schema is validated before any sort takes place.
        $summary = (new SyntheticPipelineSummary())->summarize($fixtures);
        $positions = array_keys($fixtures);
        if ($mode !== 'original') {
            usort($positions, static function (int $a, int $b) use ($fixtures, $mode): int {
                $scoreOrder = $mode === 'high'
                    ? ($fixtures[$b]['score'] <=> $fixtures[$a]['score'])
                    : ($fixtures[$a]['score'] <=> $fixtures[$b]['score']);

                // A stable tie-breaker, independent of PHP's sort stability.
                return $scoreOrder !== 0 ? $scoreOrder : ($a <=> $b);
            });
        }

        return [
            'positions' => $positions,
            'total' => $summary['total'],
            'authorized' => false,
        ];
    }
}
