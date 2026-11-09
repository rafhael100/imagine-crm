<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class OfflinePrototypeImportMappingTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        return $html;
    }

    public function test_three_fixed_mapping_states_are_accessible_without_an_input_form(): void
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
        $buttons = $xpath->query('//section[@id="screen-import"]//button[@data-import-mapping]');
        self::assertNotFalse($buttons);
        self::assertSame(3, $buttons->length);
        $values = [];
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertContains($button->getAttribute('aria-pressed'), ['true', 'false']);
            $values[] = $button->getAttribute('data-import-mapping');
        }
        self::assertSame(['complete', 'partial', 'invalid'], $values);

        $status = $xpath->query('//div[@id="import-mapping-preview" and @role="status" and @aria-live="polite"]');
        self::assertNotFalse($status);
        self::assertSame(1, $status->length);
    }

    public function test_all_scenarios_are_literal_and_never_execute_or_authorize(): void
    {
        $html = self::html();
        foreach ([
            'function showImportMappingState(code)',
            "showImportMappingState('complete')",
            'importMappingPreview.replaceChildren()',
            "node.setAttribute('aria-pressed'",
            'CENÁRIO DE IMPORTAÇÃO — DADOS FICTÍCIOS',
            'Nenhuma importação foi autorizada ou realizada.',
            'não executa o PHP F3-21/F3-22',
            'não permite upload',
        ] as $fragment) {
            self::assertStringContainsString($fragment, $html);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage',
            'sessionStorage', '<form', '<input', '<textarea', 'contenteditable=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertSame(9, substr_count($html, ' data-screen="'));
    }
}
