<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticCommercialJourneyInspector;
use PHPUnit\Framework\TestCase;

final class OfflineCommercialJourneyParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-138-commercial-journey/index.html',
        );
        self::assertIsString($html);
        return $html;
    }

    /** @return list<array<string,mixed>> */
    private static function cases(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((.*?)\);\s*const LABELS=/s',
            self::html(),
            $match,
        ), 'The UI fixture JSON must be embedded exactly once.');
        $cases = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(2, $cases);
        return $cases;
    }

    public function test_both_real_browser_fixtures_match_five_core_modules_end_to_end(): void
    {
        $seen = [];
        $expectedFields = [
            'scoring_score', 'scoring_band', 'lead_assignment_slot',
            'opportunity_claimed_status', 'opportunity_claimed_win_count',
            'proposal_claimed_status', 'proposal_discounted_total_cents',
            'proposal_preflight_blockers', 'task_claimed_status',
            'task_due_phase', 'journey_warning_codes',
            'fictional_consistency_warnings', 'recognized_wins',
            'recognized_revenue_cents',
        ];
        foreach (self::cases() as $case) {
            self::assertSame(
                ['id', 'title', 'subtitle', 'input', 'expected'],
                array_keys($case),
            );
            self::assertContains($case['id'], ['consistent', 'conflict']);
            self::assertArrayNotHasKey($case['id'], $seen);
            $seen[$case['id']] = true;
            self::assertSame($expectedFields, array_keys($case['expected']));

            $real = (new SyntheticCommercialJourneyInspector())
                ->inspect($case['input']);
            foreach ($case['expected'] as $key => $value) {
                self::assertArrayHasKey($key, $real);
                self::assertSame($value, $real[$key], $case['id'].'.'.$key);
            }
            self::assertFalse($real['authorized']);
            self::assertTrue($real['informational_only']);
            foreach ($real as $key => $value) {
                if ($key === 'authorized'
                    || str_starts_with($key, 'can_')
                    || str_ends_with($key, '_verified')) {
                    self::assertFalse($value, $case['id'].'.'.$key);
                }
            }
        }
        self::assertSame(['consistent', 'conflict'], array_keys($seen));
    }

    public function test_a_conflict_never_creates_a_verified_sale_and_good_case_is_still_denied(): void
    {
        $cases = self::cases();
        self::assertSame(0, $cases[0]['expected']['fictional_consistency_warnings']);
        self::assertSame([], $cases[0]['expected']['journey_warning_codes']);
        self::assertSame(4, $cases[1]['expected']['fictional_consistency_warnings']);
        self::assertCount(4, $cases[1]['expected']['journey_warning_codes']);
        foreach ($cases as $case) {
            $report = (new SyntheticCommercialJourneyInspector())
                ->inspect($case['input']);
            self::assertSame('won', $report['opportunity_claimed_status']);
            self::assertSame(0, $report['recognized_wins']);
            self::assertSame(0, $report['recognized_revenue_cents']);
            self::assertContains(
                'sender_identity_unverified',
                $report['proposal_preflight_blockers'],
            );
            self::assertFalse($report['can_send_proposal']);
            self::assertFalse($report['can_charge']);
            self::assertFalse($report['can_persist']);
        }
    }

    public function test_page_is_self_contained_keyboard_accessible_and_read_only(): void
    {
        $html = self::html();
        self::assertLessThan(18000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(2, preg_match_all(
            '/<button type="button" data-case="(?:consistent|conflict)" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.',
            "connect-src 'none'",
            "form-action 'none'",
            'aria-live="polite"',
            'button:focus-visible',
            'href="../index.html"',
            '@media(max-width:390px)',
            "warnings.replaceChildren()",
            "text('recognized',currency(r.recognized_revenue_cents))",
            'Criar lead: NÃO · Atribuir: NÃO · Mover: NÃO · Enviar: NÃO · Cobrar: NÃO · Gravar: NÃO',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', 'indexedDB', 'Date.now',
            '.innerHTML', '.outerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe', '<img',
            '<script src=', '<link ', 'onload=', 'onclick=', 'download=',
            'http://', 'https://', 'url(', '@import',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/index.html');

        $json = json_encode(self::cases(), JSON_THROW_ON_ERROR);
        foreach ([
            'tenant', 'customer', 'email', 'phone', 'user_id',
            'lead_id', 'opportunity_id', 'proposal_id', 'task_id',
            'payment_token', 'signature',
        ] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
    }
}
