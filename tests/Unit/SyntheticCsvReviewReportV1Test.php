<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticColumnMappingSummary;
use App\Core\Portability\SyntheticCsvImportPreflight;
use App\Core\Portability\SyntheticCsvImportPreflightSummary;
use App\Core\Portability\SyntheticCsvReviewReportV1;
use App\Core\Portability\SyntheticCsvRowRepeatSummary;
use App\Core\Portability\SyntheticImportMappedPreviewSummary;
use App\Core\Portability\SyntheticImportPreviewSummary;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvReviewReportV1Test extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const OTHER = '22222222-2222-4222-8222-222222222222';
    private const PRINCIPAL = '33333333-3333-4333-8333-333333333333';
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';

    private static function preflight(
        string $csv = "stage;name;note\n"
            . ";SYNTHETIC_A;\"=1+1\"\n"
            . ";SYNTHETIC_A;\"=1+1\"\n",
        mixed $claimedTenant = self::TENANT,
        ?array $required = ['name', 'stage', 'missing'],
    ): SyntheticCsvImportPreflightSummary {
        return (new SyntheticCsvImportPreflight())->inspect(
            $csv,
            ['stage', 'name', 'note'],
            new TenantContext(self::TENANT, self::PRINCIPAL, self::CORRELATION),
            $claimedTenant,
            ['stage', 'name'],
            ';',
            $required,
        );
    }

    public function test_version_one_report_is_only_numeric_positions_and_permanent_denial(): void
    {
        $report = (new SyntheticCsvReviewReportV1())->build(self::preflight());

        self::assertSame([
            'protocol', 'schema_version', 'status',
            'rows_inspected', 'columns_inspected', 'empty_cells',
            'mapping', 'warnings', 'analysis_performed', 'denial_reason',
            'allowed', 'can_import', 'can_persist_lead', 'can_enable_import_route',
        ], array_keys($report));
        self::assertSame('imagine.crm.synthetic_csv_review', $report['protocol']);
        self::assertSame(1, $report['schema_version']);
        self::assertSame('REVIEW_ONLY_DENIED', $report['status']);
        self::assertSame(2, $report['rows_inspected']);
        self::assertSame(3, $report['columns_inspected']);
        self::assertSame(2, $report['empty_cells']);
        self::assertSame([
            ['source' => 1, 'target' => 1],
            ['source' => 2, 'target' => 2],
            ['source' => 3, 'target' => 3],
        ], $report['mapping']['mapped_source_to_target_positions']);
        self::assertSame([], $report['mapping']['unmapped_source_positions']);
        self::assertSame([], $report['mapping']['unmapped_target_positions']);

        self::assertSame([
            ['row' => 1, 'column' => 3],
            ['row' => 2, 'column' => 3],
        ], $report['warnings']['potential_formula_positions']);
        self::assertSame([
            ['firstRow' => 1, 'repeatedRow' => 2],
        ], $report['warnings']['repeated_row_positions']);
        self::assertSame([1, 2], $report['warnings']['required_source_positions']);
        self::assertSame([3], $report['warnings']['missing_required_header_positions']);
        self::assertSame([
            ['row' => 1, 'column' => 1],
            ['row' => 2, 'column' => 1],
        ], $report['warnings']['empty_required_cell_positions']);
        self::assertSame(['repeated_rows' => true, 'required_cells' => true],
            $report['analysis_performed']);
        self::assertSame('SCOPE_NOT_VERIFIED', $report['denial_reason']);
        foreach (['allowed', 'can_import', 'can_persist_lead', 'can_enable_import_route'] as $property) {
            self::assertFalse($report[$property]);
        }

        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertLessThan(4096, strlen($json));
        foreach (['SYNTHETIC_A', '=1+1', '"stage"', '"name"', '"note"',
            '"missing"', self::TENANT, self::PRINCIPAL, self::CORRELATION] as $secret) {
            self::assertStringNotContainsString($secret, $json);
        }
    }

    public function test_optional_required_analysis_is_explicitly_marked_as_not_performed(): void
    {
        $report = (new SyntheticCsvReviewReportV1())->build(self::preflight(
            "stage;name;note\nSYNTHETIC_A;SYNTHETIC_B;safe\n",
            self::TENANT,
            null,
        ));
        self::assertSame(['repeated_rows' => true, 'required_cells' => false],
            $report['analysis_performed']);
        self::assertSame([], $report['warnings']['required_source_positions']);
        self::assertSame([], $report['warnings']['missing_required_header_positions']);
        self::assertSame([], $report['warnings']['empty_required_cell_positions']);
        self::assertSame([], $report['warnings']['repeated_row_positions']);
        self::assertSame('SCOPE_NOT_VERIFIED', $report['denial_reason']);
        self::assertFalse($report['allowed']);
    }

    public function test_tenant_mismatch_is_reduced_to_safe_denial_code_without_identity(): void
    {
        $report = (new SyntheticCsvReviewReportV1())->build(
            self::preflight(claimedTenant: self::OTHER),
        );
        self::assertSame('CONTRACT_INVALID', $report['denial_reason']);
        self::assertFalse($report['allowed']);
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::TENANT, $json);
        self::assertStringNotContainsString(self::OTHER, $json);
        self::assertStringNotContainsString(self::PRINCIPAL, $json);
        self::assertStringNotContainsString(self::CORRELATION, $json);
    }

    public function test_forged_preview_positions_are_rejected_without_echoing_payload(): void
    {
        $good = self::preflight();
        $existing = $good->preview->csvPreview;
        $badValues = [
            [
                new SyntheticImportMappedPreviewSummary(
                    new SyntheticImportPreviewSummary(
                        $existing->rowsInspected,
                        $existing->columnsInspected,
                        $existing->emptyCells,
                        [['row' => 'private@example.test', 'column' => 1]],
                    ),
                    $good->preview->mappingPreview,
                ),
                null,
            ],
            [
                $good->preview,
                new SyntheticCsvRowRepeatSummary(
                    $existing,
                    [['firstRow' => 'private@example.test', 'repeatedRow' => 2]],
                ),
            ],
            [
                new SyntheticImportMappedPreviewSummary(
                    $existing,
                    new SyntheticColumnMappingSummary(
                        3, 3,
                        [['source' => 'private@example.test', 'target' => 1]],
                        [2, 3], [2, 3],
                    ),
                ),
                null,
            ],
        ];

        foreach ($badValues as [$preview, $repeat]) {
            $bad = new SyntheticCsvImportPreflightSummary(
                $preview,
                $good->decision,
                $repeat,
            );
            try {
                (new SyntheticCsvReviewReportV1())->build($bad);
                self::fail('Forged positions unexpectedly entered the public report');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Relatorio CSV sintetico invalido.', $e->getMessage());
                self::assertStringNotContainsString('private@example.test', $e->getMessage());
            }
        }
    }

    public function test_report_rejects_mismatched_or_out_of_range_snapshot_metadata(): void
    {
        $good = self::preflight();
        $preview = $good->preview->csvPreview;
        $cases = [
            new SyntheticImportPreviewSummary(51, 3, 0, []),
            new SyntheticImportPreviewSummary(2, 9, 0, []),
            new SyntheticImportPreviewSummary(2, 3, 99, []),
            new SyntheticImportPreviewSummary(2, 3, 0, [['row' => 3, 'column' => 1]]),
        ];

        foreach ($cases as $invalid) {
            $bad = new SyntheticCsvImportPreflightSummary(
                new SyntheticImportMappedPreviewSummary(
                    $invalid,
                    $good->preview->mappingPreview,
                ),
                $good->decision,
            );
            try {
                (new SyntheticCsvReviewReportV1())->build($bad);
                self::fail('Malformed metadata accepted as a public CSV review');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Relatorio CSV sintetico invalido.', $error->getMessage());
            }
        }
    }
}
