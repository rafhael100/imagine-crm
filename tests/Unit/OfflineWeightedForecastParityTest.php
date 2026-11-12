<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticWeightedSalesForecast;
use PHPUnit\Framework\TestCase;

final class OfflineWeightedForecastParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-115-weighted-forecast/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    /** @return list<array<string,mixed>> */
    private static function cases(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(), $m,
        ));
        $cases = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(4, $cases);

        return $cases;
    }

    public function test_all_offline_pipeline_scenarios_and_adjustable_probabilities_match_php(): void
    {
        $calculator = new SyntheticWeightedSalesForecast();
        $seen = [];
        foreach (self::cases() as $case) {
            self::assertSame(['id', 'opportunities', 'expected_raw_cents', 'expected_weighted_cents'], array_keys($case));
            self::assertContains($case['id'], ['standard', 'conservative', 'optimistic', 'empty']);
            self::assertArrayNotHasKey($case['id'], $seen);
            $seen[$case['id']] = true;
            $entries = [];
            foreach ($case['opportunities'] as $item) {
                self::assertSame(['label', 'amount_cents', 'win_probability_bp'], array_keys($item));
                self::assertMatchesRegularExpression('/\ANegócio fictício [A-G]\z/uD', $item['label']);
                $entries[] = [
                    'amount_cents' => $item['amount_cents'],
                    'win_probability_bp' => $item['win_probability_bp'],
                ];
            }

            $report = $calculator->preview($entries);
            self::assertSame($case['expected_raw_cents'], $report['unweighted_total_cents']);
            self::assertSame($case['expected_weighted_cents'], $report['weighted_total_cents']);
            self::assertSame(count($entries), $report['opportunity_count']);
            self::assertSame(array_sum($report['weighted_line_cents']), $report['weighted_total_cents']);
            self::assertFalse($report['probabilities_verified']);
            self::assertFalse($report['forecast_verified']);
            foreach (['authorized', 'can_mark_won', 'can_invoice', 'can_charge', 'can_persist'] as $denied) {
                self::assertFalse($report[$denied]);
            }

            // Exercise every 5%-step probability the browser can reach.
            foreach ($entries as $i => $entry) {
                for ($bp = 0; $bp <= 10000; $bp += 500) {
                    $modified = $entries;
                    $modified[$i]['win_probability_bp'] = $bp;
                    $preview = $calculator->preview($modified);
                    self::assertLessThanOrEqual($preview['unweighted_total_cents'], $preview['weighted_total_cents']);
                    self::assertSame(array_sum($preview['weighted_line_cents']), $preview['weighted_total_cents']);
                }
            }
        }
        self::assertSame(['standard', 'conservative', 'optimistic', 'empty'], array_keys($seen));
    }

    public function test_offline_forecast_never_collects_customers_or_grants_sales_rights(): void
    {
        $html = self::html();
        self::assertLessThanOrEqual(15000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(4, preg_match_all(
            '/<button type="button" data-case="(?:standard|conservative|optimistic|empty)" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.', "connect-src 'none'",
            "form-action 'none'", 'href="../index.html"',
            'button:focus-visible', 'aria-live="polite"',
            'body.replaceChildren();', 'label.textContent=item.label;',
            'BigInt(a)*BigInt(bp)', 'same.focus()', 'opposite.focus()',
            '@media(max-width:390px)',
            "put('rights','Marcar ganha: NÃO · Faturar: NÃO · Cobrar: NÃO · Gravar: NÃO');",
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
        foreach (['tenant', 'cnpj', 'email', 'phone', 'opportunity_id', 'invoice', 'customer'] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/index.html');
    }
}
