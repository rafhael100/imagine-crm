<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/** UX Experience System v1: nine real offline navigation actions, grouped by task. */
final class ImagineExperienceNavigationV1Test extends TestCase
{
    public function test_menu_has_three_semantic_groups_and_preserves_all_nine_actions(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($doc);
        $groups = $xpath->query('//nav[@id="demo-navigation"]/div[@role="group"]');
        self::assertNotFalse($groups);
        self::assertSame(3, $groups->length);
        $expected = [
            'Início' => ['welcome', 'organization', 'dashboard'],
            'Operação comercial' => ['leads', 'pipeline', 'monitor'],
            'Revisão e suporte' => ['import', 'states', 'about'],
        ];
        foreach ($groups as $group) {
            $label = $group->getAttribute('aria-label');
            self::assertArrayHasKey($label, $expected);
            $buttons = $xpath->query('./button[@data-page][@type="button"]', $group);
            self::assertNotFalse($buttons);
            self::assertSame(3, $buttons->length);
            $pages = [];
            foreach ($buttons as $button) {
                $page = $button->getAttribute('data-page');
                self::assertSame('screen-'.$page, $button->getAttribute('aria-controls'));
                $pages[] = $page;
            }
            self::assertSame($expected[$label], $pages);
        }
        self::assertSame(1, $xpath->query('//nav[@id="demo-navigation"]//*[@aria-current="page"]')->length);
        self::assertSame(1, $xpath->query('//*[@id="nav-section"]')->length);
        self::assertSame(1, $xpath->query('//*[@id="nav-current"]')->length);
    }

    public function test_breadcrumb_and_touch_guards_are_dynamic_and_offline(): void
    {
        $html = (string) file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertStringContainsString('sectionHeading.textContent=sections[page];', $html);
        self::assertStringContainsString('pageCrumb.textContent=names[page];', $html);
        self::assertStringContainsString("if (!Object.prototype.hasOwnProperty.call(names,page)) return;", $html);
        self::assertStringContainsString('.nav-button{min-height:44px}', $html);
        self::assertStringContainsString('menuViewport.scrollLeft+=item.right-viewport.right+12;', $html);
        self::assertStringContainsString('menuViewport.scrollLeft-=viewport.left-item.left+12;', $html);
        self::assertStringContainsString('.lead,.step p,.caption,.soft,.field-label,.demo-ticket span,.bottom,.disclaimer{color:#626262}', $html);
        self::assertStringContainsString('.nav-group{display:flex;align-items:center;flex:0 0 auto', $html);
        self::assertStringContainsString('.side-nav{gap:8px}', $html);
        self::assertStringContainsString('prefers-reduced-motion:reduce', $html);
        self::assertStringNotContainsString('localStorage', $html);
        self::assertStringNotContainsString('sessionStorage', $html);
    }
}
