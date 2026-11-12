<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OfflinePrivateStageDashboardTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-148-private-stage-dashboard/index.html',
        );
        self::assertIsString($html);
        return $html;
    }

    public function test_three_scenarios_are_keyboard_accessible_and_all_claims_are_inert(): void
    {
        $html = self::html();
        foreach (['stable', 'changed', 'hidden'] as $case) {
            self::assertStringContainsString('id="'.$case.'"', $html);
            self::assertStringContainsString('for="'.$case.'"', $html);
        }
        self::assertSame(3, substr_count($html, 'type="radio"'));
        self::assertStringContainsString('input:focus-visible+label', $html);
        self::assertStringContainsString('role="group"', $html);
        self::assertStringContainsString('PROTÓTIPO OFFLINE', $html);
        self::assertStringContainsString('Exportar: bloqueado', $html);
        self::assertStringContainsString('Enviar: bloqueado', $html);
        self::assertStringContainsString('Gravar: bloqueado', $html);
        self::assertSame(3, substr_count($html, 'Exportar: bloqueado'));
    }

    public function test_all_scenario_content_is_local_without_network_or_exfiltration(): void
    {
        $html = self::html();
        foreach ([
            "default-src 'none'", "script-src 'none'",
            "connect-src 'none'", "form-action 'none'",
            "img-src 'none'", "frame-src 'none'",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            '<script', '<form', '<iframe', '<img', 'https://', 'http://',
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage',
            'sessionStorage', 'indexedDB', 'onclick=', 'onload=',
            'download=', '@import', 'url(', 'mailto:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertStringContainsString('@media(max-width:360px)', $html);
        self::assertStringContainsString('href="../index.html"', $html);
    }

    public function test_sensitive_snapshots_never_show_percentages_in_protected_scenarios(): void
    {
        $html = self::html();
        self::assertSame(2, substr_count($html, '<span class="pct">50%</span>'));
        self::assertSame(10, substr_count($html, '<span class="pct">—</span>'));
        self::assertStringContainsString('small_cross_snapshot_delta', $html);
        self::assertStringContainsString('suppression_or_empty_denominator', $html);
        self::assertSame(1, substr_count($html, '</body>'));
    }
}
