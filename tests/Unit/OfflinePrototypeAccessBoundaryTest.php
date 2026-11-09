<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F3-25: accessible fixed-text review of three *unverified* access states.
 * There is no route, login, permission engine or export capability.
 */
final class OfflinePrototypeAccessBoundaryTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_three_offline_cases_are_accessible_and_remain_within_import_screen(): void
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
        $buttons = $xpath->query('//section[@id="screen-import"]//button[@data-access-scenario]');
        self::assertNotFalse($buttons);
        self::assertSame(3, $buttons->length);

        $cases = [];
        $active = 0;
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertContains($button->getAttribute('aria-pressed'), ['true', 'false']);
            $cases[] = $button->getAttribute('data-access-scenario');
            if ($button->getAttribute('aria-pressed') === 'true') {
                $active++;
            }
        }
        self::assertSame(['read-only', 'cross-tenant', 'revoked'], $cases);
        self::assertSame(1, $active);

        $live = $xpath->query('//div[@id="access-boundary-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]');
        self::assertNotFalse($live);
        self::assertSame(1, $live->length);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_fixture_only_states_do_not_grant_access_or_expose_operations(): void
    {
        $html = self::html();
        foreach ([
            'function showAccessBoundaryCase(code)',
            "showAccessBoundaryCase('read-only')",
            'Visualizar não autoriza exportar',
            'Outra organização — acesso não comprovado',
            'Vínculo alterado — revalidação necessária',
            'Nenhuma exportação foi autorizada ou realizada.',
            'EXEMPLO FICTÍCIO — SEM VERIFICAÇÃO DE ACESSO',
            'accessScenarioPreview.replaceChildren()',
            'data-access-scenario',
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
