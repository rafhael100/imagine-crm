<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Static checks for fixture-only ordering; Chromium validates actual DOM order.
 */
final class OfflinePrototypeLeadSortTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_sort_controls_and_fixture_scores_are_bounded_and_accessible(): void
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML(self::html(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);

        $buttons = $xpath->query('//section[@id="screen-leads"]//button[@data-lead-sort]');
        self::assertNotFalse($buttons);
        self::assertSame(3, $buttons->length);
        $sortModes = [];
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertContains($button->getAttribute('aria-pressed'), ['true', 'false']);
            $sortModes[] = $button->getAttribute('data-lead-sort');
        }
        self::assertSame(['original', 'high', 'low'], $sortModes);

        $rows = $xpath->query('//section[@id="screen-leads"]//tbody/tr[@data-lead-fixture-stage]');
        self::assertNotFalse($rows);
        self::assertSame(4, $rows->length);
        $scores = [];
        $positions = [];
        foreach ($rows as $row) {
            $scores[] = (int) $row->getAttribute('data-lead-fixture-score');
            $positions[] = (int) $row->getAttribute('data-lead-fixture-order');
            self::assertStringContainsString('Lead Exemplo', $row->textContent);
        }
        self::assertSame([30, 55, 85, 70], $scores);
        self::assertSame([0, 1, 2, 3], $positions);

        $liveSummary = $xpath->query('//p[@id="lead-sort-summary" and @role="status" and @aria-live="polite"]');
        self::assertNotFalse($liveSummary);
        self::assertSame(1, $liveSummary->length);
    }

    public function test_sorting_is_local_only_and_does_not_replace_existing_stage_filter(): void
    {
        $html = self::html();
        foreach ([
            'function showLeadStage(stage)',
            'function sortLeadFixtures(mode)',
            "const modes=['original','high','low']",
            "leadFixtureRows.slice().sort(",
            "leadFixtureTableBody.appendChild(node)",
            "sortLeadFixtures('original')",
            'leadSortSummary.textContent=description[mode]',
            'data-lead-stage="qualificacao"',
            "leadFilterSummary.textContent='Mostrando '",
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'localStorage', 'sessionStorage', '<form', '<input', 'contenteditable='] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }
}
