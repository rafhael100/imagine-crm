<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvReviewBundleV1;
use PHPUnit\Framework\TestCase;

final class OfflineCsvReviewBoardTest extends TestCase
{
    private static function html(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-225-csv-review-board/index.html',
        );
        self::assertIsString($source);
        return $source;
    }

    public function test_exact_offline_fixture_metrics_match_real_php_core_bundle(): void
    {
        $report = (new PortableCsvReviewBundleV1())->build(
            "name;notes;ignored\r\nDemoA;ok;\u{202E}fictional\r\nDemoA;ok;other\r\n",
            'semicolon',
            [
                ['source_position' => 0, 'target_field' => 'name'],
                ['source_position' => 1, 'target_field' => 'notes'],
            ],
            true,
            ['name', 'email'],
        );

        $html = self::html();
        $expected = [
            'data_rows' => $report['structural_review']['counts']['data_rows'],
            'projection_collision_extra_rows' => $report['structural_review']['counts']['projection_collision_extra_rows'],
            'source_bidi_control_cells' => $report['control_signals']['counts']['source_bidi_control_cells'],
            'unmapped_claimed_required_fields' => $report['structural_review']['counts']['unmapped_claimed_required_fields'],
        ];
        foreach ($expected as $metric => $value) {
            self::assertSame(1, substr_count(
                $html,
                '<output data-metric="'.$metric.'">'.(string) $value.'</output>',
            ), $metric);
        }
        self::assertSame(1, $report['control_signals']['counts']['source_bidi_control_cells']);
        self::assertFalse($report['authorized']);
        self::assertFalse($report['can_import']);
        self::assertFalse($report['can_export']);
    }

    public function test_brand_uses_exact_original_node_portal_png(): void
    {
        $html = self::html();
        self::assertSame(1, preg_match(
            '/<img class="brand-logo" alt="Imagine" width="160" height="44" src="data:image\/png;base64,([A-Za-z0-9+\/=]+)">/',
            $html, $match,
        ));
        $bytes = base64_decode($match[1], true);
        self::assertIsString($bytes);
        self::assertSame(
            '678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77',
            sha1('blob '.strlen($bytes)."\0".$bytes),
        );
        self::assertSame(1, substr_count($html, '<img'));
        foreach (['--orange:#f9941d','--ink:#171b19','--paper:#f7f7f8'] as $color) {
            self::assertStringContainsString($color, $html);
        }
    }

    public function test_local_board_is_accessible_non_operational_and_does_not_leak_fixtures(): void
    {
        $html = self::html();
        foreach ([
            "default-src 'none'", "script-src 'none'", "connect-src 'none'",
            "form-action 'none'", "img-src data:", "base-uri 'none'",
            'referrer" content="no-referrer"', 'href="#conteudo"',
            '<main id="conteudo">', 'aria-label=', '<h1>',
            'REVISÃO SOMENTE', 'NO-GO OPERACIONAL',
            'Nenhum arquivo é carregado', 'nenhuma alteração é executada',
            '@media(max-width:480px)', '@media(max-width:900px)',
            '@media(prefers-reduced-motion:reduce)',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            '<script', '<form', '<input', '<textarea', '<select', '<button',
            '<iframe', '<link', 'http://', 'https://', '@import',
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage',
            'sessionStorage', 'indexedDB', 'download=', 'onclick=',
            'DemoA', 'fictional', 'other\r\n',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertLessThan(30000, strlen($html));
    }
}
