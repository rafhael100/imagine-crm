<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class OfflineDeliveryReconciliationDashboardTest extends TestCase
{
    private static function source(): string
    {
        $html=file_get_contents(dirname(__DIR__,2).'/prototypes/f3-52-delivery-reconciliation/index.html');
        self::assertIsString($html);
        return $html;
    }

    public function test_six_cases_and_controls_are_accessible_in_standalone_page(): void
    {
        $doc=new DOMDocument();
        $previous=libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML(self::source(),LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xp=new DOMXPath($doc);
        self::assertSame(1,$xp->query('//main[@id="conteudo" and @tabindex="-1"]')->length);
        self::assertSame(1,$xp->query('//a[@class="skip" and @href="#conteudo"]')->length);
        self::assertSame(6,$xp->query('//tbody[@id="case-rows"]/tr[@data-case]')->length);
        self::assertSame(6,$xp->query('//tbody[@id="case-rows"]//button[@data-detail and @aria-controls="case-detail" and @aria-expanded="false"]')->length);
        self::assertSame(4,$xp->query('//div[@id="filter-controls" and @role="group"]/button[@data-filter]')->length);
        self::assertSame(2,$xp->query('//div[@id="sort-controls" and @role="group"]/button[@data-sort]')->length);
        self::assertSame(1,$xp->query('//p[@id="list-status" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length);
        self::assertSame(1,$xp->query('//div[@id="case-detail-content" and @role="status" and @aria-live="polite"]')->length);
        self::assertSame(1,$xp->query('//a[@href="../s01-prot-01/index.html"]')->length);
        self::assertSame(0,$xp->query('//form|//input|//textarea|//iframe')->length);
    }

    public function test_fixtures_have_exact_status_distribution_and_no_contact_fields(): void
    {
        $html=self::source();
        foreach([
            'data-group="review"',
            'data-group="waiting"',
            'data-group="lab"',
            'Resultado desconhecido',
            'Conflito de vínculo',
            'Recibo sintético',
            'Reenvio bloqueado',
            'Somente CI',
            'Não reenviar automaticamente',
            'Não declarar lead entregue a um CRM real',
            'Não há botões de reenviar, confirmar, descartar ou liberar acesso.',
            'PROTÓTIPO — DADOS FICTÍCIOS',
        ] as $required)self::assertStringContainsString($required,$html);

        self::assertSame(2,substr_count($html,'data-group="review"'));
        self::assertSame(3,substr_count($html,'data-group="waiting"'));
        self::assertSame(1,substr_count($html,'data-group="lab"'));
    }

    public function test_immutable_offline_behavior_does_not_introduce_storage_or_io(): void
    {
        $html=self::source();
        self::assertLessThan(28000,strlen($html));
        foreach([
            "connect-src 'none'","form-action 'none'",
            "object-src 'none'","default-src 'none'",
            'Object.freeze({',
            'detailContent.replaceChildren();',
            'node.textContent=value;',
            "activeFilter='all'",
            "activeSort='original'",
            'if(!current || current.hidden)showDetail(null);',
            'button.setAttribute(',
            'aria-expanded',
            'tabindex="0"'
        ] as $required)self::assertStringContainsString($required,$html);
        foreach([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage','sessionStorage',
            '<form','<input','<textarea','<iframe','contenteditable=','innerHTML',
            'navigator.clipboard', 'sendBeacon(', 'window.open(',
        ] as $forbidden)self::assertStringNotContainsString($forbidden,$html);
    }
}
