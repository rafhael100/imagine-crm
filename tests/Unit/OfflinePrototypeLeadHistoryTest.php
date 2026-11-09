<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Offline UX fixture only. Timeline claims must never be portrayed as evidence.
 */
final class OfflinePrototypeLeadHistoryTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_history_stays_inside_one_hidden_lead_detail_panel(): void
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($dom->loadHTML(self::html(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($dom);
        $panels = $xpath->query('//section[@id="screen-leads"]//div[@id="lead-detail-preview" and @hidden and @role="status" and @aria-live="polite"]');
        self::assertNotFalse($panels);
        self::assertSame(1, $panels->length);
        self::assertSame(4, $xpath->query('//section[@id="screen-leads"]//button[@data-lead-detail]')->length);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_all_four_histories_are_fixed_only_and_cannot_claim_authority(): void
    {
        $html = self::html();
        foreach ([
            "events:['Criação ilustrativa do exemplo', 'Classificação ilustrativa da etapa Entrada']",
            "events:['Criação ilustrativa do exemplo', 'Atribuição ilustrativa', 'Classificação ilustrativa da etapa Em contato']",
            "events:['Criação ilustrativa do exemplo', 'Classificação ilustrativa da etapa Qualificação']",
            "events:['Criação ilustrativa do exemplo', 'Revisão ilustrativa da etapa Entrada']",
            'Sequência de eventos fictícios — não verificada',
            "document.createElement('ol')",
            "addText(eventList,'li',eventLabel)",
            'A ordem, os autores e os eventos desta amostra não foram comprovados nem persistidos.',
            'Visualização ilustrativa não autoriza exportação, alteração ou ação comercial.',
            "leadDetailPreview.replaceChildren()",
            "if(page!=='leads') showLeadDetail(null)",
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
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
