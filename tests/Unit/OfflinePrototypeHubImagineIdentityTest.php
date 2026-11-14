<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * CRM-UX-01: static identity/preview gates, not visual approval.
 * The 12 offline links and restrictive CSP must remain unchanged.
 */
final class OfflinePrototypeHubImagineIdentityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/index.html');
        self::assertIsString($html);
        return $html;
    }

    public function test_approved_imagine_palette_and_light_navigation_are_used(): void
    {
        $html = self::html();
        foreach ([
            '--imagine-orange:#F9941D',
            '--imagine-graphite:#171B19',
            '--imagine-white:#FFFFFF',
            '--imagine-background:#F7F7F8',
            'background:var(--imagine-graphite)',
            'background:var(--imagine-orange)',
            'color:var(--imagine-graphite)',
            'border-bottom:3px solid var(--imagine-orange)',
            'border-left:4px solid var(--imagine-orange)',
            '@media(max-width:950px)',
            '@media(max-width:620px)',
            '@media(prefers-reduced-motion:reduce)',
            'a:focus-visible',
            '.card a:focus-visible',
            'grid-template-columns:minmax(0,1fr)',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'color:#172f45',
            'background:#17334b',
            'background:#195c66',
            'outline:3px solid #108b85',
        ] as $oldStyle) {
            self::assertStringNotContainsString($oldStyle, $html);
        }
    }

    public function test_all_twelve_existing_demo_links_remain_offline_and_unchanged(): void
    {
        $html = self::html();
        preg_match_all('/\bhref="([^"]+)"/', $html, $links);
        self::assertSame([
            '#conteudo',
            's01-prot-01/index.html',
            'f3-59-offline-kanban/index.html',
            'f3-22-csv-risk-preview/index.html',
            'f3-104-retention-lifecycle/index.html',
            'f3-109-sales-quote-subtotal/index.html',
            'f3-113-scoring-trend/index.html',
            'f3-115-weighted-forecast/index.html',
            'f3-117-opportunity-outcomes/index.html',
            'f3-138-commercial-journey/index.html',
            'f3-148-private-stage-dashboard/index.html',
            'f3-155-commercial-workload/index.html',
            'f3-164-interactive-lead-triage/index.html',
        ], $links[1]);
        self::assertSame(12, substr_count($html, '<article class="card">'));
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        foreach ([
            "default-src 'none'",
            "script-src 'none'",
            "connect-src 'none'",
            "img-src 'none'",
            "form-action 'none'",
            'referrer" content="no-referrer"',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach (['<form', '<script', '<iframe', 'localStorage', 'fetch(', 'https://', 'http://'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
