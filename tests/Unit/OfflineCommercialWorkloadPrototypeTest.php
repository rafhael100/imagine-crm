<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticCommercialWorkloadInspector;
use App\Core\Sales\SyntheticCommercialWorkloadTrendReview;
use App\Core\Sales\SyntheticSalesTriageWorkQueueInspector;
use PHPUnit\Framework\TestCase;

final class OfflineCommercialWorkloadPrototypeTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-155-commercial-workload/index.html',
        );
        self::assertIsString($html);
        return $html;
    }

    public function test_four_offline_scenarios_are_accessible_and_every_action_denied(): void
    {
        $html = self::html();
        foreach (['pressure','balanced','trend','triage'] as $key) {
            self::assertStringContainsString('id="'.$key.'"', $html);
            self::assertStringContainsString('for="'.$key.'"', $html);
        }
        self::assertSame(4, substr_count($html, 'type="radio"'));
        self::assertSame(4, substr_count($html, 'Atribuir: bloqueado'));
        self::assertSame(4, substr_count($html, 'Enviar aviso: bloqueado'));
        self::assertSame(4, substr_count($html, 'Gravar: bloqueado'));
        self::assertStringContainsString('fictional_lead_capacity_shortfall', $html);
        self::assertStringContainsString('fictional_overdue_tasks', $html);
        self::assertStringContainsString('cross_module_identity_unverified', $html);
        self::assertStringContainsString('href="../index.html"', $html);
        self::assertStringContainsString('#triage:focus-visible~.tabs label[for=triage]', $html);
    }

    public function test_csp_offline_and_mobile_protections_are_present(): void
    {
        $html = self::html();
        foreach (["default-src 'none'", "script-src 'none'",
            "connect-src 'none'", "form-action 'none'",
            "img-src 'none'", "frame-src 'none'"] as $rule) {
            self::assertStringContainsString($rule, $html);
        }
        foreach (['<script', '<form', '<iframe', '<img', 'http://',
            'https://', 'fetch(', 'XMLHttpRequest', 'localStorage',
            'sessionStorage', 'indexedDB', 'onclick=', 'download=',
            'onload=', '@import', 'url('] as $bad) {
            self::assertStringNotContainsString($bad, $html);
        }
        self::assertStringContainsString('@media(max-width:500px)', $html);
        self::assertSame(1, substr_count($html, '</body>'));
    }

    public function test_fictional_capacity_warning_codes_match_real_php_core(): void
    {
        $scenario = [
            'agents' => [[
                'enabled'=>true,'has_required_skill'=>true,
                'open_leads'=>0,'capacity'=>1,
            ]],
            'lead_states' => ['unassigned','unassigned','engaged'],
            'start_cursor' => 0,
            'task_histories' => [[
                [
                    'revision'=>1,'action'=>'create',
                    'occurred_at_utc'=>'2026-11-01T10:00:00Z',
                    'due_at_utc'=>'2026-11-09T12:00:00Z',
                    'priority'=>'high','reason_code'=>null,
                ],
            ],[
                [
                    'revision'=>1,'action'=>'create',
                    'occurred_at_utc'=>'2026-11-01T10:00:00Z',
                    'due_at_utc'=>'2026-11-10T12:00:00Z',
                    'priority'=>'normal','reason_code'=>null,
                ],
            ]],
            'observed_at_utc'=>'2026-11-10T12:00:00Z',
        ];
        $out = (new SyntheticCommercialWorkloadInspector())->inspect($scenario);
        self::assertSame(1, $out['fictional_assignments_planned']);
        self::assertSame(1, $out['fictional_leads_waiting_for_capacity']);
        self::assertSame(1, $out['fictional_overdue_tasks']);
        self::assertSame(1, $out['fictional_due_now_tasks']);
        foreach ($out['workload_warning_codes'] as $code) {
            self::assertStringContainsString($code, self::html());
        }
        self::assertFalse($out['authorized']);
        self::assertFalse($out['can_notify']);
    }

    public function test_visual_improvement_matches_real_php_trend_without_permissions(): void
    {
        $earlier = [
            'agents' => [[
                'enabled'=>true,'has_required_skill'=>true,
                'open_leads'=>0,'capacity'=>0,
            ]],
            'lead_states' => ['unassigned','unassigned'],
            'start_cursor' => 0,
            'task_histories' => [[[
                'revision'=>1,'action'=>'create',
                'occurred_at_utc'=>'2026-11-01T10:00:00Z',
                'due_at_utc'=>'2026-11-09T12:00:00Z',
                'priority'=>'high','reason_code'=>null,
            ]]],
            'observed_at_utc'=>'2026-11-10T12:00:00Z',
        ];
        $later = $earlier;
        $later['agents'][0]['capacity'] = 2;
        $later['task_histories'][0][0]['due_at_utc'] = '2026-11-11T12:00:00Z';
        $result = (new SyntheticCommercialWorkloadTrendReview())->inspect($earlier, $later);
        self::assertSame(-2, $result['fictional_capacity_shortfall_delta']);
        self::assertSame(-1, $result['fictional_overdue_task_delta']);
        self::assertSame('reduced_pressure', $result['pressure_trend']);
        foreach ($result['warning_codes'] as $code) {
            self::assertStringContainsString($code, self::html());
        }
        foreach (['authorized','can_assign','can_reschedule','can_notify','can_persist'] as $field) {
            self::assertFalse($result[$field]);
        }
    }

    public function test_fourth_tab_matches_prioritized_php_leads_and_task_positions(): void
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

        $task = static fn (string $due, string $priority): array => [[
            'revision' => 1, 'action' => 'create',
            'occurred_at_utc' => '2026-11-01T10:00:00Z',
            'due_at_utc' => $due, 'priority' => $priority,
            'reason_code' => null,
        ]];
        $result = (new SyntheticSalesTriageWorkQueueInspector())->inspect([
            'lead_batch' => [
                'agents' => [
                    ['enabled' => true,'has_required_skill' => true,'open_leads' => 0,'capacity' => 1],
                    ['enabled' => true,'has_required_skill' => true,'open_leads' => 0,'capacity' => 1],
                ],
                'leads' => [
                    ['state' => 'unassigned', 'signals' => $profile],
                    ['state' => 'unassigned', 'signals' => $quote],
                    ['state' => 'unassigned', 'signals' => $stale],
                    ['state' => 'engaged', 'signals' => $quote],
                ],
                'weights' => [
                    'profile_complete' => 20, 'recent_engagement' => 10,
                    'requested_quote' => 30, 'scheduled_demo' => 25,
                    'stale_response' => -20,
                ],
                'base_score' => 20,
                'start_cursor' => 0,
            ],
            'task_histories' => [
                $task('2026-11-09T12:00:00Z', 'high'),
                $task('2026-11-11T12:00:00Z', 'urgent'),
                $task('2026-11-10T12:00:00Z', 'low'),
            ],
            'observed_at_utc' => '2026-11-10T12:00:00Z',
        ]);
        self::assertSame([1, 0, 2], $result['lead_priority_positions']);
        self::assertSame([0, 2, 1], $result['task_priority_positions']);
        self::assertSame(2, $result['fictional_planned_assignments']);
        self::assertSame(1, $result['fictional_waiting_leads']);
        self::assertSame(1, $result['fictional_overdue_tasks']);
        self::assertSame(1, $result['fictional_due_now_tasks']);
        $html = self::html();
        self::assertStringContainsString('posição 2, posição 1, posição 3', $html);
        self::assertStringContainsString('posição 1, posição 3, posição 2', $html);
        foreach ($result['warning_codes'] as $code) {
            self::assertStringContainsString($code, $html);
        }
        self::assertFalse($result['authorized']);
        self::assertFalse($result['can_assign']);
        self::assertFalse($result['can_notify']);
    }
}
