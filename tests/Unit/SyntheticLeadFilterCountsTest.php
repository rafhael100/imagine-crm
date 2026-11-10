<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadFilterCounts;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadFilterCountsTest extends TestCase
{
    private const FIXTURES = [
        ['stage' => 'entrada', 'score' => 30],
        ['stage' => 'em-contato', 'score' => 55],
        ['stage' => 'qualificacao', 'score' => 85],
        ['stage' => 'entrada', 'score' => 70],
    ];

    public function test_all_stage_and_temperature_combinations_remain_denied_and_match_expected_counts(): void
    {
        $counter = new SyntheticLeadFilterCounts();
        $expected = [
            'all' => ['all' => 4, 'frio' => 1, 'morno' => 1, 'quente' => 2],
            'entrada' => ['all' => 2, 'frio' => 1, 'morno' => 0, 'quente' => 1],
            'em-contato' => ['all' => 1, 'frio' => 0, 'morno' => 1, 'quente' => 0],
            'qualificacao' => ['all' => 1, 'frio' => 0, 'morno' => 0, 'quente' => 1],
        ];

        foreach ($expected as $stage => $temperatures) {
            foreach ($temperatures as $temperature => $count) {
                $result = $counter->count(self::FIXTURES, $stage, $temperature);
                self::assertSame($count, $result['matched']);
                self::assertSame(4, $result['total']);
                self::assertSame($count === 0, $result['empty']);
                self::assertFalse($result['authorized']);
                self::assertSame(['matched', 'total', 'empty', 'authorized'], array_keys($result));
            }
        }
    }

    public function test_empty_fixture_remains_empty_for_every_valid_filter(): void
    {
        $counter = new SyntheticLeadFilterCounts();
        self::assertSame(['matched' => 0, 'total' => 0, 'empty' => true, 'authorized' => false],
            $counter->count([]));
        self::assertSame(0, $counter->count([], 'entrada', 'quente')['matched']);
    }

    public function test_invalid_or_untrusted_filter_is_rejected(): void
    {
        $counter = new SyntheticLeadFilterCounts();
        foreach ([['Entrada', 'all'], ['all', 'hot'], ['*', 'all'], ['all', ''], ['qualificacao', 'QUENTE']] as [$stage, $temperature]) {
            try {
                $counter->count(self::FIXTURES, $stage, $temperature);
                self::fail('Unexpected accepted filter.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function test_unmatched_rows_cannot_hide_untrusted_fields_or_oversized_input(): void
    {
        $counter = new SyntheticLeadFilterCounts();
        foreach ([
            [['stage' => 'em-contato', 'score' => 55, 'tenant_id' => 'forged']],
            [['stage' => 'em-contato', 'score' => 102]],
            array_fill(0, 21, ['stage' => 'entrada', 'score' => 30]),
        ] as $invalid) {
            try {
                $counter->count($invalid, 'qualificacao', 'quente');
                self::fail('Unexpected accepted fixture.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }
}
