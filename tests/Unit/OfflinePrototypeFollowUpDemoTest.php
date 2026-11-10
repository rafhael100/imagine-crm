<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpQueue;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * F4-01 UX: mock labels must match the fixed PHP follow-up laboratory counts.
 * No alarm, browser clock, storage, identity or network is used.
 */
final class OfflinePrototypeFollowUpDemoTest extends TestCase
{
    public function test_four_accessible_static_modes_correspond_to_anonymous_php_fixture_counts(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $groups = $xpath->query('//section[@id="screen-monitor"]//section[@data-followup-demo]');
        self::assertSame(1, $groups->length);
        $buttons = $xpath->query('//section[@data-followup-demo]//button[@data-followup-case]');
        self::assertSame(4, $buttons->length);
        $modes = [];
        $pressed = 0;
        foreach ($buttons as $button) {
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'));
            $modes[] = $button->getAttribute('data-followup-case');
            self::assertContains($button->getAttribute('aria-pressed'), ['true', 'false']);
            if ($button->getAttribute('aria-pressed') === 'true') {
                $pressed++;
            }
        }
        self::assertSame(['overdue', 'due-now', 'upcoming', 'completed'], $modes);
        self::assertSame(1, $pressed);
        self::assertSame('true', $buttons->item(0)->getAttribute('aria-pressed'));

        self::assertSame(
            1,
            $xpath->query('//section[@data-followup-demo]//div[@id="followup-demo-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length,
        );

        $fixed = [
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T22:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => true],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
        ];
        $summary = (new SyntheticFollowUpQueue())->summarize($fixed, '2026-11-09T22:00:00Z');
        self::assertSame(['overdue' => 1, 'due_now' => 1, 'upcoming' => 2, 'completed' => 1], [
            'overdue' => $summary['overdue'],
            'due_now' => $summary['due_now'],
            'upcoming' => $summary['upcoming'],
            'completed' => $summary['completed'],
        ]);
        self::assertFalse($summary['authorized']);
        self::assertFalse($summary['can_notify']);
        self::assertSame(5, $summary['total']);
    }

    public function test_demo_cannot_become_a_real_task_writer_or_notification_channel(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'function showFollowUpCase(code)',
            "showFollowUpCase('overdue')",
            'followUpPreview.replaceChildren();',
            'EXEMPLO DE ATIVIDADE — SEM AGENDA REAL',
            'Nenhuma ação ou notificação foi executada.',
            "connect-src 'none'",
            "form-action 'none'",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage', '<form', '<input', '<textarea'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
