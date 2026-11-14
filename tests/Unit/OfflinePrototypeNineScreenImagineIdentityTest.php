<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** CRM UX-02: visual-token guard without claiming production UX approval. */
final class OfflinePrototypeNineScreenImagineIdentityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        return $html;
    }

    public function test_nine_screen_demo_uses_approved_imagine_palette(): void
    {
        $html = self::html();
        foreach ([
            '--imagine-orange:#F9941D',
            '--imagine-graphite:#171B19',
            '--imagine-white:#FFFFFF',
            '--imagine-background:#F7F7F8',
            'background:#171B19;color:#FFFFFF',
            'border-color:#F9941D',
            'background:linear-gradient(110deg,#171B19,#393024)',
            '.hero .action{background:#F9941D;color:#171B19}',
            '.action{border:0;border-radius:11px;background:#F9941D;color:#171B19',
            'outline:3px solid #F9941D',
            '@media(max-width:900px)',
            '@media(max-width:420px)',
            '@media(prefers-reduced-motion:reduce)',
            '.side-nav{display:flex;min-width:0;max-width:100%;overflow-x:auto',
            'scrollbar-width:thin',
        ] as $needle) {
            self::assertStringContainsString($needle, $html);
        }
        foreach ([
            'background:#122943',
            'background:#1d7e90',
            'background:#24556b',
            'background:linear-gradient(110deg,#173450,#1e6575)',
            'background:#1b8b8c',
        ] as $outdated) {
            self::assertStringNotContainsString($outdated, $html);
        }
    }

    public function test_imagine_logo_is_canonical_png_and_local_only(): void
    {
        $html = self::html();
        self::assertSame(1, substr_count(
            $html,
            '<img class="brand-logo" src="../assets/logo-imagine.png" width="200" height="55" alt="Imagine">',
        ));
        self::assertStringContainsString("img-src 'self' data:", $html);
        $asset = dirname(__DIR__, 2).'/prototypes/assets/logo-imagine.png';
        self::assertFileExists($asset);
        $binary = file_get_contents($asset);
        self::assertIsString($binary);
        self::assertSame(
            '678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77',
            sha1('blob '.strlen($binary)."\0".$binary),
        );
        self::assertSame([200, 55], array_values(
            unpack('Nwidth/Nheight', substr($binary, 16, 8)),
        ));
    }

    public function test_demo_remains_nine_offline_screens_with_no_network_or_real_auth(): void
    {
        $html = self::html();
        preg_match_all('/\bdata-screen="([^"]+)"/', $html, $screens);
        self::assertSame([
            'welcome', 'organization', 'dashboard', 'leads', 'pipeline',
            'monitor', 'import', 'states', 'about',
        ], $screens[1]);
        preg_match_all('/<button class="nav-button"[^>]+data-page="([^"]+)"/', $html, $buttons);
        self::assertSame($screens[1], $buttons[1]);
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS',
            "connect-src 'none'",
            "form-action 'none'",
            'Nenhum acesso foi concedido ou verificado.',
            "if (moveFocus) heading.focus();",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'localStorage', 'sessionStorage',
            'XMLHttpRequest', 'fetch(',
            'APP_KEY=', 'DB_PASSWORD=',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
