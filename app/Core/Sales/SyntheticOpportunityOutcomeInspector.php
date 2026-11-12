<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R04/R05 laboratory: anonymous, unverified opportunity outcome claims.
 *
 * A simulated "lost" outcome must carry an illustrative reason code. This
 * does NOT define tenant-configured reasons, authorize status transitions,
 * prove an opportunity was won/lost or count revenue/achieved targets.
 */
final class SyntheticOpportunityOutcomeInspector
{
    private const MAX_RECORDS = 20;
    private const OUTCOMES = ['open', 'won', 'lost'];
    private const LOSS_REASONS = ['price', 'timing', 'competition', 'no_response', 'other'];

    /**
     * @param mixed $records list<array{outcome:string,loss_reason_code:?string}>
     * @return array{
     *   samples:int,claimed_outcome_counts:array{open:int,won:int,lost:int},
     *   claimed_lost_by_reason:array{price:int,timing:int,competition:int,no_response:int,other:int},
     *   loss_reason_required:true,outcomes_verified:false,
     *   loss_reason_policy_verified:false,recognized_wins:int,
     *   informational_only:true,authorized:false,
     *   can_mark_won:false,can_mark_lost:false,can_reopen:false,
     *   can_count_revenue:false,can_notify:false,can_persist:false
     * }
     */
    public function inspect(mixed $records): array
    {
        if (!is_array($records)
            || !array_is_list($records)
            || count($records) > self::MAX_RECORDS) {
            self::invalid();
        }

        $outcomes = array_fill_keys(self::OUTCOMES, 0);
        $reasons = array_fill_keys(self::LOSS_REASONS, 0);

        foreach ($records as $record) {
            if (!is_array($record) || count($record) !== 2) {
                self::invalid();
            }
            $keys = array_keys($record);
            sort($keys);
            if ($keys !== ['loss_reason_code', 'outcome']
                || !is_string($record['outcome'])
                || !in_array($record['outcome'], self::OUTCOMES, true)) {
                self::invalid();
            }
            $outcome = $record['outcome'];
            $reason = $record['loss_reason_code'];
            if ($outcome === 'lost') {
                if (!is_string($reason) || !in_array($reason, self::LOSS_REASONS, true)) {
                    self::invalid();
                }
                ++$reasons[$reason];
            } elseif ($reason !== null) {
                // A won/open claim cannot carry a contradictory loss reason.
                self::invalid();
            }
            ++$outcomes[$outcome];
        }

        return [
            'samples' => count($records),
            'claimed_outcome_counts' => $outcomes,
            'claimed_lost_by_reason' => $reasons,
            'loss_reason_required' => true,
            'outcomes_verified' => false,
            'loss_reason_policy_verified' => false,
            'recognized_wins' => 0,
            'informational_only' => true,
            'authorized' => false,
            'can_mark_won' => false,
            'can_mark_lost' => false,
            'can_reopen' => false,
            'can_count_revenue' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Resultado sintetico de oportunidade invalido.');
    }
}
