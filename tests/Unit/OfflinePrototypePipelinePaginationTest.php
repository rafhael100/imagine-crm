<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F3-39: pipeline-to-lead demo navigation must resolve the visible page.
 * Chromium smoke tests assert keyboard focus and selected fixture in two viewports.
 */
final class OfflinePrototypePipelinePaginationTest extends TestCase
{
    public function test_every_fixed_pipeline_target_has_a_matching_local_lead_detail(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        $doc = new DOMDocument();
        $old = libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        $xp = new DOMXPath($doc);
        $pipeline = $xp->query('//section[@id="screen-pipeline"]//button[@data-pipeline-review]');
        $details = $xp->query('//section[@id="screen-leads"]//button[@data-lead-detail]');
        self::assertSame(4, $pipeline->length);
        self::assertSame(4, $details->length);
        $codes = [];
        foreach ($details as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            $codes[] = $button->getAttribute('data-lead-detail');
        }
        self::assertSame(['a', 'b', 'c', 'd'], $codes);
        foreach ($pipeline as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertContains($button->getAttribute('data-pipeline-review'), $codes);
        }
    }

    public function test_pipeline_navigation_resolves_local_page_and_never_queries_backend(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'const targetRow=destination.closest(\'tr\');',
            'const orderedRows=Array.from(leadFixtureTableBody.querySelectorAll(\'tr\'));',
            'const rowIndex=orderedRows.indexOf(targetRow);',
            'selectedLeadPage=Math.floor(rowIndex/selectedLeadPageSize);',
            'applyLeadFixtureFilters();',
            'if(targetRow.hidden) return;',
            'showLeadDetail(code);',
            'destination.focus();',
            "connect-src 'none'",
            "form-action 'none'",
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage', '<form', '<input'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
