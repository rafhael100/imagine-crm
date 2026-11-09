<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** UX-F3-27: fixed timeline texts; not a historical API or an audit trail. */
final class OfflinePrototypeLeadHistoryTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_four_literal_timeline_slices_exist_without_actor_date_or_client_payload(): void
    {
        $html = self::html();

        self::assertSame(4, substr_count($html, 'events:Object.freeze(['));
        foreach ([
            "events:Object.freeze(['Entrada ilustrativa na lista.', 'Amostra aguardando revisão visual.'])",
            "events:Object.freeze(['Entrada ilustrativa na lista.', 'Etapa fictícia alterada para Em contato.'])",
            'Contato fictício descrito no fluxo visual.',
            'Etapa fictícia alterada para Qualificação.',
            'Pontuação de demonstração revisada visualmente.',
            'Sequência ilustrativa de eventos (sem proveniência verificada)',
            'Todos os eventos são textos inventados, sem datas, autores ou comprovação do CRM.',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }

        self::assertStringNotContainsString('lead@example.com', $html);
        self::assertStringNotContainsString('history_api', $html);
    }

    public function test_display_uses_only_safe_dom_text_and_never_performs_mutation_or_export(): void
    {
        $html = self::html();

        foreach ([
            "const list=document.createElement('ol')",
            "list.setAttribute('aria-label','Eventos fictícios do exemplo selecionado')",
            "data.events.forEach(function(label)",
            "addText(list,'li',label)",
            "inner.appendChild(list)",
            "leadDetailPreview.replaceChildren()",
            "if(!active || active.closest('tr').hidden) showLeadDetail(null)",
            'Visualização ilustrativa não autoriza exportação, alteração ou ação comercial.',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }

        foreach ([
            'innerHTML', 'insertAdjacentHTML', 'fetch(', 'XMLHttpRequest', 'WebSocket',
            'indexedDB', 'localStorage', 'sessionStorage',
            '<input', '<textarea', '<form', 'contenteditable=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertSame(9, substr_count($html, ' data-screen="'));
    }
}
