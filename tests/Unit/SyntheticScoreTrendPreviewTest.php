<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadScoringBands;
use App\Core\Leads\SyntheticScoreTrendPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticScoreTrendPreviewTest extends TestCase
{
    /** @return list<array{observed_at_utc:string,score:int}> */
    private static function fixtures(): array
    {
        return [
            ['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => 20],
            ['observed_at_utc' => '2026-11-02T12:00:00Z', 'score' => 45],
            ['observed_at_utc' => '2026-11-03T12:00:00Z', 'score' => 76],
            ['observed_at_utc' => '2026-11-04T12:00:00Z', 'score' => 74],
            ['observed_at_utc' => '2026-11-05T12:00:00Z', 'score' => 74],
            ['observed_at_utc' => '2026-11-06T12:00:00Z', 'score' => 42],
        ];
    }

    public function test_six_synthetic_score_samples_produce_exact_descriptive_trend(): void
    {
        $inputs = self::fixtures();
        $original = $inputs;
        $preview = (new SyntheticScoreTrendPreview())->preview($inputs);
        self::assertSame(6, $preview['sample_count']);
        self::assertSame(20, $preview['first_score']);
        self::assertSame(42, $preview['last_score']);
        self::assertSame(22, $preview['score_delta']);
        self::assertSame('frio', $preview['first_band']);
        self::assertSame('morno', $preview['last_band']);
        self::assertSame(2, $preview['upward_steps']);
        self::assertSame(2, $preview['downward_steps']);
        self::assertSame(1, $preview['unchanged_steps']);
        self::assertSame(3, $preview['band_changes']);
        self::assertSame(20, $preview['min_score']);
        self::assertSame(76, $preview['max_score']);
        self::assertSame(5, $preview['upward_steps'] + $preview['downward_steps'] + $preview['unchanged_steps']);
        self::assertSame($original, $inputs);
        self::assertSame($preview, (new SyntheticScoreTrendPreview())->preview($inputs));
        self::assertTrue($preview['informational_only']);
        foreach (['band_policy_verified', 'history_verified', 'authorized', 'can_rescore', 'can_notify', 'can_trigger_automation', 'can_persist'] as $flag) {
            self::assertFalse($preview[$flag]);
        }
    }

    public function test_empty_samples_and_custom_thresholds_are_descriptive_only(): void
    {
        $inspect = new SyntheticScoreTrendPreview();
        $empty = $inspect->preview([]);
        self::assertSame(0, $empty['sample_count']);
        foreach (['first_score', 'last_score', 'score_delta', 'first_band', 'last_band', 'min_score', 'max_score'] as $key) {
            self::assertNull($empty[$key]);
        }
        self::assertSame(0, $empty['upward_steps']);
        self::assertFalse($empty['history_verified']);

        $custom = $inspect->preview(self::fixtures(), new LeadScoringBands(10, 40));
        self::assertSame('morno', $custom['first_band']);
        self::assertSame('quente', $custom['last_band']);
        self::assertSame(1, $custom['band_changes']);
        self::assertFalse($custom['band_policy_verified']);
        self::assertFalse($custom['can_trigger_automation']);
    }

    public function test_score_bands_and_evolution_handle_exact_zero_and_hundred(): void
    {
        $out = (new SyntheticScoreTrendPreview())->preview([
            ['observed_at_utc' => '2026-11-01T00:00:00Z', 'score' => 100],
            ['observed_at_utc' => '2026-11-01T00:00:01Z', 'score' => 0],
        ]);
        self::assertSame(-100, $out['score_delta']);
        self::assertSame('quente', $out['first_band']);
        self::assertSame('frio', $out['last_band']);
        self::assertSame(1, $out['downward_steps']);
        self::assertSame(1, $out['band_changes']);
        self::assertSame(0, $out['min_score']);
        self::assertSame(100, $out['max_score']);
    }

    public function test_invalid_dates_nonsequential_samples_or_identity_payload_fail_closed(): void
    {
        $good = ['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => 20];
        $next = ['observed_at_utc' => '2026-11-02T12:00:00Z', 'score' => 30];
        $invalid = [
            null, true, 8, 'score',
            [1 => $good],
            [$next, $good],
            [$good, $good],
            [['observed_at_utc' => '2026-11-01T12:00:00+00:00', 'score' => 20]],
            [['observed_at_utc' => '2026-11-01 12:00:00Z', 'score' => 20]],
            [['observed_at_utc' => '2026-02-30T12:00:00Z', 'score' => 20]],
            [['observed_at_utc' => '2026-11-01T12:00:60Z', 'score' => 20]],
            [['observed_at_utc' => 0, 'score' => 20]],
            [['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => '20']],
            [['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => 20.0]],
            [['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => true]],
            [['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => -1]],
            [['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => 101]],
            [['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => 20, 'email' => 'private@example.test']],
            [['observed_at_utc' => '2026-11-01T12:00:00Z', 'score' => 20, 'tenant' => 'forged']],
            [['score' => 20]],
            array_fill(0, 17, $good),
        ];

        foreach ($invalid as $input) {
            try {
                (new SyntheticScoreTrendPreview())->preview($input);
                self::fail('Unsafe or out-of-sequence score trend accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Evolucao sintetica de pontuacao invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_output_is_aggregated_and_contains_no_record_identifiers(): void
    {
        $out = (new SyntheticScoreTrendPreview())->preview(self::fixtures());
        self::assertSame([
            'sample_count', 'first_score', 'last_score', 'score_delta',
            'first_band', 'last_band', 'upward_steps', 'downward_steps',
            'unchanged_steps', 'band_changes', 'min_score', 'max_score',
            'band_policy_verified', 'history_verified', 'informational_only',
            'authorized', 'can_rescore', 'can_notify', 'can_trigger_automation',
            'can_persist',
        ], array_keys($out));
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['email', 'tenant', 'timestamp', 'observed_at_utc', 'note', 'lead_id', 'score_event_id', 'source', 'phone', 'name'] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
    }
}
