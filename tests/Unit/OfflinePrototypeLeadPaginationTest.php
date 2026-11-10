<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F3-38: fixed-data pagination controls, not server-side list pagination.
 * Chromium smoke tests in CI exercise changes at 520 and 320 CSS pixels.
 */
final class OfflinePrototypeLeadPaginationTest extends TestCase
{
    public function test_offline_pagination_controls_are_explicit_accessible_and_safe(): void
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
        $xp = new DOMXPath($dom);
        $sizes = $xp->query('//section[@id="screen-leads"]//div[@aria-label="Quantidade de exemplos fictícios por página"]/button[@data-lead-page-size]');
        self::assertNotFalse($sizes);
        self::assertSame(2, $sizes->length);
        self::assertSame(['4', '2'], [
            $sizes->item(0)->getAttribute('data-lead-page-size'),
            $sizes->item(1)->getAttribute('data-lead-page-size'),
        ]);
        self::assertSame('true', $sizes->item(0)->getAttribute('aria-pressed'));
        self::assertSame('false', $sizes->item(1)->getAttribute('aria-pressed'));
        foreach ($sizes as $size) {
            self::assertInstanceOf(DOMElement::class, $size);
            self::assertSame('button', $size->getAttribute('type'));
        }

        foreach (['lead-page-prev', 'lead-page-next'] as $id) {
            $buttons = $xp->query('//section[@id="screen-leads"]//button[@id="'.$id.'"]');
            self::assertSame(1, $buttons->length);
            $button = $buttons->item(0);
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'));
            self::assertTrue($button->hasAttribute('disabled'));
        }

        $summary = $xp->query('//section[@id="screen-leads"]//p[@id="lead-page-summary" and @role="status" and @aria-live="polite" and @aria-atomic="true"]');
        self::assertSame(1, $summary->length);
        self::assertSame(4, $xp->query('//section[@id="screen-leads"]//tr[@data-lead-fixture-score]')->length);
        self::assertSame(9, $xp->query('//section[@data-screen]')->length);
    }

    public function test_ui_paginates_only_previously_validated_fictitious_dom_rows(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'let selectedLeadPageSize = 4;',
            'let selectedLeadPage = 0;',
            'function showLeadPageSize(size)',
            'function moveLeadPage(step)',
            'const filteredRows=Array.from(leadFixtureTableBody.querySelectorAll',
            'row.hidden=index<selectedLeadPage*selectedLeadPageSize',
            'leadPagePrev.disabled=selectedLeadPage===0;',
            'leadPageNext.disabled=pageCount===0',
            'applyLeadFixtureFilters();',
            'if(!active || active.closest(\'tr\').hidden) showLeadDetail(null);',
            'connect-src \'none\'',
            'form-action \'none\'',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }

        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage', '<form', '<input', '<textarea'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
