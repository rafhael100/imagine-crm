<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OfflinePrototypeHubTest extends TestCase
{
    private static function hub(): string
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/prototypes/index.html');
        self::assertIsString($content);

        return $content;
    }

    public function test_hub_connects_exactly_ten_existing_offline_prototypes(): void
    {
        $html = self::hub();
        self::assertSame(10, substr_count($html, '<article class="card">'));
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        self::assertStringContainsString('Funil Kanban', $html);
        self::assertStringContainsString('Prévia de CSV', $html);
        self::assertStringContainsString('Trial e retenção', $html);
        self::assertStringContainsString('Itens de oportunidade', $html);
        self::assertStringContainsString('Evolução de scoring', $html);
        self::assertStringContainsString('Previsão de vendas', $html);
        self::assertStringContainsString('Ganhos e perdas', $html);
        self::assertStringContainsString('Jornada comercial integrada', $html);
        self::assertStringContainsString('Painel de funil com privacidade', $html);

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
        ], $links[1]);
        foreach (array_slice($links[1], 1) as $relative) {
            self::assertFileExists(dirname(__DIR__, 2).'/prototypes/'.$relative);
        }
    }

    public function test_no_network_capture_or_local_storage_is_possible(): void
    {
        $html = self::hub();
        foreach ([
            "default-src 'none'", "script-src 'none'",
            "img-src 'none'", "connect-src 'none'",
            "frame-src 'none'", "form-action 'none'",
            'base-uri '."'none'", 'referrer" content="no-referrer"',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'http://', 'https://', '//cdn.', '<form', '<input', '<textarea',
            '<iframe', '<script', '<img', '<link', 'fetch(', 'XMLHttpRequest',
            'WebSocket', 'EventSource', 'localStorage', 'sessionStorage',
            'indexedDB', 'onload=', 'onclick=', '@import', 'url(',
            'download=', '.innerHTML',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_mobile_and_keyboard_accessibility_structure_is_present(): void
    {
        $html = self::hub();
        self::assertStringContainsString('lang="pt-BR"', $html);
        self::assertStringContainsString('<main id="conteudo">', $html);
        self::assertStringContainsString('href="#conteudo"', $html);
        self::assertStringContainsString('a:focus-visible', $html);
        self::assertStringContainsString('@media(max-width:620px)', $html);
        self::assertStringContainsString('grid-template-columns:minmax(0,1fr)', $html);
        self::assertSame(1, substr_count($html, '</body>'));
    }
}
