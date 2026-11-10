<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpUtcHorizon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F4-04: a closed, side-effect-free preview of UTC day buckets in HTML.
 * Live Chromium CI separately verifies controls at 520 and 320 CSS pixels.
 */
final class OfflinePrototypeFollowUpHorizonTest extends TestCase
{
    private const UTC = '2026-11-09T22:00:00Z';

    public function test_one_day_and_seven_day_views_match_bounded_php_horizon(): void
    {
        $fixtures = [
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
            ['due_at' => self::UTC, 'completed' => false],
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => true],
            ['due_at' => '2026-11-12T23:00:00Z', 'completed' => false],
        ];
        $projector = new SyntheticFollowUpUtcHorizon();
        $one = $projector->summarize($fixtures, self::UTC, 1);
        $seven = $projector->summarize($fixtures, self::UTC, 7);

        self::assertSame(['2026-11-09' => 1], $one['daily']);
        self::assertSame(1, $one['upcoming_within_horizon']);
        self::assertSame(1, $one['upcoming_outside_horizon']);
        self::assertSame([
            '2026-11-09' => 1,
            '2026-11-10' => 0,
            '2026-11-11' => 0,
            '2026-11-12' => 1,
            '2026-11-13' => 0,
            '2026-11-14' => 0,
            '2026-11-15' => 0,
        ], $seven['daily']);
        self::assertSame(2, $seven['upcoming_within_horizon']);
        self::assertSame(0, $seven['upcoming_outside_horizon']);
        foreach ([$one, $seven] as $result) {
            self::assertFalse($result['authorized']);
            self::assertFalse($result['can_notify']);
        }

        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        self::assertSame(5, substr_count($html, 'Object.freeze({dueAt:'));
        self::assertStringContainsString("Object.freeze({dueAt:'2026-11-12T23:00:00Z',completed:false})", $html);
        self::assertStringContainsString("const followUpReferenceUtc='".self::UTC."'", $html);
        foreach (array_keys($seven['daily']) as $day) {
            self::assertStringContainsString("'".$day."'", $html);
        }
    }

    public function test_horizon_controls_are_semantically_accessible_and_bounded(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        $doc = new DOMDocument();
        $old = libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }

        $xp = new DOMXPath($doc);
        $buttons = $xp->query('//section[@data-followup-demo]//button[@data-followup-horizon]');
        self::assertNotFalse($buttons);
        self::assertSame(2, $buttons->length);
        self::assertSame(['1', '7'], [
            $buttons->item(0)->getAttribute('data-followup-horizon'),
            $buttons->item(1)->getAttribute('data-followup-horizon'),
        ]);
        self::assertSame('true', $buttons->item(0)->getAttribute('aria-pressed'));
        self::assertSame('false', $buttons->item(1)->getAttribute('aria-pressed'));
        foreach ($buttons as $button) {
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'));
        }
        self::assertSame(1, $xp->query('//section[@data-followup-demo]//p[@id="followup-horizon-summary" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length);
        self::assertSame(1, $xp->query('//section[@data-followup-demo]//ul[@id="followup-horizon-daily" and @aria-label]')->length);
        self::assertSame(9, $xp->query('//section[@data-screen]')->length);
    }

    public function test_only_static_text_is_rendered_and_no_active_calendar_is_introduced(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'function renderFollowUpHorizon()',
            'function showFollowUpHorizon(days)',
            'followUpHorizonDaily.replaceChildren();',
            "const row=document.createElement('li');",
            "const days=followUpHorizonDates.slice(0,selectedFollowUpHorizon);",
            "if(days!==1&&days!==7) return;",
            "showFollowUpHorizon(1);",
            "connect-src 'none'",
            "form-action 'none'",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage', 'Date.now(', '<form', '<input', '<textarea'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
