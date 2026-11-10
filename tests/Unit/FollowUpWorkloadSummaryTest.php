<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpWorkloadSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpWorkloadSummaryTest extends TestCase
{
    public function test_counts_synthetic_groups_without_side_effects(): void
    {
        self::assertSame([
            'overdue' => 2,
            'due_24h' => 1,
            'due_later' => 0,
            'total' => 3,
        ], FollowUpWorkloadSummary::summarize([
            'overdue' => ['a', 'b'],
            'due_24h' => ['c'],
            'due_later' => [],
        ]));
    }

    public function test_empty_groups_return_zeros(): void
    {
        self::assertSame([
            'overdue' => 0, 'due_24h' => 0, 'due_later' => 0, 'total' => 0,
        ], FollowUpWorkloadSummary::summarize([
            'overdue' => [], 'due_24h' => [], 'due_later' => [],
        ]));
    }

    public function test_rejects_repeated_ids_across_groups(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpWorkloadSummary::summarize([
            'overdue' => ['a'], 'due_24h' => ['a'], 'due_later' => [],
        ]);
    }

    public function test_rejects_extra_group(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpWorkloadSummary::summarize([
            'overdue' => [], 'due_24h' => [], 'due_later' => [], 'unexpected' => [],
        ]);
    }

    public function test_rejects_non_list_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpWorkloadSummary::summarize([
            'overdue' => ['first' => 'a'], 'due_24h' => [], 'due_later' => [],
        ]);
    }
}
