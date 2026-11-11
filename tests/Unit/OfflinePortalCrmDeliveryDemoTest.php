<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX F3-50: visual-only states for the Portal→CRM delivery story.
 * No visitor data, service request, receipt or business authority is created.
 */
final class OfflinePortalCrmDeliveryDemoTest extends TestCase
{
    private static function html(): string
    {
        $source=file_get_contents(dirname(__DIR__,2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($source);
        return $source;
    }

    public function test_delivery_states_are_accessible_and_not_public_business_routes(): void
    {
        $html=self::html();
        $doc=new DOMDocument();
        $previous=libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML($html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xp=new DOMXPath($doc);
        self::assertSame(9,$xp->query('//section[@data-screen]')->length);
        self::assertSame(1,$xp->query('//section[@id="screen-leads"]//div[@data-delivery-demo]')->length);
        self::assertSame(1,$xp->query('//section[@id="screen-leads"]//h3[@id="delivery-demo-title"]')->length);
        $buttons=$xp->query('//section[@id="screen-leads"]//button[@data-delivery-case]');
        self::assertSame(4,$buttons->length);
        $ids=[];$pressed=0;
        foreach ($buttons as $button) {
            self::assertInstanceOf(DOMElement::class,$button);
            self::assertSame('button',$button->getAttribute('type'));
            self::assertContains($button->getAttribute('aria-pressed'),['true','false']);
            $ids[]=$button->getAttribute('data-delivery-case');
            if ($button->getAttribute('aria-pressed')==='true')$pressed++;
        }
        self::assertSame(['queued','claimed','unknown','mock-confirmed'],$ids);
        self::assertSame(1,$pressed);
        self::assertSame('true',$buttons->item(0)->getAttribute('aria-pressed'));
        self::assertSame(1,$xp->query(
            '//section[@id="screen-leads"]//div[@id="delivery-demo-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]'
        )->length);
        self::assertSame(0,$xp->query(
            '//section[@id="screen-leads"]//form | //section[@id="screen-leads"]//input | //section[@id="screen-leads"]//textarea'
        )->length);
    }

    public function test_javascript_remains_fixed_text_only_without_network_or_persistence(): void
    {
        $html=self::html();
        foreach ([
            'function showDeliveryDemoCase(code)',
            "showDeliveryDemoCase('queued');",
            "data-delivery-case",
            'deliveryCaseButtons.forEach(function(node)',
            'deliveryDemoPreview.replaceChildren();',
            "panel=document.createElement('div')",
            "addText(panel,'h4',entry.title)",
            'Resultado incerto — reenvio bloqueado',
            'Não reenviar automaticamente.',
            'Uma assinatura de teste não comprova CRM real',
            'Nenhum envio, reenvio, consulta, confirmação ou alteração de estado foi realizado.',
            "connect-src 'none'",
            "form-action 'none'",
        ] as $expected)self::assertStringContainsString($expected,$html);
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket','localStorage',
            'sessionStorage','<form','<input','<textarea','contenteditable='
        ] as $forbidden)self::assertStringNotContainsString($forbidden,$html);
        self::assertLessThan(100000,strlen($html));
    }

    public function test_demo_status_language_does_not_claim_real_crm_delivery(): void
    {
        $html=self::html();
        self::assertStringContainsString(
            'Nenhuma entrega ao CRM foi confirmada; não apresentar sucesso ao visitante.',$html
        );
        self::assertStringContainsString(
            'O receptor pode ter gravado ou não.',$html
        );
        self::assertStringContainsString(
            'NENHUM CONTATO ENVIADO',$html
        );
    }
}
