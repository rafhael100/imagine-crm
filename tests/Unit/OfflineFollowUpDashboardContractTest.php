<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OfflineFollowUpDashboardContractTest extends TestCase
{
    private function html(): string
    {
        $content = file_get_contents(__DIR__.'/../../prototypes/f4-15-followup-dashboard/index.html');
        self::assertIsString($content);

        return $content;
    }

    public function test_prototype_has_explicit_fiction_and_offline_boundary(): void
    {
        $html = $this->html();
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertStringContainsString('default-src &#39;none&#39;', htmlspecialchars($html, ENT_QUOTES, 'UTF-8'));
    }

    public function test_has_three_keyboard_operable_period_controls(): void
    {
        $html = $this->html();
        self::assertSame(3, preg_match_all('/<button type="button" aria-pressed="(?:true|false)" data-period="[012]">/', $html));
        self::assertStringContainsString('button:focus-visible', $html);
        self::assertStringContainsString('aria-live="polite"', $html);
    }

    public function test_metrics_and_table_have_accessible_labels(): void
    {
        $html = $this->html();
        self::assertStringContainsString('aria-labelledby="title-counts"', $html);
        self::assertStringContainsString('<caption class="sr-only">', $html);
        self::assertStringContainsString('<th scope="col">', $html);
        self::assertStringContainsString('<th scope="row">', $html);
    }

    public function test_prototype_does_not_use_network_or_browser_persistence_apis(): void
    {
        $html = $this->html();
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage',
            'navigator.sendBeacon', '<form', '<iframe', '<script src=', '<link rel="stylesheet"'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
