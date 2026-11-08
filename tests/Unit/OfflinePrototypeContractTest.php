<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Static regression checks for a standalone synthetic demo.
 * These assertions are not a browser, accessibility or security audit.
 */
final class OfflinePrototypeContractTest extends TestCase
{
    private static function html(): string
    {
        $path = dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html';
        $html = file_get_contents($path);

        self::assertNotFalse($html);

        return $html;
    }

    public function test_demo_contains_seven_distinct_navigable_sections(): void
    {
        $html = self::html();
        preg_match_all('/<section data-screen="([^"]+)"/', $html, $screens);
        preg_match_all('/<button class="nav-button"[^>]+data-page="([^"]+)"/', $html, $navigation);

        self::assertSame(['welcome', 'organization', 'dashboard', 'leads', 'pipeline', 'states', 'about'], $screens[1]);
        self::assertSame($screens[1], $navigation[1]);
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS', $html);
    }

    public function test_synthetic_business_screens_contain_no_real_contact_fields(): void
    {
        $html = self::html();

        foreach (['Empresa Exemplo', 'Seu vínculo: Ativo', 'Lead Exemplo A', 'Lead Exemplo B', 'Lead Exemplo C', 'Lead Exemplo D'] as $fixture) {
            self::assertStringContainsString($fixture, $html);
        }

        self::assertStringContainsString('Dados de clientes', $html);
        self::assertStringContainsString('00', $html);
        self::assertStringContainsString('apenas para revisão visual', $html);
    }

    public function test_no_network_storage_credentials_forms_or_remote_assets_are_requested(): void
    {
        $html = self::html();

        foreach ([
            '/\bfetch\s*\(/i',
            '/\bXMLHttpRequest\b/i',
            '/\bWebSocket\b/i',
            '/\bEventSource\b/i',
            '/\b(?:localStorage|sessionStorage)\b/i',
            '/<(?:form|input|textarea)\b/i',
            '/<(?:script|link)\b[^>]*(?:src|href)="https?:\/\//i',
            '/\s(?:onclick|onload|onsubmit)\s*=/i',
        ] as $pattern) {
            self::assertDoesNotMatchRegularExpression($pattern, $html);
        }

        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }

    public function test_four_demo_states_are_descriptive_not_real_authorization(): void
    {
        $html = self::html();
        preg_match_all('/data-demo-state="(loading|sample|denied|revalidate)"/', $html, $matches);
        self::assertSame(['loading', 'sample', 'denied', 'revalidate'], $matches[1]);
        self::assertStringContainsString('preview.replaceChildren()', $html);
        self::assertStringContainsString('Nenhum acesso foi concedido ou verificado.', $html);
        self::assertStringContainsString('Não foi possível apresentar estas informações.', $html);
    }
}
