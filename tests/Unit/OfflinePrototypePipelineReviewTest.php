<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Four inert cross-screen links between pipeline fixtures and the lead review.
 */
final class OfflinePrototypePipelineReviewTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_each_pipeline_card_has_one_accessible_button_to_the_same_static_lead(): void
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
        $buttons = $xpath->query('//section[@id="screen-pipeline"]//div[contains(concat(" ", normalize-space(@class), " "), " demo-ticket ")]/button[@data-pipeline-review]');
        self::assertNotFalse($buttons);
        self::assertSame(4, $buttons->length);
        $ids = [];
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertSame('Revisar ficha ilustrativa', trim($button->textContent));
            self::assertStringContainsString('Revisar ficha fictícia de Lead Exemplo', $button->getAttribute('aria-label'));
            $ids[] = $button->getAttribute('data-pipeline-review');
        }

        self::assertSame(['a', 'd', 'b', 'c'], $ids);
        $leads = $xpath->query('//section[@id="screen-leads"]//button[@data-lead-detail]');
        self::assertNotFalse($leads);
        self::assertSame(4, $leads->length);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_pipeline_navigation_is_literal_and_cannot_perform_business_effects(): void
    {
        $html = self::html();
        foreach ([
            "querySelectorAll('#screen-pipeline [data-pipeline-review]')",
            "button.getAttribute('data-lead-detail')===code",
            "if(!destination) return",
            "showLeadStage('all')",
            "navigate('leads',false)",
            "showLeadDetail(code)",
            "destination.focus()",
            "if(page!=='leads') showLeadDetail(null)",
            'nenhuma venda, proposta, contato ou qualificação efetiva foi registrada',
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
        foreach ([
            '<form', '<input', '<textarea', 'contenteditable=',
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage',
            'sessionStorage', 'indexedDB',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }

        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }
}
