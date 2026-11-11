<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class OfflineDemoHubContractTest extends TestCase
{
    private static function html(): string
    {
        $path=dirname(__DIR__,2).'/prototypes/index.html';
        self::assertFileExists($path);
        $source=file_get_contents($path);
        self::assertIsString($source);
        return $source;
    }

    public function test_six_exact_local_demo_targets_exist_and_are_self_contained(): void
    {
        $html=self::html();
        $doc=new DOMDocument();
        $prior=libxml_use_internal_errors(true);
        try{
            self::assertTrue($doc->loadHTML($html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING));
        }finally{
            libxml_clear_errors();libxml_use_internal_errors($prior);
        }
        $xp=new DOMXPath($doc);
        $links=$xp->query('//section[@class="grid"]//article[@class="card"]/a[@href]');
        self::assertSame(6,$links->length);
        $paths=[];
        $root=realpath(dirname(__DIR__,2).'/prototypes');
        self::assertIsString($root);
        foreach($links as $link){
            self::assertInstanceOf(DOMElement::class,$link);
            $href=$link->getAttribute('href');
            self::assertMatchesRegularExpression('~\A[a-z0-9-]+/index\.html\z~D',$href);
            self::assertFalse($link->hasAttribute('target'));
            self::assertFalse($link->hasAttribute('download'));
            $file=realpath($root.'/'.$href);
            self::assertIsString($file);
            self::assertStringStartsWith($root.DIRECTORY_SEPARATOR,$file);
            self::assertFileExists($file);
            $target=file_get_contents($file);
            self::assertIsString($target);
            self::assertStringContainsString("connect-src 'none'",$target);
            self::assertStringContainsString("form-action 'none'",$target);
            $paths[]=$href;
        }
        self::assertSame([
            's01-prot-01/index.html',
            'f3-55-sales-pipeline-lab/index.html',
            'f3-52-delivery-reconciliation/index.html',
            'f4-15-followup-dashboard/index.html',
            'f4-28-followup-worklist/index.html',
            'f3-22-csv-risk-preview/index.html',
        ],$paths);
    }

    public function test_launcher_is_fully_static_without_scripts_or_external_resources(): void
    {
        $html=self::html();
        self::assertLessThan(8000,strlen($html));
        foreach([
            "default-src 'none'","script-src 'none'","connect-src 'none'",
            "form-action 'none'","img-src 'none'","object-src 'none'",
            'PROTÓTIPOS — DADOS FICTÍCIOS.',
            'Sem backend','sem backend',
        ] as $phrase) {
            if(in_array($phrase,['Sem backend','sem backend'],true))continue;
            self::assertStringContainsString($phrase,$html);
        }
        foreach([
            '<script','<iframe','<form','<input','<textarea','<img','<link',
            'XMLHttpRequest','WebSocket','localStorage','sessionStorage','fetch(',
            'http://','https://','contenteditable='
        ] as $forbidden)self::assertStringNotContainsString($forbidden,$html);
        self::assertSame(1,substr_count($html,'<details>'));
        self::assertSame(1,substr_count($html,'<summary>'));
        self::assertStringContainsString('@media(max-width:560px)',$html);
    }
}
