<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadPriorityCapacityBatchPreview;
use PHPUnit\Framework\TestCase;

final class OfflineInteractiveLeadTriageTest extends TestCase
{
    private static function page(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-164-interactive-lead-triage/index.html',
        );
        self::assertIsString($html);
        return $html;
    }

    public function test_local_inputs_reset_outputs_and_accessibility_are_present(): void
    {
        $html = self::page();
        self::assertSame(2, substr_count($html, 'type="range"'));
        foreach ([
            'for="capacity0"', 'for="capacity1"',
            'min="0" max="4" step="1" value="1"',
            'id="reset"', 'id="ranking"', 'id="assigned"',
            'id="waiting"', 'id="metric-planned"',
            'id="metric-waiting"', 'role="status" aria-live="polite"',
            'button:focus-visible', 'input:focus-visible',
            'href="../index.html"', 'PROTÓTIPO — DADOS FICTÍCIOS.',
        ] as $expected) {
            self::assertStringContainsString($expected, $html);
        }
        self::assertStringContainsString('@media(max-width:440px)', $html);
        self::assertStringContainsString('Atribuir: bloqueado', $html);
        self::assertStringContainsString('Notificar: bloqueado', $html);
        self::assertStringContainsString('Gravar: bloqueado', $html);
    }

    public function test_csp_and_static_source_never_connect_or_persist(): void
    {
        $html = self::page();
        foreach ([
            "default-src 'none'", "style-src 'unsafe-inline'",
            "script-src 'unsafe-inline'", "connect-src 'none'",
            "img-src 'none'", "form-action 'none'",
            "frame-src 'none'", "object-src 'none'",
        ] as $rule) {
            self::assertStringContainsString($rule, $html);
        }
        foreach ([
            '<form', '<iframe', '<img', 'http://', 'https://', 'fetch(',
            'XMLHttpRequest', 'WebSocket', 'EventSource', 'sendBeacon',
            'localStorage', 'sessionStorage', 'indexedDB',
            'eval(', 'new Function(', 'onload=', 'onclick=', 'download=',
            '.innerHTML', 'document.cookie',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertSame(1, substr_count($html, '</body>'));
    }

    public function test_default_score_priority_and_capacity_match_actual_core(): void
    {
        $signals = [
            'profile_complete' => false, 'recent_engagement' => false,
            'requested_quote' => false, 'scheduled_demo' => false,
            'stale_response' => false,
        ];
        $profile = $signals;
        $profile['profile_complete'] = true;
        $quote = $signals;
        $quote['requested_quote'] = true;
        $stale = $signals;
        $stale['stale_response'] = true;

        $scenario = [
            'agents' => [
                ['enabled' => true, 'has_required_skill' => true,
                    'open_leads' => 0, 'capacity' => 1],
                ['enabled' => true, 'has_required_skill' => true,
                    'open_leads' => 0, 'capacity' => 1],
            ],
            'leads' => [
                ['state' => 'unassigned', 'signals' => $profile],
                ['state' => 'unassigned', 'signals' => $quote],
                ['state' => 'unassigned', 'signals' => $quote],
                ['state' => 'engaged', 'signals' => $quote],
                ['state' => 'unassigned', 'signals' => $stale],
            ],
            'weights' => [
                'profile_complete' => 20, 'recent_engagement' => 10,
                'requested_quote' => 30, 'scheduled_demo' => 25,
                'stale_response' => -20,
            ],
            'base_score' => 20,
            'start_cursor' => 1,
        ];

        $report = (new SyntheticLeadPriorityCapacityBatchPreview())->preview($scenario);
        self::assertSame([1, 2, 0, 4], $report['ranked_unassigned_positions']);
        self::assertSame([
            ['lead_position' => 1, 'agent_slot' => 1],
            ['lead_position' => 2, 'agent_slot' => 0],
        ], $report['planned_slot_pairs']);
        self::assertSame([0, 4], $report['waiting_lead_positions']);
        self::assertSame(2, $report['planned_count']);
        self::assertSame(2, $report['waiting_count']);
        self::assertFalse($report['can_assign']);
        self::assertFalse($report['authorized']);

        $html = self::page();
        self::assertStringContainsString('2 → 3 → 1 → 5', $html);
        self::assertStringContainsString('let cursor=1;', $html);
        self::assertStringContainsString('Posição 2 → vaga fictícia 2', $html);
        self::assertStringContainsString('Posição 3 → vaga fictícia 1', $html);
    }
}
