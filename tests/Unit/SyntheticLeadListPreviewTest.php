<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadListPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadListPreviewTest extends TestCase
{
    private const FIXTURES = [
        ['stage' => 'entrada', 'score' => 30],
        ['stage' => 'em-contato', 'score' => 55],
        ['stage' => 'qualificacao', 'score' => 85],
        ['stage' => 'entrada', 'score' => 70],
    ];

    public function test_filtered_sorted_mock_positions_are_stable_and_never_authorized(): void
    {
        $preview = new SyntheticLeadListPreview();

        foreach ([
            ['all', 'all', 'original', [0, 1, 2, 3]],
            ['all', 'all', 'high', [2, 3, 1, 0]],
            ['all', 'all', 'low', [0, 1, 3, 2]],
            ['entrada', 'all', 'high', [3, 0]],
            ['entrada', 'quente', 'low', [3]],
            ['em-contato', 'morno', 'high', [1]],
            ['qualificacao', 'frio', 'original', []],
        ] as [$stage, $temperature, $mode, $positions]) {
            $result = $preview->preview(self::FIXTURES, $stage, $temperature, $mode);
            self::assertSame($positions, $result['positions']);
            self::assertSame(count($positions), $result['matched']);
            self::assertSame(4, $result['total']);
            self::assertSame($positions === [], $result['empty']);
            self::assertFalse($result['authorized']);
            self::assertSame(['positions', 'matched', 'total', 'empty', 'authorized'], array_keys($result));
        }
    }

    public function test_all_forty_eight_filter_and_sort_combinations_have_consistent_counts(): void
    {
        $preview = new SyntheticLeadListPreview();
        foreach (['all', 'entrada', 'em-contato', 'qualificacao'] as $stage) {
            foreach (['all', 'frio', 'morno', 'quente'] as $temperature) {
                foreach (['original', 'high', 'low'] as $sort) {
                    $result = $preview->preview(self::FIXTURES, $stage, $temperature, $sort);
                    self::assertCount($result['matched'], $result['positions']);
                    self::assertSame($result['matched'] === 0, $result['empty']);
                    self::assertFalse($result['authorized']);
                    self::assertSame(count(array_unique($result['positions'])), count($result['positions']));
                    foreach ($result['positions'] as $position) {
                        self::assertGreaterThanOrEqual(0, $position);
                        self::assertLessThan(4, $position);
                    }
                }
            }
        }
    }

    public function test_empty_fixture_and_equal_scores_are_handled_deterministically(): void
    {
        $preview = new SyntheticLeadListPreview();
        self::assertSame(
            ['positions' => [], 'matched' => 0, 'total' => 0, 'empty' => true, 'authorized' => false],
            $preview->preview([], 'all', 'all', 'high')
        );

        $sameScore = [
            ['stage' => 'entrada', 'score' => 70],
            ['stage' => 'qualificacao', 'score' => 70],
            ['stage' => 'entrada', 'score' => 40],
        ];
        self::assertSame([0, 2], $preview->preview($sameScore, 'entrada', 'all', 'high')['positions']);
        self::assertSame([2, 0], $preview->preview($sameScore, 'entrada', 'all', 'low')['positions']);
    }

    public function test_untrusted_inputs_fail_closed_even_when_filter_would_hide_them(): void
    {
        $preview = new SyntheticLeadListPreview();
        $invalid = [
            [self::FIXTURES, 'all', 'all', 'reverse'],
            [self::FIXTURES, 'unknown', 'all', 'original'],
            [self::FIXTURES, 'all', 'hot', 'original'],
            [[['stage' => 'em-contato', 'score' => 55, 'tenant_id' => 'spoof']], 'entrada', 'quente', 'low'],
            [[['stage' => 'em-contato', 'score' => 105]], 'entrada', 'quente', 'low'],
            [array_fill(0, 21, ['stage' => 'entrada', 'score' => 10]), 'all', 'all', 'original'],
        ];
        foreach ($invalid as [$fixtures, $stage, $temperature, $mode]) {
            try {
                $preview->preview($fixtures, $stage, $temperature, $mode);
                self::fail('Invalid synthetic request unexpectedly accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }
}
