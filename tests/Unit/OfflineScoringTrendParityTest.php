<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticScoreTrendPreview;
use PHPUnit\Framework\TestCase;

final class OfflineScoringTrendParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-113-scoring-trend/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    /** @return list<array<string,mixed>> */
    private static function cases(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $matches,
        ));
        $cases = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(4, $cases);

        return $cases;
    }

    public function test_four_displayed_trends_match_the_real_php_score_history_reducer(): void
    {
        $ids = [];
        $preview = new SyntheticScoreTrendPreview();
        foreach (self::cases() as $case) {
            self::assertSame(['id', 'samples', 'expected'], array_keys($case));
            self::assertContains($case['id'], ['growth', 'mixed', 'decline', 'empty']);
            self::assertArrayNotHasKey($case['id'], $ids);
            $ids[$case['id']] = true;
            self::assertLessThanOrEqual(16, count($case['samples']));

            $report = $preview->preview($case['samples']);
            self::assertSame([
                'first_score' => $report['first_score'],
                'last_score' => $report['last_score'],
                'score_delta' => $report['score_delta'],
                'first_band' => $report['first_band'],
                'last_band' => $report['last_band'],
                'upward_steps' => $report['upward_steps'],
                'downward_steps' => $report['downward_steps'],
                'unchanged_steps' => $report['unchanged_steps'],
                'band_changes' => $report['band_changes'],
            ], $case['expected']);
            self::assertSame(count($case['samples']), $report['sample_count']);
            self::assertSame(max(0, $report['sample_count'] - 1),
                $report['upward_steps'] + $report['downward_steps'] + $report['unchanged_steps']);
            foreach ([
                'band_policy_verified', 'history_verified', 'authorized',
                'can_rescore', 'can_notify', 'can_trigger_automation', 'can_persist',
            ] as $ability) {
                self::assertFalse($report[$ability]);
            }
        }
        self::assertSame(['growth', 'mixed', 'decline', 'empty'], array_keys($ids));
    }

    public function test_visualization_is_offline_keyboard_friendly_and_contains_no_customer_data(): void
    {
        $html = self::html();
        self::assertLessThanOrEqual(14000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(4, preg_match_all(
            '/<button type="button" data-case="(?:growth|mixed|decline|empty)" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.', "connect-src 'none'",
            "form-action 'none'", 'href="../index.html"',
            'role="status" aria-live="polite" aria-atomic="true"',
            'button:focus-visible', 'chart.replaceChildren();',
            "bar.style.height=item.score+'%';",
            'value.textContent=String(item.score);',
            'get(\'chart-wrap\').hidden=sample.samples.length===0;',
            "put('rights','Reclassificar: NÃO · Notificar: NÃO · Automatizar: NÃO · Gravar: NÃO');",
            '@media(max-width:390px)',
        ] as $needed) {
            self::assertStringContainsString($needed, $html);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', 'indexedDB', 'Date.now',
            'new Date()', '.innerHTML', '.outerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe', '<img',
            '<script src=', '<link ', 'onload=', 'onclick=', 'download=',
            'http://', 'https://', 'url(', '@import',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        $json = json_encode(self::cases(), JSON_THROW_ON_ERROR);
        foreach (['tenant', 'cnpj', 'email', 'phone', 'contact', 'name', 'lead_id', 'actor'] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/index.html');
    }
}
