<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Static boundary checks on the invented, local-only record detail panel.
 * Chromium interaction is covered separately by check-followup-worklist-browser.php.
 */
final class OfflineFollowUpDetailContractTest extends TestCase
{
    private static function html(): string
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/prototypes/f4-28-followup-worklist/index.html');
        self::assertIsString($content);

        return $content;
    }

    public function test_detail_region_starts_hidden_and_has_keyboard_accessible_close(): void
    {
        $doc = new DOMDocument();
        $old = libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML(self::html(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }

        $xpath = new DOMXPath($doc);
        self::assertSame(
            1,
            $xpath->query('//*[@id="record-detail" and @hidden and @role="region" and @aria-labelledby="detail-title" and @tabindex="-1"]')->length,
        );
        self::assertSame(1, $xpath->query('//button[@id="close-detail" and @type="button"]')->length);
        foreach (['detail-id', 'detail-due', 'detail-bucket'] as $id) {
            self::assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length);
        }
        self::assertSame(1, $xpath->query('//th[normalize-space(.)="Ação local"]')->length);
    }

    public function test_detail_is_populated_as_plain_text_with_no_action_or_network_sink(): void
    {
        $html = self::html();
        foreach ([
            'document.getElementById("detail-id").textContent = record.id;',
            'document.getElementById("detail-due").textContent = record.due_at;',
            'document.getElementById("detail-bucket").textContent = BUCKET_NAMES[classify(record.due_at)];',
            'detailButton.setAttribute("aria-controls", "record-detail");',
            'detailButton.setAttribute("aria-expanded", "false");',
            'detailPanel.hidden = true;',
            'detailPanel.focus();',
            'hideDetail(false);',
            'hideDetail(true);',
        ] as $contract) {
            self::assertStringContainsString($contract, $html);
        }
        foreach (['.innerHTML', '.outerHTML', 'insertAdjacentHTML', 'fetch(', 'XMLHttpRequest',
            'WebSocket', 'localStorage', 'sessionStorage', '<form', '<input', '<iframe'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
    }

    public function test_original_time_offset_is_labeled_as_iso8601_not_falsely_utc(): void
    {
        $html = self::html();
        self::assertStringContainsString('Prazo (ISO 8601)', $html);
        self::assertStringContainsString('Prazo (com fuso)', $html);
        self::assertStringContainsString('2026-11-10T08:00:00-03:00', $html);
        self::assertStringNotContainsString('Prazo (UTC)', $html);
        self::assertStringNotContainsString('<dt>Prazo UTC</dt>', $html);
    }
}
