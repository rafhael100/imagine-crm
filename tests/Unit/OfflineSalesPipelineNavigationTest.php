<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/** Restricted local-only link; NOT a lead conversion or a permission grant. */
final class OfflineSalesPipelineNavigationTest extends TestCase
{
    private static function source(string $directory): string
    {
        $path=dirname(__DIR__,2).'/prototypes/'.$directory.'/index.html';
        self::assertFileExists($path);
        $raw=file_get_contents($path);
        self::assertIsString($raw);
        return $raw;
    }

    public function test_main_pipeline_has_exactly_one_local_sandbox_link(): void
    {
        $html=self::source('s01-prot-01');
        $doc=new DOMDocument();
        $old=libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML($html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING));
        }finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        $xp=new DOMXPath($doc);
        $links=$xp->query(
            '//section[@id="screen-pipeline"]//p[@data-offline-pipeline-sandbox-link]//a[@href="../f3-55-sales-pipeline-lab/index.html"]'
        );
        self::assertSame(1,$links->length);
        $link=$links->item(0);
        self::assertInstanceOf(DOMElement::class,$link);
        self::assertSame('Abrir simulador fictício de movimentação do funil',trim($link->textContent));
        self::assertFalse($link->hasAttribute('target'));
        self::assertSame(9,$xp->query('//section[@data-screen]')->length);
        self::assertSame(0,$xp->query('//section[@id="screen-pipeline"]//input | //section[@id="screen-pipeline"]//form')->length);
    }

    public function test_bidirectional_navigation_is_exclusively_file_based(): void
    {
        $main=self::source('s01-prot-01');
        $simulator=self::source('f3-55-sales-pipeline-lab');
        self::assertStringContainsString(
            '<a href="../f3-55-sales-pipeline-lab/index.html">Abrir simulador fictício de movimentação do funil</a>',
            $main
        );
        self::assertStringContainsString(
            '<a href="../s01-prot-01/index.html">Voltar ao protótipo principal do CRM</a>',
            $simulator
        );
        foreach ([$main,$simulator] as $html) {
            preg_match_all('/\bhref="([^"]+)"/',$html,$matches);
            foreach ($matches[1] as $url)self::assertMatchesRegularExpression(
                '~^(?:#conteudo|\.\./[a-z0-9-]+/index\.html)$~D',$url
            );
            self::assertStringContainsString("connect-src 'none'",$html);
            self::assertStringContainsString("form-action 'none'",$html);
            self::assertStringNotContainsString('<form',$html);
        }
        self::assertLessThan(100000,strlen($main));
    }
}
