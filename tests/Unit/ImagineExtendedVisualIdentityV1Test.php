<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** DS-V1 visual-contract regression for the ten independent offline screens. */
final class ImagineExtendedVisualIdentityV1Test extends TestCase
{
    private const SCREENS = [
        'f3-22-csv-risk-preview', 'f3-104-retention-lifecycle',
        'f3-109-sales-quote-subtotal', 'f3-113-scoring-trend',
        'f3-115-weighted-forecast', 'f3-117-opportunity-outcomes',
        'f3-138-commercial-journey', 'f3-148-private-stage-dashboard',
        'f3-155-commercial-workload', 'f3-164-interactive-lead-triage',
    ];

    public function test_ten_offline_screens_share_node_palette_and_keep_network_blocked(): void
    {
        self::assertCount(10, self::SCREENS);
        foreach (self::SCREENS as $screen) {
            $html = file_get_contents(
                dirname(__DIR__, 2).'/prototypes/'.$screen.'/index.html',
            );
            self::assertIsString($html);
            self::assertSame(1, substr_count($html, 'IMAGINE-DS-V1'), $screen);
            self::assertSame(1, substr_count($html, '</style>'), $screen);
            foreach ([
                '--imagine-orange:#f9941d;',
                '--imagine-ink:#171b19;',
                '--imagine-white:#ffffff;',
                '--imagine-surface:#f7f7f8;',
                '--imagine-orange-soft:#fff1e0;',
                '--imagine-orange-text:#9b4d00;',
                '--imagine-focus:#b75c00;',
                '--imagine-radius-card:9px;',
                '--imagine-sidebar-width:240px;',
                '--imagine-font-ui:Arial,Helvetica,sans-serif;',
                'body{background:var(--imagine-surface)}',
                'header{background:var(--imagine-white);color:var(--imagine-ink);',
                'a:focus-visible,button:focus-visible,input:focus-visible,',
                "connect-src 'none'",
                "form-action 'none'",
            ] as $expected) {
                self::assertStringContainsString($expected, $html, $screen);
            }
            self::assertStringNotContainsString('@import', $html, $screen);
            self::assertStringNotContainsString('https://cdn.', $html, $screen);
        }
    }

    public function test_radio_selection_uses_high_contrast_orange_without_changing_input_structure(): void
    {
        $root = dirname(__DIR__, 2).'/prototypes/';
        $privacy = (string) file_get_contents($root.'f3-148-private-stage-dashboard/index.html');
        $load = (string) file_get_contents($root.'f3-155-commercial-workload/index.html');
        self::assertStringContainsString(
            '#stable:checked~.choices label[for=stable],#changed:checked~.choices',
            $privacy,
        );
        self::assertStringContainsString(
            '#pressure:checked~.tabs label[for=pressure],#balanced:checked~.tabs',
            $load,
        );
        foreach ([$privacy, $load] as $html) {
            self::assertStringContainsString('background:var(--imagine-orange-soft);color:var(--imagine-orange-text)', $html);
            self::assertStringContainsString('outline:3px solid var(--imagine-focus)', $html);
        }
    }
}
