<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticPipelineDelta;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticPipelineDeltaTest extends TestCase
{
    public function test_comparison_counts_are_aggregate_only_and_have_no_authority(): void
    {
        $result = (new SyntheticPipelineDelta())->compare(
            [['stage' => 'entrada', 'score' => 30], ['stage' => 'em-contato', 'score' => 55]],
            [['stage' => 'qualificacao', 'score' => 85], ['stage' => 'entrada', 'score' => 95], ['stage' => 'entrada', 'score' => 0]]
        );

        self::assertSame(2, $result['before_total']);
        self::assertSame(3, $result['after_total']);
        self::assertSame(1, $result['total_delta']);
        self::assertSame(['entrada' => 1, 'em-contato' => -1, 'qualificacao' => 1], $result['stage_deltas']);
        self::assertSame(['frio' => 0, 'morno' => -1, 'quente' => 2], $result['temperature_deltas']);
        self::assertFalse($result['authorized']);
        self::assertFalse($result['matched_records']);
        self::assertSame(
            ['before_total', 'after_total', 'total_delta', 'stage_deltas', 'temperature_deltas', 'authorized', 'matched_records'],
            array_keys($result)
        );
    }

    public function test_two_empty_snapshots_produce_only_zero_deltas(): void
    {
        $result = (new SyntheticPipelineDelta())->compare([], []);
        self::assertSame(0, $result['total_delta']);
        self::assertSame([0, 0, 0], array_values($result['stage_deltas']));
        self::assertSame([0, 0, 0], array_values($result['temperature_deltas']));
    }

    public function test_independently_supplied_identical_snapshots_do_not_claim_record_matching(): void
    {
        $rows = [['stage' => 'entrada', 'score' => 99]];
        $result = (new SyntheticPipelineDelta())->compare($rows, $rows);
        self::assertSame(0, $result['total_delta']);
        self::assertFalse($result['matched_records']);
    }

    public function test_untrusted_fields_in_either_snapshot_are_rejected(): void
    {
        foreach ([
            [ [['stage' => 'entrada', 'score' => 5, 'tenant_id' => 'forged']], [] ],
            [ [], [['stage' => 'entrada', 'score' => 5, 'lead_id' => 'forged']] ],
            [ [], [['stage' => 'entrada', 'score' => 101]] ],
            [ array_fill(0, 21, ['stage' => 'entrada', 'score' => 1]), [] ],
        ] as [$before, $after]) {
            try {
                (new SyntheticPipelineDelta())->compare($before, $after);
                self::fail('A malformed synthetic snapshot was accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }
}
