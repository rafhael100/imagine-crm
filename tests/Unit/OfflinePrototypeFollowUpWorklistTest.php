<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Activities\SyntheticFollowUpWorklist;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-F4-02: fixture-only list preview. Chromium tests exercise the controls
 * in both viewports; PHP verifies the closed contract and static HTML boundary.
 */
final class OfflinePrototypeFollowUpWorklistTest extends TestCase
{
    public function test_five_utc_fixture_rows_match_php_worklist_positions(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        self::assertSame(5, substr_count($html, 'Object.freeze({dueAt:'));
        self::assertStringContainsString("Object.freeze({dueAt:'2026-11-12T23:00:00Z',completed:false})", $html);
        self::assertStringContainsString("const followUpReferenceUtc='2026-11-09T22:00:00Z'", $html);
        foreach (['21:00:00Z', '22:00:00Z', '23:00:00Z'] as $time) {
            self::assertStringContainsString($time, $html);
        }

        $fixtures = [
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T23:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T22:00:00Z', 'completed' => false],
            ['due_at' => '2026-11-09T21:00:00Z', 'completed' => true],
            ['due_at' => '2026-11-12T23:00:00Z', 'completed' => false],
        ];

        $worklist = new SyntheticFollowUpWorklist();
        foreach ([
            'overdue' => [0],
            'due_now' => [2],
            'upcoming' => [1, 4],
            'completed' => [3],
        ] as $view => $expected) {
            $result = $worklist->page($fixtures, '2026-11-09T22:00:00Z', $view, 0, 2);
            self::assertSame($expected, $result['positions']);
            self::assertSame(count($expected), $result['matched']);
            self::assertFalse($result['authorized']);
            self::assertFalse($result['can_notify']);
        }

        self::assertSame(
            [1],
            $worklist->page($fixtures, '2026-11-09T22:00:00Z', 'upcoming', 0, 1)['positions'],
        );
        self::assertSame(
            [4],
            $worklist->page($fixtures, '2026-11-09T22:00:00Z', 'upcoming', 1, 1)['positions'],
        );
    }

    public function test_accessible_pagination_and_list_appear_once_inside_offline_monitor(): void
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
        $sizes = $xp->query('//section[@data-followup-demo]//button[@data-followup-page-size]');
        self::assertSame(2, $sizes->length);
        self::assertSame(['2', '1'], [
            $sizes->item(0)->getAttribute('data-followup-page-size'),
            $sizes->item(1)->getAttribute('data-followup-page-size'),
        ]);
        self::assertSame('true', $sizes->item(0)->getAttribute('aria-pressed'));
        self::assertSame('false', $sizes->item(1)->getAttribute('aria-pressed'));
        foreach (['followup-page-prev', 'followup-page-next'] as $id) {
            $buttonList = $xp->query('//section[@data-followup-demo]//button[@id="'.$id.'"]');
            self::assertSame(1, $buttonList->length);
            $button = $buttonList->item(0);
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertTrue($button->hasAttribute('disabled'));
            self::assertSame('button', $button->getAttribute('type'));
        }
        self::assertSame(1, $xp->query('//section[@data-followup-demo]//p[@id="followup-page-summary" and @role="status" and @aria-live="polite"]')->length);
        self::assertSame(1, $xp->query('//section[@data-followup-demo]//ul[@id="followup-fixture-list" and @aria-label]')->length);
        self::assertSame(9, $xp->query('//section[@data-screen]')->length);
    }

    public function test_offline_render_uses_only_local_text_and_no_task_writer(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'function renderFollowUpWorklist()',
            'followUpList.replaceChildren();',
            "const li=document.createElement('li');",
            "followUpPageSummary.textContent=pages===0?",
            "function showFollowUpPageSize(size)",
            "function moveFollowUpPage(step)",
            'selectedFollowUpPage=0;',
            'selectedFollowUpPageSize=2;',
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
