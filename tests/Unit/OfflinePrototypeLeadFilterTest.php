<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Contracts of the four literal fixtures in the public-offline demonstration.
 * Real Chromium events are checked separately in both 520px and 320px jobs.
 */
final class OfflinePrototypeLeadFilterTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        return $html;
    }

    public function test_four_predefined_leads_have_typed_stages_and_no_customer_fields(): void
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML(self::html(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $rows = $xpath->query('//section[@id="screen-leads"]//tbody/tr[@data-lead-fixture-stage]');
        self::assertNotFalse($rows);
        self::assertSame(4, $rows->length);
        $found = [];
        foreach ($rows as $row) {
            $found[] = $row->getAttribute('data-lead-fixture-stage');
            self::assertStringContainsString('Lead Exemplo', $row->textContent);
        }
        self::assertSame(['entrada', 'em-contato', 'qualificacao', 'entrada'], $found);

        $buttons = $xpath->query('//section[@id="screen-leads"]//button[@data-lead-stage]');
        self::assertNotFalse($buttons);
        self::assertSame(4, $buttons->length);
        $values = [];
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            $values[] = $button->getAttribute('data-lead-stage');
            self::assertTrue(in_array($button->getAttribute('aria-pressed'), ['true', 'false'], true));
        }
        self::assertSame(['all', 'entrada', 'em-contato', 'qualificacao'], $values);

        $summary = $xpath->query('//p[@id="lead-filter-summary" and @role="status" and @aria-live="polite"]');
        self::assertNotFalse($summary);
        self::assertSame(1, $summary->length);
        self::assertStringContainsString('4 de 4 leads fictícios', $summary->item(0)->textContent);
    }

    public function test_filter_code_changes_only_dom_visibility_and_feedback(): void
    {
        $html = self::html();
        foreach ([
            'function showLeadStage(stage)',
            "const stages=['all','entrada','em-contato','qualificacao']",
            "node.setAttribute('aria-pressed'",
            "node.hidden=!(byStage&&byTemperature)",
            "leadFilterSummary.textContent='Mostrando '",
            "showLeadStage('all')",
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }

        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('sessionStorage', $html);
        self::assertStringNotContainsString('fetch(', $html);
        self::assertStringNotContainsString('XMLHttpRequest', $html);
        self::assertStringNotContainsString('input type="file"', $html);
        self::assertStringNotContainsString('contenteditable=', $html);
    }
}
