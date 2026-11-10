<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticQuotaImpactPreview;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class OfflinePrototypeQuotaWhatIfTest extends TestCase
{
    public function test_four_fixed_increments_match_php_arithmetic_and_never_authorize(): void
    {
        foreach ([
            [0, 4, 16, 0, 'in_range'],
            [10, 14, 6, 0, 'in_range'],
            [16, 20, 0, 0, 'at_limit'],
            [17, 21, 0, 1, 'would_exceed'],
        ] as [$increase, $usedAfter, $remaining, $excess, $impact]) {
            $out = (new SyntheticQuotaImpactPreview([
                new QuotaUsageProjection('leads', 20, 4, $increase),
            ]))->preview();
            self::assertSame($increase, $out['rows'][0]['requested']);
            self::assertSame($usedAfter, $out['rows'][0]['projected']);
            self::assertSame($remaining, $out['rows'][0]['remaining_after']);
            self::assertSame($excess, $out['rows'][0]['excess_after']);
            self::assertSame($impact, $out['rows'][0]['impact']);
            self::assertSame(1, $out['impact_counts'][$impact]);
            self::assertFalse($out['authorized']);
            self::assertFalse($out['can_reserve']);
            self::assertFalse($out['can_increase']);
            self::assertFalse($out['can_charge']);
        }

        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'none:{requested:0,projected:4,remaining:16,excess:0,impact:\'in_range\'}',
            'ten:{requested:10,projected:14,remaining:6,excess:0,impact:\'in_range\'}',
            'sixteen:{requested:16,projected:20,remaining:0,excess:0,impact:\'at_limit\'}',
            'seventeen:{requested:17,projected:21,remaining:0,excess:1,impact:\'would_exceed\'}',
        ] as $fixture) {
            self::assertStringContainsString($fixture, $html);
        }
    }

    public function test_what_if_dashboard_has_four_accessible_mutually_exclusive_controls(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($dom);
        self::assertSame(1, $xpath->query('//section[@id="screen-dashboard"]//section[@data-quota-whatif-demo]')->length);
        $buttons = $xpath->query('//section[@data-quota-whatif-demo]//button[@data-quota-whatif]');
        self::assertSame(4, $buttons->length);
        $values = [];
        foreach ($buttons as $button) {
            self::assertSame('button', $button->getAttribute('type'));
            $values[] = $button->getAttribute('data-quota-whatif');
        }
        self::assertSame(['none', 'ten', 'sixteen', 'seventeen'], $values);
        self::assertSame('true', $buttons->item(0)->getAttribute('aria-pressed'));
        foreach ([1,2,3] as $i) {
            self::assertSame('false', $buttons->item($i)->getAttribute('aria-pressed'));
        }
        self::assertSame(1, $xpath->query('//section[@data-quota-whatif-demo]//div[@id="quota-whatif-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_no_request_form_mutation_billing_or_storage_was_introduced(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'function renderQuotaWhatIf()',
            'function showQuotaWhatIf(code)',
            "renderQuotaWhatIf();",
            'quotaWhatIfPreview.replaceChildren();',
            'Nenhum lead foi criado, nenhuma franquia foi reservada e nenhuma cobrança foi executada.',
            "connect-src 'none'",
            "form-action 'none'",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage', '<form', '<input', '<textarea'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
