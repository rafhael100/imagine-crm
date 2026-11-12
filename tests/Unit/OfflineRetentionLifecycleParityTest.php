<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\SyntheticCancellationRetentionTimeline;
use App\Core\Plans\SyntheticTrialRetentionTimeline;
use App\Core\Plans\TrialWindowAdvisory;
use PHPUnit\Framework\TestCase;

final class OfflineRetentionLifecycleParityTest extends TestCase
{
    private static function html(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-104-retention-lifecycle/index.html',
        );
        self::assertIsString($source);

        return $source;
    }

    /**
     * @return list<array{
     *   id:string,kind:string,start:int,now:int,phase:string,
     *   ends_at:int,remaining_seconds:int,days:int
     * }>
     */
    private static function cases(): array
    {
        $html = self::html();
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            $html,
            $matches,
        ));
        $cases = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertCount(9, $cases);
        self::assertTrue(array_is_list($cases));

        return $cases;
    }

    public function test_nine_browser_scenarios_match_php_trial_and_cancellation_core_exactly(): void
    {
        $seen = [];
        $trials = 0;
        $cancellations = 0;

        foreach (self::cases() as $case) {
            self::assertSame(
                ['id', 'kind', 'start', 'now', 'phase', 'ends_at', 'remaining_seconds', 'days'],
                array_keys($case),
            );
            self::assertMatchesRegularExpression('/\A(?:trial|cancel)-[a-z]+\z/D', $case['id']);
            self::assertArrayNotHasKey($case['id'], $seen);
            $seen[$case['id']] = true;

            if ($case['kind'] === 'trial') {
                ++$trials;
                $model = new SyntheticTrialRetentionTimeline(
                    new TrialWindowAdvisory($case['start'], $case['now']),
                );
                $result = $model->readout();
                self::assertSame($result['review_phase'], $case['phase']);
                self::assertSame($result['review_window_ends_at_epoch'], $case['ends_at']);
                self::assertSame($result['remaining_review_seconds'], $case['remaining_seconds']);
                self::assertSame($result['remaining_review_days_rounded_up'], $case['days']);
                foreach (['can_delete_data', 'can_restore_data', 'can_grant_access', 'can_charge'] as $capability) {
                    self::assertFalse($result[$capability]);
                }
            } elseif ($case['kind'] === 'cancel') {
                ++$cancellations;
                $result = (new SyntheticCancellationRetentionTimeline(
                    $case['start'], $case['now'],
                ))->readout();
                self::assertSame($result['phase'], $case['phase']);
                self::assertSame($result['window_ends_at_epoch'], $case['ends_at']);
                self::assertSame($result['remaining_seconds'], $case['remaining_seconds']);
                self::assertSame($result['remaining_days_rounded_up'], $case['days']);
                foreach (['can_delete_customer_data', 'can_restore_data', 'can_reactivate_service', 'can_grant_access', 'can_charge'] as $capability) {
                    self::assertFalse($result[$capability]);
                }
            } else {
                self::fail('Unknown synthetic lifecycle type.');
            }
        }

        self::assertSame(5, $trials);
        self::assertSame(4, $cancellations);
    }

    public function test_offline_page_never_captures_personal_data_or_uses_live_clock(): void
    {
        $html = self::html();
        self::assertLessThanOrEqual(16000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(9, preg_match_all('/<button type="button" data-case="[^"]+" aria-pressed="(?:true|false)">/', $html));
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        self::assertStringContainsString('id="denials"', $html);
        self::assertStringContainsString('id="status" class="state" role="status" aria-live="polite"', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        self::assertStringContainsString('button:focus-visible', $html);
        self::assertStringContainsString('textContent=String(value)', $html);
        self::assertStringContainsString('@media(max-width:390px)', $html);
        self::assertStringContainsString('href="../index.html"', $html);
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/index.html');

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

        foreach (self::cases() as $case) {
            self::assertSame(1, substr_count($html, 'data-case="'.$case['id'].'"'));
        }
    }
}
