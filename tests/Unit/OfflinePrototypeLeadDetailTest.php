<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F3-26: fixture-only read-only lead details. No record-level rights exist.
 */
final class OfflinePrototypeLeadDetailTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_four_buttons_are_tied_to_existing_fixture_rows_and_one_live_panel(): void
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
        $buttons = $xpath->query('//section[@id="screen-leads"]//tbody/tr[@data-lead-fixture-stage]/td/button[@data-lead-detail]');
        self::assertNotFalse($buttons);
        self::assertSame(4, $buttons->length);
        $ids = [];
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertSame('lead-detail-preview', $button->getAttribute('aria-controls'));
            self::assertSame('false', $button->getAttribute('aria-expanded'));
            self::assertStringContainsString('Lead Exemplo', $button->textContent);
            $ids[] = $button->getAttribute('data-lead-detail');
        }
        self::assertSame(['a', 'b', 'c', 'd'], $ids);

        $panel = $xpath->query('//section[@id="screen-leads"]//div[@id="lead-detail-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true" and @hidden]');
        self::assertNotFalse($panel);
        self::assertSame(1, $panel->length);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_detail_selection_is_closed_text_only_and_clears_hidden_or_departed_selection(): void
    {
        $html = self::html();
        foreach ([
            'function showLeadDetail(code)',
            "selectedLeadFixture=selected?code:null",
            "Object.prototype.hasOwnProperty.call(fixtures,code)",
            "button.closest('tr').hidden",
            "if(!active || active.closest('tr').hidden) showLeadDetail(null)",
            "if(page!=='leads') showLeadDetail(null)",
            'leadDetailPreview.replaceChildren()',
            "node.setAttribute('aria-expanded'",
            'Visualização ilustrativa não autoriza exportação, alteração ou ação comercial.',
            'Nenhum contato, histórico pessoal ou permissão foi consultado.',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage',
            'indexedDB', '<form', '<input', '<textarea', 'contenteditable=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }
}
