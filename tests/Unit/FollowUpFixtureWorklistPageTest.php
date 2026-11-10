<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\FollowUpFixtureWorklistPage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FollowUpFixtureWorklistPageTest extends TestCase
{
    private const SCOPE = 'fixture-sales-a';
    private const CLOCK = '2026-11-10T10:00:00Z';

    private static function record(string $id, string $dueAt, string $scope = self::SCOPE): array
    {
        return ['id' => $id, 'due_at' => $dueAt, 'scope' => $scope];
    }

    private static function records(): array
    {
        return [
            self::record('fixture-late', '2026-11-12T12:00:00Z'),
            self::record('fixture-b', '2026-11-10T11:00:00Z'),
            self::record('fixture-overdue', '2026-11-10T09:00:00Z'),
            self::record('fixture-a', '2026-11-10T08:00:00-03:00'),
            self::record('fixture-now', '2026-11-10T10:00:00Z'),
        ];
    }

    public function test_all_bucket_sorts_by_absolute_due_date_and_paginates(): void
    {
        $first = FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK, 'all', 1, 2);
        self::assertSame([
            'bucket' => 'all',
            'page' => 1,
            'page_size' => 2,
            'pages' => 3,
            'selected_total' => 5,
            'has_previous' => false,
            'has_next' => true,
            'counts' => ['overdue' => 1, 'due_24h' => 3, 'due_later' => 1, 'total' => 5],
            'items' => [
                ['id' => 'fixture-overdue', 'due_at' => '2026-11-10T09:00:00.000000Z', 'bucket' => 'overdue'],
                ['id' => 'fixture-now', 'due_at' => '2026-11-10T10:00:00.000000Z', 'bucket' => 'due_24h'],
            ],
        ], $first);

        $second = FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK, 'all', 2, 2);
        self::assertSame(['fixture-a', 'fixture-b'], array_column($second['items'], 'id'));
        self::assertSame('2026-11-10T11:00:00.000000Z', $second['items'][0]['due_at']);
        self::assertTrue($second['has_previous']);
        self::assertTrue($second['has_next']);

        $third = FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK, 'all', 3, 2);
        self::assertSame(['fixture-late'], array_column($third['items'], 'id'));
        self::assertTrue($third['has_previous']);
        self::assertFalse($third['has_next']);
    }

    public function test_filtering_a_bucket_does_not_hide_full_snapshot_counts(): void
    {
        $first = FollowUpFixtureWorklistPage::paginate(
            self::SCOPE, self::records(), self::CLOCK, 'due_24h', 1, 2,
        );
        $last = FollowUpFixtureWorklistPage::paginate(
            self::SCOPE, self::records(), self::CLOCK, 'due_24h', 2, 2,
        );

        self::assertSame(['fixture-now', 'fixture-a'], array_column($first['items'], 'id'));
        self::assertSame(['fixture-b'], array_column($last['items'], 'id'));
        self::assertSame(3, $first['selected_total']);
        self::assertSame(2, $first['pages']);
        self::assertSame(5, $first['counts']['total']);
        self::assertSame(1, $first['counts']['overdue']);
        self::assertSame(1, $first['counts']['due_later']);
    }

    public function test_permuting_input_does_not_change_worklist_order(): void
    {
        $normal = FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK);
        $reversed = FollowUpFixtureWorklistPage::paginate(
            self::SCOPE, array_reverse(self::records()), self::CLOCK,
        );
        self::assertSame($normal, $reversed);
    }

    public function test_equivalent_clock_offsets_give_identical_result(): void
    {
        self::assertSame(
            FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK),
            FollowUpFixtureWorklistPage::paginate(
                self::SCOPE, self::records(), '2026-11-10T07:00:00-03:00',
            ),
        );
    }

    public function test_microsecond_order_and_exact_24_hour_cutoff_are_preserved(): void
    {
        $items = [
            self::record('fixture-after', '2026-11-11T10:00:00.000001Z'),
            self::record('fixture-before', '2026-11-10T09:59:59.999999Z'),
            self::record('fixture-cutoff', '2026-11-11T10:00:00.000000Z'),
            self::record('fixture-at', '2026-11-10T10:00:00.000000Z'),
        ];
        $result = FollowUpFixtureWorklistPage::paginate(self::SCOPE, $items, self::CLOCK);
        self::assertSame(
            ['fixture-before', 'fixture-at', 'fixture-cutoff', 'fixture-after'],
            array_column($result['items'], 'id'),
        );
        self::assertSame(
            ['overdue', 'due_24h', 'due_24h', 'due_later'],
            array_column($result['items'], 'bucket'),
        );
    }

    public function test_empty_fixtures_have_one_empty_page_not_a_false_completion_state(): void
    {
        $result = FollowUpFixtureWorklistPage::paginate(self::SCOPE, [], self::CLOCK);
        self::assertSame([], $result['items']);
        self::assertSame(0, $result['selected_total']);
        self::assertSame(1, $result['pages']);
        self::assertSame(0, $result['counts']['total']);
        self::assertFalse($result['has_previous']);
        self::assertFalse($result['has_next']);
    }

    public function test_rejects_nonexistent_page_even_if_no_records_match(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, [], self::CLOCK, 'all', 2);
    }

    public function test_rejects_zero_page(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK, 'all', 0);
    }

    public function test_rejects_more_than_25_items_per_page(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK, 'all', 1, 26);
    }

    public function test_rejects_zero_page_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK, 'all', 1, 0);
    }

    public function test_rejects_unknown_bucket(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK, 'completed');
    }

    public function test_rejects_cross_company_fixture_with_distinct_local_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $items = self::records();
        $items[] = self::record('fixture-other-company', '2026-11-12T08:00:00Z', 'fixture-sales-b');
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, $items, self::CLOCK);
    }

    public function test_rejects_unknown_record_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $items = self::records();
        $items[0]['tenant_id'] = 'user-forged';
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, $items, self::CLOCK);
    }

    public function test_rejects_missing_explicit_timezone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), '2026-11-10T10:00:00');
    }

    public function test_rejects_repeated_record_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $items = self::records();
        $items[] = self::record('fixture-a', '2026-11-12T10:00:00Z');
        FollowUpFixtureWorklistPage::paginate(self::SCOPE, $items, self::CLOCK);
    }

    public function test_rejects_more_than_100_fixture_records(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FollowUpFixtureWorklistPage::paginate(
            self::SCOPE,
            array_fill(0, 101, self::record('fixture-repeated', self::CLOCK)),
            self::CLOCK,
        );
    }

    public function test_no_fake_scope_is_revealed_in_the_paged_result(): void
    {
        $result = FollowUpFixtureWorklistPage::paginate(self::SCOPE, self::records(), self::CLOCK);
        self::assertSame(
            ['bucket', 'page', 'page_size', 'pages', 'selected_total', 'has_previous', 'has_next', 'counts', 'items'],
            array_keys($result),
        );

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['fixture-sales-a', 'tenant_id', 'membership', 'principal', 'authorized', 'ALLOW'] as $field) {
            self::assertStringNotContainsString($field, $json);
        }
    }

    public function test_exact_100_items_with_25_per_page_can_be_paginated(): void
    {
        $items = [];
        for ($i = 0; $i < 100; ++$i) {
            $items[] = self::record(sprintf('fixture-%03d', $i), '2026-11-10T11:00:00Z');
        }
        $result = FollowUpFixtureWorklistPage::paginate(self::SCOPE, $items, self::CLOCK, 'due_24h', 4, 25);
        self::assertSame(100, $result['selected_total']);
        self::assertSame(4, $result['pages']);
        self::assertCount(25, $result['items']);
        self::assertSame('fixture-075', $result['items'][0]['id']);
        self::assertSame('fixture-099', $result['items'][24]['id']);
        self::assertFalse($result['has_next']);
    }
}
