<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreview;
use PHPUnit\Framework\TestCase;

final class OfflineCsvDiagnosticsChecklistTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-72-csv-checklist/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    /** @return list<array<string,mixed>> */
    private static function browserReports(): array
    {
        $html = self::html();
        self::assertSame(1, preg_match(
            '/const SCENARIOS = Object\.freeze\((\[[\s\S]*?\])\);/',
            $html,
            $matches,
        ));

        $reports = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($reports);
        self::assertCount(4, $reports);

        return $reports;
    }

    /**
     * @return list<array{string, list<string>, list<string>, string}>
     */
    private static function fixtures(): array
    {
        return [
            [
                "name,stage\nSYNTHETIC_A,INITIAL\nSYNTHETIC_B,CONTACT\n",
                ['name', 'stage'], ['name', 'stage'], ',',
            ],
            [
                "stage;name;note\n"
                    . ";SYNTHETIC_A;safe\n"
                    . "SYNTHETIC_STAGE;;safe\n"
                    . "SYNTHETIC_STAGE;;safe\n",
                ['stage', 'name', 'note'], ['name', 'stage', 'missing'], ';',
            ],
            [
                "name;stage;note\n"
                    . "SYNTHETIC_A;STAGE;\"=SUM(1)\"\n"
                    . "SYNTHETIC_B;STAGE;\"=SUM(1)\"\n",
                ['stage', 'name', 'note'], ['name', 'note'], ';',
            ],
            [
                "source;stage\nSYNTHETIC_A;STAGE\n",
                ['name', 'stage'], ['name', 'email'], ';',
            ],
        ];
    }

    public function test_four_browser_reports_are_identical_to_actual_php_composite_results(): void
    {
        $reports = self::browserReports();
        $parser = new SyntheticCsvImportPreview();

        foreach (self::fixtures() as $index => [$csv, $target, $required, $delimiter]) {
            $bundle = $parser->inspectComposite($csv, $target, $required, $delimiter);
            self::assertNotNull($bundle->requiredCellsPreview);

            $expected = [
                'rows' => $bundle->mappedPreview->csvPreview->rowsInspected,
                'columns' => $bundle->mappedPreview->csvPreview->columnsInspected,
                'mapped' => $bundle->mappedPreview->mappingPreview->mappedColumns(),
                'repeats' => $bundle->repeatPreview->repeatedRowPositions,
                'missing_headers' => $bundle->requiredCellsPreview->missingRequiredTargetPositions,
                'empty_cells' => $bundle->requiredCellsPreview->emptyRequiredCellPositions,
                'formula_positions' => $bundle->mappedPreview->csvPreview->potentialFormulaPositions,
                'allowed' => $bundle->canImport(),
            ];

            self::assertSame($expected, $reports[$index], 'PHP/HTML metadata parity case '.$index);
            self::assertFalse($bundle->canImport());
            self::assertFalse($bundle->canPersistLead());
        }
    }

    public function test_reporting_is_positions_only_with_no_csv_cells_or_headers_leaked(): void
    {
        $html = self::html();
        $json = json_encode(self::browserReports(), JSON_THROW_ON_ERROR);

        foreach (['SYNTHETIC_A', 'SYNTHETIC_B', 'SYNTHETIC_STAGE',
            'INITIAL', 'CONTACT', 'STAGE', '=SUM(1)', '"name"', '"email"',
            '"source"', 'contact@example.test'] as $private) {
            self::assertStringNotContainsString($private, $json);
        }

        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        self::assertStringContainsString('Importação proibida', $html);
        self::assertStringContainsString('nenhuma pessoa ou empresa foi verificada', $html);
        self::assertStringContainsString('não são uma política comercial aprovada', $html);
        self::assertStringContainsString('report.allowed!==false', $html);
    }

    public function test_offline_keyboard_ui_has_four_scenarios_and_safe_dom_construction(): void
    {
        $html = self::html();
        self::assertSame(4, preg_match_all(
            '/<button type="button" data-case="[0-3]" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            '<main id="conteudo">', 'aria-label="Cenários de análise CSV fictícia"',
            'aria-live="polite"', 'id="check-rows"',
            'id="metric-rows"', 'id="metric-columns"',
            'id="metric-mapped"', 'id="metric-findings"',
            'button:focus-visible', "table.replaceChildren();",
            "left.textContent=type", "right.textContent=position",
            "connect-src 'none'", "form-action 'none'",
        ] as $needed) {
            self::assertStringContainsString($needed, $html);
        }

        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', '.innerHTML', '.outerHTML',
            'insertAdjacentHTML', '<form', '<input', '<textarea',
            '<iframe', '<img', '<script src=', '<link',
            'onload=', 'onclick=', '@import', 'url(', 'download='] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }

        preg_match_all('/\bhref="([^"]+)"/', $html, $matches);
        self::assertSame(
            ['#conteudo', '../f3-22-csv-risk-preview/index.html', '../f3-81-submission-review/index.html'],
            $matches[1],
        );
        self::assertFileExists(
            dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html',
        );
        self::assertFileExists(
            dirname(__DIR__, 2).'/prototypes/f3-81-submission-review/index.html',
        );
        self::assertStringContainsString('@media(max-width:390px)', $html);
    }
}
