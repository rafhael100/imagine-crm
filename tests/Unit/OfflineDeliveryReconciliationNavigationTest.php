<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class OfflineDeliveryReconciliationNavigationTest extends TestCase
{
    private static function html(string $folder): string
    {
        $path=dirname(__DIR__,2).'/prototypes/'.$folder.'/index.html';
        self::assertFileExists($path);
        $contents=file_get_contents($path);
        self::assertIsString($contents);
        return $contents;
    }

    public function test_main_lead_screen_exposes_new_offline_board_only_once(): void
    {
        $doc=new DOMDocument();
        $before=libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML(
                self::html('s01-prot-01'),
                LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING
            ));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($before);
        }
        $xp=new DOMXPath($doc);
        $links=$xp->query(
            '//section[@id="screen-leads"]//p[@data-offline-reconciliation-link]//a[@href="../f3-52-delivery-reconciliation/index.html"]'
        );
        self::assertSame(1,$links->length);
        $anchor=$links->item(0);
        self::assertInstanceOf(DOMElement::class,$anchor);
        self::assertSame('Abrir painel fictício de conferência Portal–CRM',trim($anchor->textContent));
        self::assertFalse($anchor->hasAttribute('target'));
        self::assertFalse($anchor->hasAttribute('download'));
        self::assertSame(9,$xp->query('//section[@data-screen]')->length);
    }

    public function test_reciprocal_paths_exist_and_are_only_local_html_files(): void
    {
        $main=self::html('s01-prot-01');
        $dashboard=self::html('f3-52-delivery-reconciliation');
        self::assertStringContainsString(
            '<a href="../f3-52-delivery-reconciliation/index.html">Abrir painel fictício de conferência Portal–CRM</a>',
            $main
        );
        self::assertStringContainsString(
            '<a class="back" href="../s01-prot-01/index.html">Voltar ao protótipo principal do CRM</a>',
            $dashboard
        );
        foreach ([$main,$dashboard] as $source) {
            preg_match_all('/\bhref="([^"]+)"/',$source,$matches);
            foreach ($matches[1] as $href) {
                self::assertMatchesRegularExpression(
                    '~^(?:#conteudo|\.\./[a-z0-9-]+/index\.html)$~D',
                    $href
                );
                self::assertStringNotContainsString('://',$href);
            }
            self::assertStringContainsString("connect-src 'none'",$source);
            self::assertStringContainsString("form-action 'none'",$source);
            self::assertStringNotContainsString('<form',$source);
        }
        self::assertLessThan(100000,strlen($main));
    }
}
