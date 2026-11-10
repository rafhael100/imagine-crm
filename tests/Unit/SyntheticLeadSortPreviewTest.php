<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadSortPreview;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadSortPreviewTest extends TestCase
{
    private const FIXTURES = [
        ['stage' => 'entrada', 'score' => 30],
        ['stage' => 'em-contato', 'score' => 55],
        ['stage' => 'qualificacao', 'score' => 85],
        ['stage' => 'entrada', 'score' => 70],
    ];

    public function test_the_three_mock_sort_modes_match_the_offline_screen(): void
    {
        $sorter = new SyntheticLeadSortPreview();
        foreach ([
            'original' => [0, 1, 2, 3],
            'high' => [2, 3, 1, 0],
            'low' => [0, 1, 3, 2],
        ] as $mode => $expected) {
            $result = $sorter->order(self::FIXTURES, $mode);
            self::assertSame($expected, $result['positions']);
            self::assertSame(4, $result['total']);
            self::assertFalse($result['authorized']);
            self::assertSame(['positions', 'total', 'authorized'], array_keys($result));
        }
    }

    public function test_equal_scores_use_original_position_as_explicit_tie_breaker(): void
    {
        $fixtures = [
            ['stage' => 'entrada', 'score' => 70],
            ['stage' => 'em-contato', 'score' => 100],
            ['stage' => 'qualificacao', 'score' => 70],
            ['stage' => 'entrada', 'score' => 0],
            ['stage' => 'entrada', 'score' => 100],
        ];
        $sorter = new SyntheticLeadSortPreview();
        self::assertSame([1, 4, 0, 2, 3], $sorter->order($fixtures, 'high')['positions']);
        self::assertSame([3, 0, 2, 1, 4], $sorter->order($fixtures, 'low')['positions']);
        self::assertSame([0, 1, 2, 3, 4], $sorter->order($fixtures)['positions']);
    }

    public function test_empty_fixtures_have_no_positions(): void
    {
        self::assertSame(
            ['positions' => [], 'total' => 0, 'authorized' => false],
            (new SyntheticLeadSortPreview())->order([], 'high')
        );
    }

    public function test_fixture_invalidity_and_unsupported_modes_fail_before_sorting(): void
    {
        $sorter = new SyntheticLeadSortPreview();
        $invalidCases = [
            [self::FIXTURES, 'score_desc'],
            [self::FIXTURES, 'HIGH'],
            [[['stage' => 'entrada', 'score' => 20, 'lead_id' => 'fake']], 'high'],
            [[['stage' => 'entrada', 'score' => '50']], 'low'],
            [[['stage' => 'entrada', 'score' => 101]], 'high'],
            [array_fill(0, 21, ['stage' => 'entrada', 'score' => 70]), 'original'],
            [[7 => ['stage' => 'entrada', 'score' => 70]], 'low'],
        ];
        foreach ($invalidCases as [$fixtures, $mode]) {
            try {
                $sorter->order($fixtures, $mode);
                self::fail('Untrusted fixture or sort accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function test_php_mock_positions_are_grounded_in_the_four_static_html_rows(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//section[@id="screen-leads"]//tbody/tr[@data-lead-fixture-order]');
        self::assertNotFalse($rows);
        self::assertSame(4, $rows->length);
        $fixtures = [];
        foreach ($rows as $index => $row) {
            self::assertInstanceOf(DOMElement::class, $row);
            self::assertSame((string) $index, $row->getAttribute('data-lead-fixture-order'));
            $fixtures[] = [
                'stage' => $row->getAttribute('data-lead-fixture-stage'),
                'score' => (int) $row->getAttribute('data-lead-fixture-score'),
            ];
        }

        self::assertSame(self::FIXTURES, $fixtures);
        $sorter = new SyntheticLeadSortPreview();
        self::assertSame([2, 3, 1, 0], $sorter->order($fixtures, 'high')['positions']);
        self::assertSame([0, 1, 3, 2], $sorter->order($fixtures, 'low')['positions']);
        self::assertStringContainsString("const modes=['original','high','low']", $html);
        self::assertStringContainsString('leadFixtureTableBody.appendChild(node)', $html);
    }
}
