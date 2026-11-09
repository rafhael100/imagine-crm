<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Fixed, offline UI fixtures only. No real quotas, tenants or entitlements.
 */
final class OfflinePrototypeQuotaDashboardTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        return $html;
    }

    public function test_dashboard_contains_three_accessible_static_quota_meters(): void
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
        $sections = $xpath->query('//section[@id="screen-dashboard"]//section[@data-quota-demo]');
        self::assertNotFalse($sections);
        self::assertSame(1, $sections->length);
        self::assertSame('quota-demo-title', $sections->item(0)->getAttribute('aria-labelledby'));

        $meters = $xpath->query('//section[@id="screen-dashboard"]//div[@data-quota-fixture]/meter');
        self::assertNotFalse($meters);
        self::assertSame(3, $meters->length);

        $actual = [];
        foreach ($meters as $meter) {
            self::assertSame('0', $meter->getAttribute('min'));
            self::assertNotSame('', $meter->getAttribute('aria-label'));
            $actual[] = [
                $meter->parentNode->getAttribute('data-quota-fixture'),
                $meter->getAttribute('value'),
                $meter->getAttribute('max'),
            ];
        }

        self::assertSame([
            ['leads', '4', '20'],
            ['users', '1', '2'],
            ['automations', '0', '2'],
        ], $actual);
    }

    public function test_dashboard_labels_are_fictional_and_do_not_claim_real_activation(): void
    {
        $html = self::html();
        foreach ([
            'Franquias demonstrativas',
            'Todos os números a seguir são fictícios',
            'não representam recursos liberados',
            'não consultam o componente PHP ENT-04',
            'Valor exibido não concede permissão',
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
        self::assertSame(3, substr_count($html, 'class="quota-demo-meter"'));
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach (['<input', '<form', 'localStorage', 'sessionStorage', 'XMLHttpRequest', 'fetch('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
