<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreflight;
use App\Core\Portability\SyntheticCsvImportPreflightSummary;
use App\Core\Portability\SyntheticCsvReviewReportV1;
use App\Core\Portability\SyntheticCsvReviewReportV1JsonEncoder;
use App\Core\Portability\SyntheticImportMappedPreviewSummary;
use App\Core\Portability\SyntheticImportPreviewSummary;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvReviewReportV1JsonEncoderTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const PRINCIPAL = '33333333-3333-4333-8333-333333333333';
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';

    private static function preflight(): SyntheticCsvImportPreflightSummary
    {
        return (new SyntheticCsvImportPreflight())->inspect(
            "stage;name;note\n;SYNTHETIC_A;\"=1+1\"\n;SYNTHETIC_A;\"=1+1\"\n",
            ['stage', 'name', 'note'],
            new TenantContext(self::TENANT, self::PRINCIPAL, self::CORRELATION),
            self::TENANT,
            ['stage', 'name'],
            ';',
            ['name', 'stage', 'missing'],
        );
    }

    public function test_compact_encoded_report_has_stable_version_and_no_payload(): void
    {
        $encoder = new SyntheticCsvReviewReportV1JsonEncoder();
        $preflight = self::preflight();

        $first = $encoder->encode($preflight);
        $second = $encoder->encode($preflight);
        self::assertSame($first, $second);
        self::assertSame(
            json_encode((new SyntheticCsvReviewReportV1())->build($preflight), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            $first,
        );
        self::assertLessThanOrEqual(SyntheticCsvReviewReportV1JsonEncoder::MAX_JSON_BYTES, strlen($first));
        self::assertStringStartsWith('{"protocol":"imagine.crm.synthetic_csv_review","schema_version":1,', $first);
        self::assertStringNotContainsString("\n", $first);

        $report = json_decode($first, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $report['schema_version']);
        self::assertSame('REVIEW_ONLY_DENIED', $report['status']);
        self::assertSame(2, $report['rows_inspected']);
        self::assertSame('SCOPE_NOT_VERIFIED', $report['denial_reason']);
        self::assertSame([['row' => 1, 'column' => 3], ['row' => 2, 'column' => 3]], $report['warnings']['potential_formula_positions']);
        foreach (['allowed', 'can_import', 'can_persist_lead', 'can_enable_import_route'] as $flag) {
            self::assertFalse($report[$flag]);
        }
        foreach (['SYNTHETIC_A', '=1+1', 'stage', 'name', 'note', 'missing',
            self::TENANT, self::PRINCIPAL, self::CORRELATION] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $first);
        }
        self::assertFalse($encoder->canImport());
        self::assertFalse($encoder->canPersistLead());
        self::assertFalse($encoder->canEnableImportRoute());
    }

    public function test_invalid_forged_metadata_fails_closed_before_json_serialization(): void
    {
        $good = self::preflight();
        $bad = new SyntheticCsvImportPreflightSummary(
            new SyntheticImportMappedPreviewSummary(
                new SyntheticImportPreviewSummary(
                    2, 3, 2, [['row' => 'private@example.test', 'column' => 1]],
                ),
                $good->preview->mappingPreview,
            ),
            $good->decision,
        );

        try {
            (new SyntheticCsvReviewReportV1JsonEncoder())->encode($bad);
            self::fail('Forged cell value escaped into report JSON.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Relatorio CSV sintetico invalido.', $exception->getMessage());
            self::assertStringNotContainsString('private@example.test', $exception->getMessage());
        }
    }
}
