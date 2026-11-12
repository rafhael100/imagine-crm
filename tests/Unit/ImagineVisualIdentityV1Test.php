<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** The CRM offline surfaces must preserve the approved original Imagine Node visual identity. */
final class ImagineVisualIdentityV1Test extends TestCase
{
    private const SURFACES = [
        'prototypes/index.html',
        'prototypes/s01-prot-01/index.html',
        'prototypes/f3-59-offline-kanban/index.html',
    ];

    private const TOKENS = [
        'orange' => '#f9941d',
        'ink' => '#171b19',
        'white' => '#ffffff',
        'surface' => '#f7f7f8',
        'muted' => '#737373',
        'border' => '#e8e8e8',
        'orange-soft' => '#fff1e0',
        'orange-text' => '#9b4d00',
        'focus' => '#b75c00',
        'radius-control' => '6px',
        'radius-card' => '9px',
        'sidebar-width' => '240px',
        'font-ui' => 'Arial,Helvetica,sans-serif',
    ];

    public function test_canonical_local_contract_equals_all_three_offline_alias_sets(): void
    {
        $root = dirname(__DIR__, 2);
        $tokens = file_get_contents($root.'/design-system/tokens.css');
        self::assertIsString($tokens);
        self::assertStringContainsString('historical Node Portal', $tokens);
        foreach (self::TOKENS as $name => $value) {
            $name = '--imagine-'.$name;
            $canonical = preg_replace('/\\s+/', '', $tokens);
            self::assertIsString($canonical);
            self::assertStringContainsString($name.':'.$value.';', strtolower($canonical));
        }
        foreach (self::SURFACES as $surface) {
            $html = file_get_contents($root.'/'.$surface);
            self::assertIsString($html);
            self::assertSame(1, substr_count($html, 'IMAGINE-DS-V1'), $surface);
            self::assertStringContainsString('font-family:var(--imagine-font-ui)', $html, $surface);
            foreach (self::TOKENS as $name => $value) {
                $name = '--imagine-'.$name;
                self::assertStringContainsString($name.':'.$value, $html, $surface);
            }
            self::assertStringContainsString("connect-src 'none'", $html);
            self::assertStringContainsString("form-action 'none'", $html);
            self::assertStringNotContainsString('@import', $html);
            self::assertStringNotContainsString('https://cdn.', $html);
            self::assertStringNotContainsString('localStorage', $html);
            self::assertStringNotContainsString('sessionStorage', $html);
        }
    }

    public function test_selected_navigation_and_high_contrast_brand_actions_are_explicit(): void
    {
        $root = dirname(__DIR__, 2).'/prototypes/';
        $shell = (string) file_get_contents($root.'s01-prot-01/index.html');
        self::assertStringContainsString('.sidebar{background:var(--imagine-white);color:var(--imagine-ink)', $shell);
        self::assertStringContainsString('.nav-button[aria-current="page"]{background:var(--imagine-orange-soft);color:var(--imagine-orange-text)', $shell);
        self::assertStringContainsString('.hero{background:var(--imagine-ink)', $shell);
        self::assertStringContainsString('.action,.hero .action{background:var(--imagine-orange);color:var(--imagine-ink)', $shell);
        self::assertStringContainsString('@media(max-width:900px){.shell{grid-template-columns:minmax(0,1fr)}', $shell);

        $hub = (string) file_get_contents($root.'index.html');
        self::assertStringContainsString('.card a{background:var(--imagine-orange);color:var(--imagine-ink)', $hub);
        self::assertStringContainsString('script-src \'none\'', $hub);
        self::assertStringContainsString('img-src \'none\'', $hub);

        $board = (string) file_get_contents($root.'f3-59-offline-kanban/index.html');
        self::assertStringContainsString('.filters button[aria-pressed="true"],.selection-toggle[aria-pressed="true"]{background:var(--imagine-orange-soft);color:var(--imagine-orange-text)', $board);
        self::assertStringContainsString('outline:3px solid var(--imagine-focus)', $board);
        self::assertStringContainsString('frame-src \'none\'', $board);
    }
}
