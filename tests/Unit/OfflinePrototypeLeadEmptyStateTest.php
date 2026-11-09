<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F3-30: accessible empty feedback; not an actual lead query.
 */
final class OfflinePrototypeLeadEmptyStateTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_hidden_empty_state_and_clear_button_are_accessible_and_local(): void
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($dom->loadHTML(self::html(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($dom);
        $states = $xpath->query('//section[@id="screen-leads"]//div[@id="lead-empty-filter-state" and @role="status" and @aria-live="polite" and @aria-atomic="true" and @hidden]');
        self::assertNotFalse($states);
        self::assertSame(1, $states->length);
        self::assertStringContainsString('Nenhum lead fictício corresponde', $states->item(0)->textContent);
        self::assertStringContainsString('Não foi realizada consulta ao CRM.', $states->item(0)->textContent);

        $buttons = $xpath->query('//div[@id="lead-empty-filter-state"]//button[@id="lead-clear-filters" and @type="button"]');
        self::assertNotFalse($buttons);
        self::assertSame(1, $buttons->length);
        self::assertSame('Limpar filtros fictícios', trim($buttons->item(0)->textContent));

        self::assertSame(4, $xpath->query('//section[@id="screen-leads"]//tbody/tr[@data-lead-fixture-stage]')->length);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_empty_state_toggles_from_combined_filter_count_and_reset_restores_focus(): void
    {
        $html = self::html();
        foreach ([
            "leadEmptyFilterState.hidden=displayed!==0",
            "leadFilterSummary.textContent='Mostrando '",
            "leadClearFiltersButton.addEventListener('click'",
            "showLeadStage('all')",
            "showLeadTemperature('all')",
            "button.getAttribute('data-lead-stage')==='all'",
            "if(defaultStage) defaultStage.focus()",
            "node.hidden=!(byStage&&byTemperature)",
            "if(!active || active.closest('tr').hidden) showLeadDetail(null)",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
    }

    public function test_no_network_mutation_user_input_or_permissions_are_added(): void
    {
        $html = self::html();
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);

        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage',
            'sessionStorage', 'indexedDB', '<form', '<input', '<textarea',
            'contenteditable=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }

        self::assertStringContainsString('Não foi realizada consulta ao CRM.', $html);
        self::assertStringContainsString('Visualização ilustrativa não autoriza exportação, alteração ou ação comercial.', $html);
    }
}
