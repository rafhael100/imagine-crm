<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * VEN-R28/R29: pure scoring of invented rule signals (no IA or lead data).
 *
 * All input signals are unverified booleans, weights an illustrative policy.
 * The score/band is neither an official source fact nor a permission to
 * trigger automation, redistribute real leads, notify or write.
 */
final class SyntheticLeadRuleScorePreview
{
    private const SIGNAL_CODES = [
        'profile_complete',
        'recent_engagement',
        'requested_quote',
        'scheduled_demo',
        'stale_response',
    ];

    /**
     * @param mixed $signals Strict map of the five signal names to bool
     * @param mixed $weights Strict map of the five signal names to int -100..100
     * @param mixed $baseScore int from 0 to 100
     * @return array<string,mixed> No identifiers or signal payload contents
     */
    public function preview(
        mixed $signals,
        mixed $weights,
        mixed $baseScore,
        ?LeadScoringBands $bands = null,
    ): array {
        if (!is_array($signals) || !is_array($weights)
            || !is_int($baseScore)
            || $baseScore < 0 || $baseScore > 100
            || count($signals) !== count(self::SIGNAL_CODES)
            || count($weights) !== count(self::SIGNAL_CODES)) {
            self::invalid();
        }

        $signalKeys = array_keys($signals);
        $weightKeys = array_keys($weights);
        sort($signalKeys);
        sort($weightKeys);
        $expected = self::SIGNAL_CODES;
        sort($expected);
        if ($signalKeys !== $expected || $weightKeys !== $expected) {
            self::invalid();
        }

        $activeCount = 0;
        $positivePoints = 0;
        $negativePoints = 0;
        foreach (self::SIGNAL_CODES as $code) {
            if (!is_bool($signals[$code])
                || !is_int($weights[$code])
                || $weights[$code] < -100
                || $weights[$code] > 100) {
                self::invalid();
            }
            if (!$signals[$code]) {
                continue;
            }

            ++$activeCount;
            if ($weights[$code] >= 0) {
                $positivePoints += $weights[$code];
            } else {
                $negativePoints += $weights[$code];
            }
        }

        $raw = $baseScore + $positivePoints + $negativePoints;
        $score = min(100, max(0, $raw));
        $policy = $bands ?? new LeadScoringBands();

        return [
            'signal_count' => count(self::SIGNAL_CODES),
            'active_signal_count' => $activeCount,
            'base_score' => $baseScore,
            'positive_points' => $positivePoints,
            'negative_points' => $negativePoints,
            'raw_score' => $raw,
            'score' => $score,
            'band' => $policy->classify($score)->value,
            'clamped' => $score !== $raw,
            'signals_verified' => false,
            'weight_policy_verified' => false,
            'band_policy_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_update_lead' => false,
            'can_trigger_automation' => false,
            'can_redistribute' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Pontuacao sintetica por regras invalida.');
    }
}
