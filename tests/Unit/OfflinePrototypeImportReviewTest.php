<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/** Regression checks for a fixture-only, offline finding display. */
final class OfflinePrototypeImportReviewTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        return $html;
    }

    public function test_review_controls_are_accessible_and_bounded_to_three_cases(): void
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
        $buttons = $xpath->query('//section[@id="screen-import"]//button[@data-import-review]');
        self::assertNotFalse($buttons);
        self::assertSame(3, $buttons->length);
        $modes = [];
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertContains($button->getAttribute('aria-pressed'), ['true', 'false']);
            $modes[] = $button->getAttribute('data-import-review');
        }
        self::assertSame(['clear', 'formula', 'empty'], $modes);

        $live = $xpath->query('//div[@id="import-review-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]');
        self::assertNotFalse($live);
        self::assertSame(1, $live->length);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_no_findings_scenario_explicitly_denies_authority_and_has_no_network(): void
    {
        $html = self::html();
        foreach ([
            'function showImportReviewCase(code)',
            "showImportReviewCase('clear')",
            'A ausência de alertas não comprova arquivo válido',
            '1 célula com possível fórmula e 1 célula vazia.',
            '0 linhas de dados; 1 aviso: EMPTY_DATASET.',
            'Nenhuma importação foi autorizada ou realizada.',
            'nenhuma análise PHP é executada nesta tela.',
            'importReviewPreview.replaceChildren()',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage',
            'sessionStorage', '<input', '<textarea', '<form', 'contenteditable=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }
}
