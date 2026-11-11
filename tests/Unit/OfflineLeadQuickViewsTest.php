<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * F3-51: preset controls only compose existing immutable demo DOM filters.
 * No customer data, saved filters, business operations, external API or ACL.
 */
final class OfflineLeadQuickViewsTest extends TestCase
{
    private static function html(): string
    {
        $value=file_get_contents(dirname(__DIR__,2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($value);
        return $value;
    }

    public function test_four_quick_views_and_status_are_accessible_and_contained_to_leads_screen(): void
    {
        $doc=new DOMDocument();
        $prior=libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML(self::html(),LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prior);
        }
        $xpath=new DOMXPath($doc);
        $controls=$xpath->query('//section[@id="screen-leads"]//div[@role="group" and @aria-label="Visões rápidas fictícias de leads"]/button[@data-lead-quick-view]');
        self::assertSame(4,$controls->length);
        $ids=[];$pressed=[];
        foreach ($controls as $b) {
            self::assertInstanceOf(DOMElement::class,$b);
            self::assertSame('button',$b->getAttribute('type'));
            $ids[]=$b->getAttribute('data-lead-quick-view');
            self::assertContains($b->getAttribute('aria-pressed'),['true','false']);
            if($b->getAttribute('aria-pressed')==='true')$pressed[]=$b->getAttribute('data-lead-quick-view');
        }
        self::assertSame(['all','hot','contact','qualification'],$ids);
        self::assertSame(['all'],$pressed);
        self::assertSame(1,$xpath->query('//section[@id="screen-leads"]//p[@id="lead-quick-view-status" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length);
        self::assertSame(9,$xpath->query('//section[@data-screen]')->length);
        self::assertSame(4,$xpath->query('//section[@id="screen-leads"]//tr[@data-lead-fixture-stage]')->length);
        self::assertSame(0,$xpath->query('//section[@id="screen-leads"]//input|//section[@id="screen-leads"]//form')->length);
    }

    public function test_quick_views_use_preexisting_filter_sort_and_page_functions_without_new_io(): void
    {
        $html=self::html();
        foreach ([
            'function showLeadQuickView(code)',
            'function updateLeadQuickViewIndicator()',
            "showLeadStage(chosen[0]);",
            "showLeadTemperature(chosen[1]);",
            "sortLeadFixtures(chosen[2]);",
            "selectedLeadSort=mode;",
            "updateLeadQuickViewIndicator();",
            "leadQuickViewStatus.textContent=active===null",
            'Filtros personalizados: nenhuma visão rápida selecionada.',
            'leadQuickViewButtons.forEach(function(button)',
            "hot:Object.freeze(['all','hot','high'])",
            "contact:Object.freeze(['em-contato','all','original'])",
            "qualification:Object.freeze(['qualificacao','all','original'])",
            "connect-src 'none'","form-action 'none'",
        ] as $required)self::assertStringContainsString($required,$html);
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket',
            'localStorage','sessionStorage','<form','<input','<textarea',
            'contenteditable='
        ] as $bad)self::assertStringNotContainsString($bad,$html);
        self::assertLessThan(100000,strlen($html));
    }

    public function test_preset_is_pure_ui_not_approval_or_pipeline_transition(): void
    {
        $html=self::html();
        self::assertStringContainsString(
            'Visão fictícia de leads quentes, ordenados por maior pontuação.',
            $html
        );
        self::assertStringContainsString(
            'Filtros personalizados: nenhuma visão rápida selecionada.',$html
        );
        self::assertStringContainsString(
            'Tabela fictícia com filtros visuais combinados',$html
        );
        self::assertStringNotContainsString('<form',$html);
        self::assertStringNotContainsString('window.location.href',$html);
    }
}
