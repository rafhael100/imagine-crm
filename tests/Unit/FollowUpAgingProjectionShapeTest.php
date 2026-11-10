<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpAgingProjection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpAgingProjectionShapeTest extends TestCase
{
    private const CLOCK = '2026-11-10T10:00:00Z';
    private const DUE = '2026-11-10T11:00:00Z';

    public function test_accepts_required_fields_even_in_reverse_key_order(): void
    {
        self::assertSame([
            'overdue' => [],
            'due_24h' => ['valid'],
            'due_later' => [],
        ], FollowUpAgingProjection::project([
            ['due_at' => self::DUE, 'id' => 'valid'],
        ], self::CLOCK));
    }

    public function test_rejects_unexpected_per_record_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project([[
            'id' => 'synthetic',
            'due_at' => self::DUE,
            'email' => 'must-not-be-accepted@example.invalid',
        ]], self::CLOCK);
    }

    public function test_rejects_associative_outer_collection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project([
            'unexpected-key' => ['id' => 'synthetic', 'due_at' => self::DUE],
        ], self::CLOCK);
    }

    public function test_accepts_exactly_one_hundred_distinct_synthetic_records(): void
    {
        $items = [];
        for ($i = 0; $i < 100; ++$i) {
            $items[] = ['id' => 'synthetic-'.$i, 'due_at' => self::DUE];
        }

        $summary = FollowUpAgingProjection::project($items, self::CLOCK);
        self::assertCount(100, $summary['due_24h']);
        self::assertSame([], $summary['overdue']);
        self::assertSame([], $summary['due_later']);
    }

    public function test_rejects_one_hundred_and_one_records_before_processing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project(array_fill(0, 101, [
            'id' => 'repeated',
            'due_at' => self::DUE,
        ]), self::CLOCK);
    }

    public function test_rejects_non_string_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpAgingProjection::project([
            ['id' => 123, 'due_at' => self::DUE],
        ], self::CLOCK);
    }
}
