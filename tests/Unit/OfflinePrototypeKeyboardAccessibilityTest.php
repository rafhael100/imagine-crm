<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * DOM/static regressions for a local-only illustrative HTML prototype.
 * Not a headless browser, responsive screenshot or assistive-technology audit.
 */
final class OfflinePrototypeKeyboardAccessibilityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        return $html;
    }

    private static function xpath(): DOMXPath
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML(self::html(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($doc);
    }

    public function test_a_skip_link_points_to_the_real_focusable_main_landmark(): void
    {
        $xpath = self::xpath();
        $skip = $xpath->query('//body/a[@class="skip-link" and @href="#conteudo"]');
        self::assertNotFalse($skip);
        self::assertSame(1, $skip->length);
        self::assertSame('Pular para o conteúdo', trim($skip->item(0)->textContent));

        $main = $xpath->query('//main[@id="conteudo" and @tabindex="-1"]');
        self::assertNotFalse($main);
        self::assertSame(1, $main->length);

        // The link is not permanently invisible to keyboard users.
        $html = self::html();
        self::assertStringContainsString('.skip-link:focus-visible{transform:translateY(0)', $html);
        self::assertStringContainsString('outline:3px solid', $html);
    }

    public function test_each_demo_navigation_control_targets_exactly_one_screen(): void
    {
        $xpath = self::xpath();
        $nav = $xpath->query('//nav[@id="demo-navigation" and @aria-label="Telas de demonstração"]/div[@role="group"]/button[@data-page]');
        self::assertNotFalse($nav);
        self::assertSame(9, $nav->length);
        $groups = $xpath->query('//nav[@id="demo-navigation"]/div[@role="group"]');
        self::assertNotFalse($groups);
        self::assertSame(3, $groups->length, 'Task-group layout retains all nine semantic controls.');
        $selected = 0;
        $screens = [];

        foreach ($nav as $button) {
            $page = $button->getAttribute('data-page');
            self::assertMatchesRegularExpression('/\A[a-z]+\z/', $page);
            self::assertSame('button', $button->getAttribute('type'));
            self::assertSame('screen-'.$page, $button->getAttribute('aria-controls'));

            $section = $xpath->query('//section[@id="screen-'.$page.'" and @data-screen="'.$page.'"]');
            self::assertNotFalse($section);
            self::assertSame(1, $section->length, "Missing/duplicate panel for {$page}");

            if ($button->getAttribute('aria-current') === 'page') {
                ++$selected;
                self::assertSame('welcome', $page);
            }
            if ($page !== 'welcome') {
                self::assertTrue($section->item(0)->hasAttribute('hidden'));
            }
            $screens[] = $page;
        }

        self::assertSame(1, $selected);
        self::assertSame([
            'welcome', 'organization', 'dashboard', 'leads', 'pipeline',
            'monitor', 'import', 'states', 'about',
        ], $screens);
    }

    public function test_keyboard_arrows_home_and_end_move_focus_but_do_not_publish_data(): void
    {
        $html = self::html();
        foreach ([
            "event.key==='ArrowRight'", "event.key==='ArrowDown'",
            "event.key==='ArrowLeft'", "event.key==='ArrowUp'",
            "event.key==='Home'", "event.key==='End'",
            'event.preventDefault()', 'nav[next].focus()', 'nav[next].scrollIntoView',
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }

        self::assertStringContainsString("node.addEventListener('click',function(){navigate(", $html);
        self::assertStringContainsString("if (moveFocus) heading.focus();", $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('fetch(', $html);
    }

    public function test_mobile_navigation_remains_scrollable_with_visible_instruction(): void
    {
        $xpath = self::xpath();
        $hint = $xpath->query('//p[@class="mobile-nav-hint"]');
        self::assertNotFalse($hint);
        self::assertSame(1, $hint->length);
        self::assertStringContainsString('Deslize horizontalmente', $hint->item(0)->textContent);

        $html = self::html();
        self::assertStringContainsString('@media(max-width:900px)', $html);
        self::assertStringContainsString('.shell{grid-template-columns:minmax(0,1fr)}', $html);
        self::assertStringContainsString('.sidebar > div{min-width:0}', $html);
        self::assertStringContainsString('.side-nav{display:flex;min-width:0;max-width:100%;overflow-x:auto', $html);
        self::assertStringContainsString('overflow-x:auto;overscroll-behavior-inline:contain', $html);
        self::assertStringContainsString('.mobile-nav-hint{display:block', $html);
        self::assertStringContainsString('@media(max-width:420px)', $html);
        self::assertStringContainsString('@media(prefers-reduced-motion:reduce)', $html);
    }
}
