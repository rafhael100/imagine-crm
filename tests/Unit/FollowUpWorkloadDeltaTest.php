<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpWorkloadDelta;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpWorkloadDeltaTest extends TestCase
{
    public function test_computes_signed_delta_from_valid_summaries(): void
    {
        self::assertSame([
            'overdue' => -2, 'due_24h' => 2, 'due_later' => 1, 'total' => 1,
        ], FollowUpWorkloadDelta::compare(
            ['overdue' => 3, 'due_24h' => 1, 'due_later' => 0, 'total' => 4],
            ['overdue' => 1, 'due_24h' => 3, 'due_later' => 1, 'total' => 5],
        ));
    }

    public function test_equal_empty_snapshots_result_in_zero_delta(): void
    {
        $empty = ['overdue' => 0, 'due_24h' => 0, 'due_later' => 0, 'total' => 0];
        self::assertSame($empty, FollowUpWorkloadDelta::compare($empty, $empty));
    }

    public function test_rejects_inconsistent_total(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpWorkloadDelta::compare(
            ['overdue' => 1, 'due_24h' => 0, 'due_later' => 0, 'total' => 2],
            ['overdue' => 0, 'due_24h' => 0, 'due_later' => 0, 'total' => 0],
        );
    }

    public function test_rejects_negative_count(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpWorkloadDelta::compare(
            ['overdue' => -1, 'due_24h' => 1, 'due_later' => 0, 'total' => 0],
            ['overdue' => 0, 'due_24h' => 0, 'due_later' => 0, 'total' => 0],
        );
    }

    public function test_rejects_unexpected_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpWorkloadDelta::compare(
            ['overdue' => 0, 'due_24h' => 0, 'due_later' => 0, 'total' => 0, 'tenant' => 'example'],
            ['overdue' => 0, 'due_24h' => 0, 'due_later' => 0, 'total' => 0],
        );
    }
}
