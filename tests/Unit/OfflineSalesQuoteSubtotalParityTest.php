<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticLineItemSubtotalPreview;
use PHPUnit\Framework\TestCase;

final class OfflineSalesQuoteSubtotalParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-109-sales-quote-subtotal/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    /** @return list<array<string, mixed>> */
    private static function scenarios(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $match,
        ));
        $cases = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(3, $cases);

        return $cases;
    }

    public function test_fictitious_ui_subtotals_match_real_php_calculator_at_all_example_quantities(): void
    {
        $model = new SyntheticLineItemSubtotalPreview();
        $ids = [];
        $scenarios = self::scenarios();

        foreach ($scenarios as $case) {
            self::assertSame(['id', 'lines', 'subtotal_cents'], array_keys($case));
            self::assertContains($case['id'], ['basic', 'compact', 'empty']);
            self::assertArrayNotHasKey($case['id'], $ids);
            $ids[$case['id']] = true;
            $lines = [];
            foreach ($case['lines'] as $item) {
                self::assertSame(['label', 'quantity', 'unit_price_cents'], array_keys($item));
                self::assertMatchesRegularExpression('/\A(?:Serviço fictício|Cortesia fictícia|Item demonstrativo)/u', $item['label']);
                $lines[] = [
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $item['unit_price_cents'],
                ];
            }

            $preview = $model->preview($lines);
            self::assertSame($case['subtotal_cents'], $preview['subtotal_cents']);
            self::assertSame(count($case['lines']), $preview['line_count']);
            self::assertSame(array_sum($preview['line_subtotals_cents']), $preview['subtotal_cents']);
            self::assertFalse($preview['can_charge']);
            self::assertFalse($preview['can_issue_proposal']);
            self::assertFalse($preview['can_persist']);

            // Exercise each quantity that the browser can reach via buttons.
            foreach ($lines as $index => $item) {
                for ($quantity = 1; $quantity <= 10; ++$quantity) {
                    $changed = $lines;
                    $changed[$index]['quantity'] = $quantity;
                    $result = $model->preview($changed);
                    self::assertSame(
                        $result['line_subtotals_cents'][$index],
                        $quantity * $item['unit_price_cents'],
                    );
                    self::assertSame(array_sum($result['line_subtotals_cents']), $result['subtotal_cents']);
                }
            }
        }
        self::assertSame(['basic', 'compact', 'empty'], array_keys($ids));
        self::assertSame(0, $model->preview([])['subtotal_cents']);
    }

    public function test_no_real_price_contact_persistence_or_html_injection_channel_exists(): void
    {
        $html = self::html();
        self::assertLessThanOrEqual(15000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(3, preg_match_all(
            '/<button type="button" data-case="(?:basic|compact|empty)" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.', "connect-src 'none'",
            'href="../f3-111-proposal-versions/index.html"',
            "form-action 'none'", 'href="../index.html"',
            'role="status" aria-live="polite"', 'button:focus-visible',
            "body.replaceChildren();", "label.textContent=line.label",
            'line.quantity+=delta;', 'same.focus()', 'opposite.focus()',
            'Number.MAX_SAFE_INTEGER', 'Math.trunc(cents/100)',
            "put('rights','Emitir proposta: NÃO · Aceitar proposta: NÃO · Cobrar: NÃO · Gravar: NÃO');",
            '@media(max-width:390px)',
        ] as $required) {
            self::assertStringContainsString($required, $html);
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
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/index.html');
    }
}
