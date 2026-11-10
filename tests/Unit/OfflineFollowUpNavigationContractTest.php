<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * These checks validate only the static, local-only navigation contract;
 * no browser or authorization semantics are claimed.
 */
final class OfflineFollowUpNavigationContractTest extends TestCase
{
    private static function html(string $relative): string
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/prototypes/'.$relative.'/index.html');
        self::assertIsString($content);

        return $content;
    }

    public function test_main_prototype_links_to_local_followup_dashboard(): void
    {
        $html = self::html('s01-prot-01');
        self::assertStringContainsString('data-offline-followup-link', $html);
        self::assertStringContainsString(
            '<a href="../f4-15-followup-dashboard/index.html">Abrir painel demonstrativo de acompanhamentos</a>',
            $html
        );
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/f4-15-followup-dashboard/index.html');
    }

    public function test_followup_dashboard_offers_local_back_navigation(): void
    {
        $html = self::html('f4-15-followup-dashboard');
        self::assertStringContainsString(
            '<a href="../s01-prot-01/index.html">Voltar ao protótipo principal do CRM</a>',
            $html
        );
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
    }

    public function test_both_pages_only_contain_local_navigation_links(): void
    {
        foreach (['s01-prot-01', 'f4-15-followup-dashboard'] as $page) {
            $html = self::html($page);
            preg_match_all('/\\bhref="([^"]+)"/', $html, $matches);
            self::assertCount($page === 'f4-15-followup-dashboard' ? 3 : 2, $matches[1]);
            foreach ($matches[1] as $href) {
                self::assertMatchesRegularExpression(
                    '~^(?:#[a-z-]+|\\.\\./[a-z0-9-]+/index\\.html)$~D',
                    $href
                );
            }
            self::assertStringContainsString("connect-src 'none'", $html);
            self::assertStringContainsString("form-action 'none'", $html);
        }
    }
}
