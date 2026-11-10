<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticQuotaSnapshotComparison;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-REV-F5-03: UI fixtures compare anonymized, invented numerical scenarios.
 */
final class OfflinePrototypeQuotaComparisonTest extends TestCase
{
    public function test_four_static_comparisons_match_pure_php_without_authorization(): void
    {
        $beforeFixtures = [
            ['leads', 20, 4],
            ['users', 2, 2],
            ['automations', 0, 0],
            ['storage_bytes', 10, 12],
        ];
        $afterFixtures = [
            ['leads', 25, 9],
            ['users', 2, 3],
            ['automations', 3, 0],
            ['storage_bytes', 10, 7],
        ];
        $build = static function (array $fixtures): QuotaSnapshotReadout {
            return new QuotaSnapshotReadout(array_map(
                static fn (array $fixture): QuotaUsageProjection => new QuotaUsageProjection(
                    $fixture[0], $fixture[1], $fixture[2], 0,
                ),
                $fixtures,
            ));
        };
        $readout = (new SyntheticQuotaSnapshotComparison(
            $build($beforeFixtures),
            $build($afterFixtures),
        ))->compare();

        self::assertSame(4, $readout['meters']);
        self::assertSame(2, $readout['usage_increased']);
        self::assertSame(1, $readout['usage_decreased']);
        self::assertSame(1, $readout['usage_unchanged']);
        self::assertSame(2, $readout['limits_changed']);
        self::assertSame(3, $readout['bands_changed']);
        self::assertFalse($readout['authorized']);
        self::assertFalse($readout['can_charge']);
        self::assertFalse($readout['can_enforce']);
        self::assertFalse($readout['can_export']);

        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ($readout['rows'] as $row) {
            $snippet = sprintf(
                "earlierLimit:%d,earlierUsed:%d,laterLimit:%d,laterUsed:%d,earlierBand:'%s',laterBand:'%s'",
                $row['earlier_limit'],
                $row['earlier_used'],
                $row['later_limit'],
                $row['later_used'],
                $row['earlier_band']->value,
                $row['later_band']->value,
            );
            self::assertStringContainsString($snippet, $html);
        }
    }

    public function test_four_buttons_have_one_selected_state_and_one_live_status_region(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($doc);
        self::assertSame(1, $xpath->query('//section[@id="screen-dashboard"]//section[@data-quota-compare-demo]')->length);
        $buttons = $xpath->query('//section[@data-quota-compare-demo]//button[@data-quota-compare]');
        self::assertSame(4, $buttons->length);
        self::assertSame(['leads','users','automations','storage_bytes'], array_map(
            static fn (DOMElement $button): string => $button->getAttribute('data-quota-compare'),
            iterator_to_array($buttons),
        ));
        foreach ($buttons as $index => $button) {
            self::assertSame('button', $button->getAttribute('type'));
            self::assertSame($index === 0 ? 'true' : 'false', $button->getAttribute('aria-pressed'));
        }
        self::assertSame(
            1,
            $xpath->query('//section[@data-quota-compare-demo]//div[@id="quota-compare-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length,
        );
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_comparison_has_no_request_storage_export_or_billing_channel(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'function renderQuotaCompare()',
            'function showQuotaCompare(code)',
            'quotaComparePreview.replaceChildren();',
            "renderQuotaCompare();",
            'Nenhuma alteração de plano, autorização, exportação ou cobrança foi realizada.',
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
