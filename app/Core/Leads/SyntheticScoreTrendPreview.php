<?php

declare(strict_types=1);

namespace App\Core\Leads;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * VEN-R30 laboratory: synthetic score evolution only, never a verified lead
 * history, historical event log, scoring rules engine, tenant read or trigger.
 *
 * Caller supplies all fabricated timestamps/scores. The result contains
 * counts and descriptive band changes only, with no event content or IDs.
 */
final class SyntheticScoreTrendPreview
{
    private const MAX_SAMPLES = 16;

    /**
     * @param mixed $samples Strict list<array{observed_at_utc:string,score:int}>
     * @return array{
     *   sample_count:int,first_score:?int,last_score:?int,score_delta:?int,
     *   first_band:?string,last_band:?string,
     *   upward_steps:int,downward_steps:int,unchanged_steps:int,
     *   band_changes:int,min_score:?int,max_score:?int,
     *   band_policy_verified:false,history_verified:false,
     *   informational_only:true,authorized:false,can_rescore:false,
     *   can_notify:false,can_trigger_automation:false,can_persist:false
     * }
     */
    public function preview(mixed $samples, ?LeadScoringBands $bands = null): array
    {
        if (!is_array($samples)
            || !array_is_list($samples)
            || count($samples) > self::MAX_SAMPLES) {
            self::invalid();
        }

        $policy = $bands ?? new LeadScoringBands();
        $first = null;
        $last = null;
        $firstBand = null;
        $lastBand = null;
        $lowest = null;
        $highest = null;
        $previousUtc = null;
        $up = 0;
        $down = 0;
        $same = 0;
        $changed = 0;

        foreach ($samples as $sample) {
            if (!is_array($sample) || count($sample) !== 2) {
                self::invalid();
            }
            $keys = array_keys($sample);
            sort($keys);
            if ($keys !== ['observed_at_utc', 'score']
                || !is_string($sample['observed_at_utc'])
                || !is_int($sample['score'])
                || $sample['score'] < 0
                || $sample['score'] > 100
                || !self::canonicalUtc($sample['observed_at_utc'])
                || ($previousUtc !== null && strcmp($sample['observed_at_utc'], $previousUtc) <= 0)) {
                self::invalid();
            }

            $score = $sample['score'];
            $currentBand = $policy->classify($score)->value;
            if ($first === null) {
                $first = $score;
                $firstBand = $currentBand;
                $lowest = $score;
                $highest = $score;
            } else {
                if ($score > $last) {
                    ++$up;
                } elseif ($score < $last) {
                    ++$down;
                } else {
                    ++$same;
                }
                if ($currentBand !== $lastBand) {
                    ++$changed;
                }
                $lowest = min($lowest, $score);
                $highest = max($highest, $score);
            }

            $previousUtc = $sample['observed_at_utc'];
            $last = $score;
            $lastBand = $currentBand;
        }

        return [
            'sample_count' => count($samples),
            'first_score' => $first,
            'last_score' => $last,
            'score_delta' => $first === null ? null : $last - $first,
            'first_band' => $firstBand,
            'last_band' => $lastBand,
            'upward_steps' => $up,
            'downward_steps' => $down,
            'unchanged_steps' => $same,
            'band_changes' => $changed,
            'min_score' => $lowest,
            'max_score' => $highest,
            'band_policy_verified' => false,
            'history_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_rescore' => false,
            'can_notify' => false,
            'can_trigger_automation' => false,
            'can_persist' => false,
        ];
    }

    private static function canonicalUtc(string $timestamp): bool
    {
        if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D', $timestamp) !== 1) {
            return false;
        }
        $utc = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z',
            $timestamp,
            new DateTimeZone('UTC'),
        );

        return $utc !== false && $utc->format('Y-m-d\TH:i:s\Z') === $timestamp;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Evolucao sintetica de pontuacao invalida.');
    }
}
