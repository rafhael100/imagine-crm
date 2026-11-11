<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvReviewRaceFixture;
use PHPUnit\Framework\TestCase;

final class OfflineCsvReviewRaceParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html');
        self::assertIsString($html);

        return $html;
    }

    private static function reports(): array
    {
        self::assertSame(1, preg_match(
            '/const RACE_REPORTS = Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $matches,
        ));
        $reports = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($reports);
        self::assertCount(6, $reports);

        return $reports;
    }

    private static function inventedReports(): array
    {
        $rows = [
            ['id', 'note'],
            ['fiction-1', 'safe'],
            ['fiction-2', '=1+1'],
        ];
        $mapping = [
            ['column' => 1, 'target' => 'fixture_id'],
            ['column' => 2, 'target' => 'fixture_note'],
        ];
        $fixture = new CsvReviewRaceFixture($rows, $mapping);
        $marker = $fixture->marker();
        $clean = [['row' => 2, 'status' => 'fixture_reviewed_no_findings']];

        $actual = [
            $fixture->summary(),
            $fixture->submit(1, $marker, 'fixture_key_a', $clean),
            $fixture->submit(1, $marker, 'fixture_key_a', $clean),
            $fixture->submit(1, $marker, 'fixture_key_a', [
                ['row' => 2, 'status' => 'fixture_needs_correction'],
            ]),
            $fixture->submit(1, $marker, 'fixture_key_b', [
                ['row' => 3, 'status' => 'fixture_needs_correction'],
            ]),
        ];

        $rows[1][1] = 'modified invented content';
        $actual[] = $fixture->replaceFixture(2, $rows, $mapping);

        return $actual;
    }

    public function test_six_static_browser_scenarios_match_php_state_transitions_exactly(): void
    {
        self::assertSame(self::inventedReports(), self::reports());
    }

    public function test_idempotent_retry_and_competing_request_do_not_increase_event_count(): void
    {
        $r = self::reports();
        self::assertSame(1, $r[1]['recorded_review_events']);
        self::assertSame('fixture_idempotent_replay_no_op', $r[2]['status']);
        self::assertSame('fixture_idempotency_conflict_no_op', $r[3]['status']);
        self::assertSame('fixture_stale_no_op', $r[4]['status']);
        foreach ([2, 3, 4] as $index) {
            self::assertSame(1, $r[$index]['recorded_review_events']);
            self::assertSame(2, $r[$index]['fixture_version']);
        }
    }

    public function test_changed_fixture_clears_mock_event_count_but_not_human_gate(): void
    {
        $r = self::reports()[5];
        self::assertSame('fixture_replaced_review_reset', $r['status']);
        self::assertSame(3, $r['fixture_version']);
        self::assertSame(0, $r['recorded_review_events']);
        self::assertSame(0, $r['last_decision_count']);
        self::assertFalse($r['human_verified']);
        self::assertTrue($r['review_required']);
        self::assertFalse($r['import_permitted']);
    }

    public function test_no_scenario_is_a_permission_or_identity_claim(): void
    {
        foreach (self::reports() as $report) {
            self::assertFalse($report['human_verified']);
            self::assertTrue($report['review_required']);
            self::assertFalse($report['import_permitted']);
            self::assertSame([
                'status', 'fixture_version', 'recorded_review_events', 'last_decision_count',
                'human_verified', 'review_required', 'import_permitted',
            ], array_keys($report));
        }
    }

    public function test_browser_has_no_capture_apis_or_raw_fixtures(): void
    {
        $html = self::html();
        self::assertSame(6, preg_match_all(
            '/<button type="button" data-race="[0-5]" aria-pressed="(?:true|false)">/',
            $html,
        ));
        self::assertStringContainsString('id="race-status" role="status" aria-live="polite"', $html);
        self::assertStringContainsString('Revisão humana não comprovada; importação proibida.', $html);
        self::assertStringContainsString('String(Number(button.dataset.race) === index)', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);

        $serialized = json_encode(self::reports(), JSON_THROW_ON_ERROR);
        foreach ([
            'fiction-1', 'fiction-2', '"fixture_id"', '"fixture_note"',
            'fixture_key_a', 'modified invented content', '=1+1',
            'tenant_id', 'approved_by',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $serialized);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage',
            'sessionStorage', '.innerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
