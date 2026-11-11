<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticPipelineSummary;
use App\Core\Leads\LeadScoringBands;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class OfflineSalesPipelineSimulatorTest extends TestCase
{
    private static function html(): string
    {
        $path=dirname(__DIR__,2).'/prototypes/f3-55-sales-pipeline-lab/index.html';
        self::assertFileExists($path);
        $raw=file_get_contents($path);
        self::assertIsString($raw);
        return $raw;
    }

    public function test_four_immutable_fixture_leads_match_core_pipeline_summary(): void
    {
        $s=self::html();
        preg_match_all(
            "/Object\\.freeze\\(\\{id:'([a-d])',name:'Lead Exemplo ([A-D])',score:([0-9]+),stage:([0-2])\\}\\)/",
            $s, $matches, PREG_SET_ORDER
        );
        self::assertCount(4,$matches);
        $toStage=['entrada','em-contato','qualificacao'];
        $toBands=['frio','morno','quente'];
        $rows=[];
        foreach($matches as $m){
            self::assertSame(strtoupper($m[1]),$m[2]);
            $score=(int)$m[3];
            $stage=(int)$m[4];
            self::assertGreaterThanOrEqual(0,$score);
            self::assertLessThanOrEqual(100,$score);
            self::assertContains($stage,[0,1,2]);
            self::assertContains((new LeadScoringBands())->classify($score)->value,$toBands);
            $rows[]=['stage'=>$toStage[$stage],'score'=>$score];
        }
        $result=(new SyntheticPipelineSummary())->summarize($rows);
        self::assertSame(4,$result['total']);
        self::assertSame(['entrada'=>2,'em-contato'=>1,'qualificacao'=>1],$result['stages']);
        self::assertSame(['frio'=>1,'morno'=>1,'quente'=>2],$result['temperatures']);
        self::assertSame(['entrada'=>1,'em-contato'=>0,'qualificacao'=>1],$result['hot_by_stage']);
        self::assertFalse($result['authorized']);
    }

    public function test_buttons_are_keyboard_accessible_and_no_business_submission_possible(): void
    {
        $doc=new DOMDocument();$prior=libxml_use_internal_errors(true);
        try {self::assertTrue($doc->loadHTML(self::html(),LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING));}
        finally{libxml_clear_errors();libxml_use_internal_errors($prior);}
        $xp=new DOMXPath($doc);
        self::assertSame(1,$xp->query('//main[@id="conteudo" and @tabindex="-1"]')->length);
        self::assertSame(3,$xp->query('//section[@class="lane"]')->length);
        self::assertSame(1,$xp->query('//div[@id="pipeline-board"]')->length);
        self::assertSame(4,$xp->query('//button[@data-action]')->length);
        self::assertSame(1,$xp->query('//p[@id="action-status" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length);
        self::assertSame(1,$xp->query('//p[@id="selection-status" and @aria-live="polite"]')->length);
        self::assertSame(1,$xp->query('//a[@href="../s01-prot-01/index.html"]')->length);
        self::assertSame(0,$xp->query('//input|//form|//textarea|//iframe|//a[@href[contains(.,"http")]]')->length);
        foreach($xp->query('//button') as $button)self::assertSame('button',$button->getAttribute('type'));
    }

    public function test_stage_movements_are_reversible_memory_only_and_never_export_data(): void
    {
        $html=self::html();
        foreach ([
            "connect-src 'none'", "form-action 'none'", "default-src 'none'",
            'const FIXTURES=Object.freeze([', 'let records=FIXTURES.map',
            "let selected='a';", 'function move(delta)', 'function revert()', 'function reset()',
            'undo.push(Object.freeze', 'lead.stage=target;', 'lead.stage=last.from;',
            'if(undo.length>20)undo.shift();',
            "if(history.length>40)history.shift();",
            'records=FIXTURES.map', 'if(next)next.focus();',
            'Nenhum lead real foi modificado.',
            'não representam contatos, tarefas, vendas',
        ] as $s)self::assertStringContainsString($s,$html);
        foreach ([
            'fetch(', 'XMLHttpRequest','WebSocket','localStorage','sessionStorage',
            'indexedDB','sendBeacon(', 'innerHTML','outerHTML',
            '<form','<input','<textarea', 'contenteditable=', 'type="file"',
            'window.open(', 'document.cookie',
        ] as $s)self::assertStringNotContainsString($s,$html);
        self::assertLessThan(16000,strlen($html));
    }
}
