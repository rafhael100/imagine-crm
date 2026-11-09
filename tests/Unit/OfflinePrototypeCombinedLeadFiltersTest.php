<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F3-29: combined stage / descriptive band filters on four fake rows.
 * These are not real scores, lead search results or authorization filters.
 */
final class OfflinePrototypeCombinedLeadFiltersTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        return $html;
    }

    public function test_closed_temperature_filter_is_accessible_and_has_one_selected_mode(): void
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

        $buttons = $xpath->query('//section[@id="screen-leads"]//div[@aria-label="Filtrar leads fictícios por faixa descritiva"]/button[@data-lead-temperature]');
        self::assertNotFalse($buttons);
        self::assertSame(4, $buttons->length);

        $names = [];
        $pressed = 0;
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertContains($button->getAttribute('aria-pressed'), ['true', 'false']);
            $names[] = $button->getAttribute('data-lead-temperature');
            if ($button->getAttribute('aria-pressed') === 'true') {
                $pressed++;
            }
        }

        self::assertSame(['all', 'cold', 'warm', 'hot'], $names);
        self::assertSame(1, $pressed);
        self::assertSame('all', $buttons->item(0)->getAttribute('data-lead-temperature'));

        $rows = $xpath->query('//section[@id="screen-leads"]//tbody/tr[@data-lead-fixture-temperature]');
        self::assertNotFalse($rows);
        self::assertSame(4, $rows->length);

        $values = [];
        foreach ($rows as $row) {
            $values[] = $row->getAttribute('data-lead-fixture-temperature');
            self::assertStringContainsString('Lead Exemplo', $row->textContent);
        }
        self::assertSame(['cold', 'warm', 'hot', 'hot'], $values);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_both_filters_are_intersected_without_exposing_hidden_detail(): void
    {
        $html = self::html();
        foreach ([
            'function applyLeadFixtureFilters()',
            'function showLeadStage(stage)',
            'function showLeadTemperature(temperature)',
            "const temperatures=['all','cold','warm','hot']",
            "selectedLeadStage=stage",
            "selectedLeadTemperature=temperature",
            "const byStage=selectedLeadStage==='all'||node.getAttribute('data-lead-fixture-stage')===selectedLeadStage",
            "const byTemperature=selectedLeadTemperature==='all'||node.getAttribute('data-lead-fixture-temperature')===selectedLeadTemperature",
            'node.hidden=!(byStage&&byTemperature)',
            "if(!active || active.closest('tr').hidden) showLeadDetail(null)",
            "showLeadStage('all')",
            "showLeadTemperature('all')",
            'leadFilterSummary.textContent',
            'data-pipeline-review',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
    }

    public function test_filter_controls_are_offline_only_and_cannot_be_permissions(): void
    {
        $html = self::html();
        foreach ([
            'connect-src \'none\'',
            'form-action \'none\'',
            'FICHA ILUSTRATIVA — DADOS FICTÍCIOS',
            'Visualização ilustrativa não autoriza exportação, alteração ou ação comercial.',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }

        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage',
            'indexedDB', '<form', '<input', '<textarea', 'contenteditable=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
