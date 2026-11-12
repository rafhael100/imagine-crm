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
            self::assertStringContainsString(strtolower($name.':'.$value.';'), strtolower($canonical));
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
        // The embedded PNG must be byte-identical to the original binary asset.
        self::assertSame(1, preg_match(
            '/<img class="brand-mark brand-logo"[^>]+src="data:image\\/png;base64,([A-Za-z0-9+\\/=]+)"/',
            $shell, $match,
        ));
        $decodedLogo = base64_decode($match[1], true);
        self::assertIsString($decodedLogo);
        self::assertSame(
            file_get_contents($root.'assets/logo-imagine.png'),
            $decodedLogo,
        );
        self::assertStringContainsString('.sidebar{background:var(--imagine-white);color:var(--imagine-ink)', $shell);
        self::assertStringContainsString('.nav-button[aria-current="page"]{background:var(--imagine-orange-soft);color:var(--imagine-orange-text)', $shell);
        self::assertStringContainsString('.hero{background:var(--imagine-ink)', $shell);
        self::assertStringContainsString('.action,.hero .action{background:var(--imagine-orange);color:var(--imagine-ink)', $shell);
        self::assertStringContainsString('@media(max-width:900px){.shell{grid-template-columns:minmax(0,1fr)}', $shell);

        $hub = (string) file_get_contents($root.'index.html');
        self::assertStringContainsString('.card a{background:var(--imagine-orange);color:var(--imagine-ink)', $hub);
        self::assertStringContainsString('script-src \'none\'', $hub);
        self::assertStringContainsString("img-src 'self'", $hub);

        $board = (string) file_get_contents($root.'f3-59-offline-kanban/index.html');
        self::assertStringContainsString('.filters button[aria-pressed="true"],.selection-toggle[aria-pressed="true"]{background:var(--imagine-orange-soft);color:var(--imagine-orange-text)', $board);
        self::assertStringContainsString('outline:3px solid var(--imagine-focus)', $board);
        self::assertStringContainsString('frame-src \'none\'', $board);
        self::assertStringContainsString("img-src 'self' data:", $board);
        foreach (['index.html'=>'assets/logo-imagine.png', 'f3-59-offline-kanban/index.html'=>'../assets/logo-imagine.png'] as $path=>$logo) {
            $document=(string)file_get_contents($root.$path);
            self::assertSame(1,substr_count($document, 'src="'.$logo.'"'));
            self::assertSame(1,substr_count($document, 'alt="Imagine Comunicação Digital"'));
            self::assertStringNotContainsString('data:image/png;base64', $document);
        }
        $asset=(string)file_get_contents($root.'assets/logo-imagine.png');
        self::assertSame('678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77',sha1('blob '.strlen($asset).chr(0).$asset));
    }
}
