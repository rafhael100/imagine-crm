<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class OfflineCsvRiskNavigationTest extends TestCase
{
    private static function content(string $name): string
    {
        $path=dirname(__DIR__,2).'/prototypes/'.$name.'/index.html';
        self::assertFileExists($path);
        $html=file_get_contents($path);
        self::assertIsString($html);
        return $html;
    }

    public function test_import_screen_links_to_existing_local_risk_preview_only(): void
    {
        $doc=new DOMDocument();
        $old=libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML(
                self::content('s01-prot-01'),
                LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING
            ));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        $xp=new DOMXPath($doc);
        $links=$xp->query('//section[@id="screen-import"]//p[@data-offline-csv-risk-link]//a[@href="../f3-22-csv-risk-preview/index.html"]');
        self::assertSame(1,$links->length);
        $link=$links->item(0);
        self::assertInstanceOf(DOMElement::class,$link);
        self::assertSame('Abrir análise fictícia de riscos CSV',trim($link->textContent));
        self::assertFalse($link->hasAttribute('target'));
        self::assertFalse($link->hasAttribute('download'));
        self::assertSame(0,$xp->query('//section[@id="screen-import"]//input | //section[@id="screen-import"]//form')->length);
        self::assertSame(9,$xp->query('//section[@data-screen]')->length);
    }

    public function test_csv_page_links_back_to_main_without_upload_or_network(): void
    {
        $main=self::content('s01-prot-01');
        $csv=self::content('f3-22-csv-risk-preview');
        self::assertStringContainsString(
            '<a href="../f3-22-csv-risk-preview/index.html">Abrir análise fictícia de riscos CSV</a>',
            $main
        );
        self::assertStringContainsString(
            'href="../s01-prot-01/index.html"',$csv
        );
        foreach ([$main,$csv] as $html) {
            preg_match_all('/\bhref="([^"]+)"/',$html,$m);
            foreach ($m[1] as $target) {
                self::assertMatchesRegularExpression(
                    '~^(?:#[a-z-]+|\.\./[a-z0-9-]+/index\.html)$~D',
                    $target
                );
            }
            foreach (["connect-src 'none'","form-action 'none'"] as $csp) {
                self::assertStringContainsString($csp,$html);
            }
            foreach (['<form','<input','type="file"','fetch(', 'XMLHttpRequest','WebSocket'] as $forbidden) {
                self::assertStringNotContainsString($forbidden,$html);
            }
        }
        self::assertLessThan(100000,strlen($main));
    }
}
