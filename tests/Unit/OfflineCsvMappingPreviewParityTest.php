<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\CsvMappingPlanPreview;
use PHPUnit\Framework\TestCase;

final class OfflineCsvMappingPreviewParityTest extends TestCase
{
    private static function html(): string
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html');
        self::assertIsString($content);

        return $content;
    }

    private static function browserPlans(): array
    {
        self::assertSame(1, preg_match(
            '/const MAPPING_REPORTS = Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $matches,
        ));

        $reports = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($reports);
        self::assertCount(2, $reports);

        return $reports;
    }

    private static function sample(): array
    {
        return [
            ['id', 'name', 'origin'],
            ['fiction-1', 'Sample One', 'sample-site'],
            ['fiction-2', 'Sample Two', 'sample-manual'],
        ];
    }

    public function test_both_static_mapping_reports_match_php_dry_run(): void
    {
        $reports = self::browserPlans();

        self::assertSame(CsvMappingPlanPreview::inspect(self::sample(), [
            ['column' => 1, 'target' => 'fixture_alpha'],
            ['column' => 2, 'target' => 'fixture_beta'],
            ['column' => 3, 'target' => 'fixture_gamma'],
        ]), $reports[0]);

        self::assertSame(CsvMappingPlanPreview::inspect(self::sample(), [
            ['column' => 1, 'target' => 'fixture_same'],
            ['column' => 1, 'target' => 'fixture_other'],
            ['column' => 2, 'target' => 'fixture_same'],
        ]), $reports[1]);
    }

    public function test_clean_mapping_never_represents_import_authorization(): void
    {
        $reports = self::browserPlans();
        self::assertSame([], $reports[0]['issue_codes']);
        self::assertTrue($reports[0]['review_required']);
        self::assertSame(0, $reports[0]['cell_warning_count']);
        self::assertStringContainsString('Revisão humana continua obrigatória.', self::html());
        self::assertStringContainsString('nenhuma importação ou escrita é autorizada.', self::html());
    }

    public function test_conflicting_mapping_only_discloses_positions_and_codes(): void
    {
        $report = self::browserPlans()[1];
        self::assertSame([3], $report['unmapped_columns']);
        self::assertSame([1], $report['duplicate_source_columns']);
        self::assertSame([[1, 3]], $report['duplicate_target_mapping_positions']);
        self::assertSame(['duplicate_source_mapping', 'duplicate_target_mapping'], $report['issue_codes']);
        self::assertTrue($report['review_required']);
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['fixture_same', 'fixture_other', 'Sample One', 'origin', 'fiction-1'] as $raw) {
            self::assertStringNotContainsString($raw, $json);
        }
    }

    public function test_mapping_ui_is_separate_and_uses_only_plain_text(): void
    {
        $html = self::html();
        self::assertSame(2, preg_match_all(
            '/<button type="button" data-map="[01]" aria-pressed="(?:true|false)">/',
            $html,
        ));
        self::assertStringContainsString('independentemente do exemplo estrutural selecionado acima', $html);
        self::assertStringContainsString('id="map-status" role="status" aria-live="polite"', $html);
        self::assertStringContainsString('mapRows.replaceChildren()', $html);
        self::assertStringContainsString('cell.textContent = value;', $html);
        self::assertStringContainsString("connect-src 'none'", $html);
        self::assertStringContainsString("form-action 'none'", $html);
        foreach ([
            'fetch(', '.innerHTML', '<form', '<input', '<textarea',
            'localStorage', 'sessionStorage', 'insertAdjacentHTML',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
