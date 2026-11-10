<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticTrialQuotaOverview;
use App\Core\Plans\TrialWindowAdvisory;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-REV-F5-01: interactive labels are synthetic, not a business entitlement.
 */
final class OfflinePrototypeTrialQuotaOverviewTest extends TestCase
{
    private const START = 1000000;
    private const WINDOW = 14 * 86400;

    public function test_three_trial_phases_and_four_quota_bands_match_php_readout_without_authority(): void
    {
        $phpTrial = [
            'before_start' => self::START - 1,
            'within_window' => self::START + 11 * 86400,
            'after_window' => self::START + self::WINDOW,
        ];
        foreach (['before_start' => 14, 'within_window' => 3, 'after_window' => 0] as $phase => $days) {
            $overview = new SyntheticTrialQuotaOverview(
                new TrialWindowAdvisory(self::START, $phpTrial[$phase]),
                new QuotaSnapshotReadout([new QuotaUsageProjection('leads', 20, 4, 0)]),
            );
            $result = $overview->readout();
            self::assertSame($phase, $result['trial']['phase']);
            self::assertSame($days, $result['trial']['remaining_days_rounded_up']);
            self::assertFalse($result['authorized']);
            self::assertFalse($result['can_activate']);
            self::assertFalse($result['can_charge']);
            self::assertFalse($result['can_enforce']);
        }

        foreach ([
            ['in_range', 20, 4, 16, 0],
            ['at_limit', 20, 20, 0, 0],
            ['over_limit', 20, 21, 0, 1],
            ['zero_capacity', 0, 0, 0, 0],
        ] as [$band, $limit, $used, $remaining, $excess]) {
            $result = (new SyntheticTrialQuotaOverview(
                new TrialWindowAdvisory(self::START, self::START + 11 * 86400),
                new QuotaSnapshotReadout([new QuotaUsageProjection('leads', $limit, $used, 0)]),
            ))->readout();
            self::assertSame($band, $result['quotas'][0]['band']->value);
            self::assertSame($remaining, $result['quotas'][0]['remaining']);
            self::assertSame($excess, $result['quotas'][0]['excess']);
            self::assertSame(1, $result['quota_band_counts'][$band]);
            self::assertFalse($result['authorized']);
            self::assertFalse($result['can_activate']);
            self::assertFalse($result['can_charge']);
            self::assertFalse($result['can_enforce']);
        }
    }

    public function test_semantic_controls_live_status_and_original_quota_meters_remain_present(): void
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
        $xp = new DOMXPath($doc);
        self::assertSame(1, $xp->query('//section[@id="screen-dashboard"]//section[@data-trial-quota-demo]')->length);
        $phaseButtons = $xp->query('//section[@data-trial-quota-demo]//button[@data-trial-phase]');
        $quotaButtons = $xp->query('//section[@data-trial-quota-demo]//button[@data-trial-quota]');
        self::assertSame(3, $phaseButtons->length);
        self::assertSame(4, $quotaButtons->length);

        $actualPhases = [];
        $actualQuotas = [];
        foreach ($phaseButtons as $button) {
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'));
            $actualPhases[] = $button->getAttribute('data-trial-phase');
        }
        foreach ($quotaButtons as $button) {
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'));
            $actualQuotas[] = $button->getAttribute('data-trial-quota');
        }
        self::assertSame(['before', 'active', 'ended'], $actualPhases);
        self::assertSame(['in-range', 'at-limit', 'over-limit', 'zero-capacity'], $actualQuotas);
        self::assertSame('true', $phaseButtons->item(1)->getAttribute('aria-pressed'));
        self::assertSame('true', $quotaButtons->item(0)->getAttribute('aria-pressed'));
        self::assertSame(1, $xp->query('//section[@data-trial-quota-demo]//div[@id="trial-quota-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length);
        self::assertSame(3, $xp->query('//section[@id="screen-dashboard"]//div[@data-quota-fixture]/meter')->length);
        self::assertSame(9, $xp->query('//section[@data-screen]')->length);
    }

    public function test_no_trial_activation_billing_persistence_or_network_is_introduced(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'function renderTrialQuotaPreview()',
            'function showTrialPhase(code)',
            'function showTrialQuota(code)',
            'trialQuotaPreview.replaceChildren();',
            'renderTrialQuotaPreview();',
            'TRIAL E FRANQUIAS — DADOS INVENTADOS',
            'Nenhum acesso, bloqueio, cobrança, convite ou recurso foi alterado.',
            "connect-src 'none'",
            "form-action 'none'",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage', 'Date.now(', '<form', '<input', '<textarea'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
