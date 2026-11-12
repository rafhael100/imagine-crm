<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticOpportunityOutcomeInspector;
use PHPUnit\Framework\TestCase;

final class OfflineOpportunityOutcomesParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-117-opportunity-outcomes/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    /** @return list<array<string,mixed>> */
    private static function cases(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(), $matches,
        ));
        $cases = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(4, $cases);

        return $cases;
    }

    public function test_four_fictional_outcome_scenarios_reconcile_with_core_php_exactly(): void
    {
        $model = new SyntheticOpportunityOutcomeInspector();
        $seen = [];
        foreach (self::cases() as $case) {
            self::assertSame(['id', 'records', 'expected'], array_keys($case));
            self::assertContains($case['id'], ['mixed', 'losses', 'wins', 'empty']);
            self::assertArrayNotHasKey($case['id'], $seen);
            $seen[$case['id']] = true;
            foreach ($case['records'] as $record) {
                self::assertSame(['outcome', 'loss_reason_code'], array_keys($record));
            }
            $report = $model->inspect($case['records']);
            self::assertSame($case['records'] === [] ? 0 : count($case['records']), $report['samples']);
            self::assertSame(array_merge(
                $report['claimed_outcome_counts'], $report['claimed_lost_by_reason'],
            ), $case['expected']);
            self::assertSame($report['claimed_outcome_counts']['lost'], array_sum($report['claimed_lost_by_reason']));
            self::assertSame(0, $report['recognized_wins']);
            self::assertTrue($report['loss_reason_required']);
            self::assertTrue($report['informational_only']);
            foreach ([
                'outcomes_verified', 'loss_reason_policy_verified', 'authorized',
                'can_mark_won', 'can_mark_lost', 'can_reopen',
                'can_count_revenue', 'can_notify', 'can_persist',
            ] as $flag) {
                self::assertFalse($report[$flag]);
            }
        }
        self::assertSame(['mixed', 'losses', 'wins', 'empty'], array_keys($seen));
    }

    public function test_ui_cannot_capture_identifiers_or_confirm_real_sales(): void
    {
        $html = self::html();
        self::assertLessThanOrEqual(13000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(4, preg_match_all(
            '/<button type="button" data-case="(?:mixed|losses|wins|empty)" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.', "connect-src 'none'",
            "form-action 'none'", 'href="../index.html"',
            'button:focus-visible', 'aria-live="polite"',
            'table.replaceChildren();', 'name.textContent=REASONS[reason];',
            'Vendas reconhecidas: 0 · Resultados verificados: NÃO',
            "put('rights','Marcar ganha: NÃO · Marcar perdida: NÃO · Reabrir: NÃO · Contar receita: NÃO · Gravar: NÃO');",
            '@media(max-width:390px)',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }

        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', 'indexedDB', 'Date.now',
            'new Date()', '.innerHTML', '.outerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe', '<img', '<script src=',
            '<link ', 'onload=', 'onclick=', 'download=', 'http://', 'https://',
            'url(', '@import',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }

        $json = json_encode(self::cases(), JSON_THROW_ON_ERROR);
        foreach (['tenant', 'cnpj', 'email', 'phone', 'customer', 'lead_id', 'opportunity_id', 'revenue'] as $key) {
            self::assertStringNotContainsString('"'.$key.'"', $json);
        }
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/index.html');
    }
}
