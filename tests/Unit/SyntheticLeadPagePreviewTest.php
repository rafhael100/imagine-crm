<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadPagePreview;
use App\Core\Leads\SyntheticLeadListPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadPagePreviewTest extends TestCase
{
    private const FIXTURES = [
        ['stage' => 'entrada', 'score' => 30],
        ['stage' => 'em-contato', 'score' => 55],
        ['stage' => 'qualificacao', 'score' => 85],
        ['stage' => 'entrada', 'score' => 70],
    ];

    public function test_two_pages_preserve_sorting_and_never_grant_access(): void
    {
        $pager = new SyntheticLeadPagePreview();
        $first = $pager->page(self::FIXTURES, sortMode: 'high', pageSize: 2);
        $second = $pager->page(self::FIXTURES, sortMode: 'high', page: 1, pageSize: 2);

        self::assertSame([2, 3], $first['positions']);
        self::assertSame([1, 0], $second['positions']);
        self::assertSame(2, $first['page_count']);
        self::assertSame(4, $first['matched']);
        self::assertSame(4, $first['total']);
        self::assertFalse($first['has_previous']);
        self::assertTrue($first['has_next']);
        self::assertTrue($second['has_previous']);
        self::assertFalse($second['has_next']);
        self::assertFalse($first['authorized']);
        self::assertFalse($second['authorized']);
        self::assertSame(
            ['positions','total','matched','page','page_size','page_count','has_previous','has_next','empty','authorized'],
            array_keys($first)
        );
    }

    public function test_filtered_pages_produce_correct_number_of_pages(): void
    {
        $pager = new SyntheticLeadPagePreview();
        $first = $pager->page(self::FIXTURES, 'entrada', 'all', 'low', pageSize: 1);
        $second = $pager->page(self::FIXTURES, 'entrada', 'all', 'low', page: 1, pageSize: 1);
        self::assertSame([0], $first['positions']);
        self::assertSame([3], $second['positions']);
        self::assertSame(2, $second['matched']);
        self::assertSame(2, $second['page_count']);
        self::assertFalse($second['has_next']);
    }

    public function test_empty_matches_produce_explicit_empty_page_without_a_cursor(): void
    {
        $result = (new SyntheticLeadPagePreview())->page(self::FIXTURES, 'em-contato', 'quente', pageSize: 2);
        self::assertSame([], $result['positions']);
        self::assertSame(0, $result['matched']);
        self::assertSame(4, $result['total']);
        self::assertSame(0, $result['page_count']);
        self::assertSame(0, $result['page']);
        self::assertTrue($result['empty']);
        self::assertFalse($result['has_next']);
        self::assertFalse($result['has_previous']);
        self::assertFalse($result['authorized']);
    }

    public function test_all_valid_filter_sort_and_page_size_combinations_reassemble_the_preview(): void
    {
        $pager = new SyntheticLeadPagePreview();
        $preview = new SyntheticLeadListPreview();
        foreach (['all', 'entrada', 'em-contato', 'qualificacao'] as $stage) {
            foreach (['all', 'frio', 'morno', 'quente'] as $temperature) {
                foreach (['original', 'high', 'low'] as $sort) {
                    $expected = $preview->preview(self::FIXTURES, $stage, $temperature, $sort);
                    foreach ([1, 2, 3, 4, 20] as $pageSize) {
                        $first = $pager->page(self::FIXTURES, $stage, $temperature, $sort, 0, $pageSize);
                        $actual = [];
                        $numPages = max(1, $first['page_count']);
                        for ($page = 0; $page < $numPages; $page++) {
                            $result = $pager->page(self::FIXTURES, $stage, $temperature, $sort, $page, $pageSize);
                            array_push($actual, ...$result['positions']);
                            self::assertFalse($result['authorized']);
                            self::assertLessThanOrEqual($pageSize, count($result['positions']));
                        }
                        self::assertSame($expected['positions'], $actual);
                        self::assertSame($expected['matched'], $first['matched']);
                    }
                }
            }
        }
    }

    public function test_invalid_pages_filters_sort_or_untrusted_rows_are_rejected(): void
    {
        $pager = new SyntheticLeadPagePreview();
        $invalid = [
            [self::FIXTURES, 'all', 'all', 'original', -1, 2],
            [self::FIXTURES, 'all', 'all', 'original', 20, 2],
            [self::FIXTURES, 'all', 'all', 'original', 1, 4],
            [self::FIXTURES, 'all', 'all', 'original', 0, 0],
            [self::FIXTURES, 'all', 'all', 'original', 0, 21],
            [self::FIXTURES, 'qualificacao', 'frio', 'original', 1, 2],
            [self::FIXTURES, 'all', 'hot', 'original', 0, 2],
            [self::FIXTURES, 'all', 'all', 'reverse', 0, 2],
            [[['stage' => 'entrada', 'score' => 30, 'tenant_id' => 'forged']], 'all', 'all', 'original', 0, 2],
            [[['stage' => 'entrada', 'score' => 101]], 'all', 'all', 'original', 0, 2],
            [array_fill(0, 21, ['stage' => 'entrada', 'score' => 10]), 'all', 'all', 'original', 0, 2],
        ];
        foreach ($invalid as $params) {
            try {
                $pager->page(...$params);
                self::fail('Malformed fixture or page request was accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }
}
