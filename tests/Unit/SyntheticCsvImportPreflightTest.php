<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreflight;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticCsvImportPreflightTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const OTHER = '22222222-2222-4222-8222-222222222222';
    private const PRINCIPAL = '33333333-3333-4333-8333-333333333333';
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';

    private static function context(string $tenant = self::TENANT, string $correlation = self::CORRELATION): TenantContext
    {
        return new TenantContext($tenant, self::PRINCIPAL, $correlation);
    }

    private static function inspect(
        string $csv = "name,stage\nSYNTHETIC_LEAD,SYNTHETIC_STAGE\n",
        array $targetLabels = ['stage', 'name'],
        mixed $tenant = self::TENANT,
        mixed $fields = ['stage', 'name'],
        ?TenantContext $context = null,
    ): \App\Core\Portability\SyntheticCsvImportPreflightSummary {
        return (new SyntheticCsvImportPreflight())->inspect(
            $csv, $targetLabels, $context ?? self::context(), $tenant, $fields,
        );
    }

    public function test_end_to_end_csv_proposal_still_denies_import(): void
    {
        $result = self::inspect();
        self::assertSame(1, $result->preview->csvPreview->rowsInspected);
        self::assertTrue($result->preview->isMappingComplete());
        self::assertSame([
            ['source' => 1, 'target' => 2],
            ['source' => 2, 'target' => 1],
        ], $result->preview->mappingPreview->positions);
        self::assertFalse($result->decision->allowed);
        self::assertSame('SCOPE_NOT_VERIFIED', $result->decision->reasonCode);
        self::assertFalse($result->preview->canImport());
        self::assertFalse($result->canImport());
        self::assertFalse($result->canPersistLead());
        self::assertFalse($result->canEnableImportRoute());
        self::assertSame(self::CORRELATION, $result->decision->signal()['correlation_id']);

        $serialized = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_LEAD', 'SYNTHETIC_STAGE', 'name', 'stage', self::TENANT, self::PRINCIPAL] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $serialized);
        }
    }

    public function test_spreadsheet_formula_warning_does_not_change_denial(): void
    {
        $report = self::inspect(
            "name,stage\nSYNTHETIC_A,\" \u{2060}=1+1\"\n",
        );
        self::assertTrue($report->preview->hasFormulaWarnings());
        self::assertSame([['row' => 1, 'column' => 2]],
            $report->preview->csvPreview->potentialFormulaPositions);
        self::assertFalse($report->canImport());
        self::assertSame('SCOPE_NOT_VERIFIED', $report->decision->reasonCode);
    }

    public function test_forged_or_inconsistent_tenant_never_grants_import(): void
    {
        $wrong = self::inspect(tenant: self::OTHER);
        self::assertSame('CONTRACT_INVALID', $wrong->decision->reasonCode);
        self::assertFalse($wrong->decision->allowed);

        $forgedContext = self::inspect(context: self::context(self::OTHER), tenant: self::OTHER);
        self::assertSame('SCOPE_NOT_VERIFIED', $forgedContext->decision->reasonCode);
        self::assertFalse($forgedContext->decision->allowed);

        $reported = json_encode($wrong, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::TENANT, $reported);
        self::assertStringNotContainsString(self::OTHER, $reported);
    }

    public function test_fields_must_be_in_exact_suggested_mapping_not_merely_target_list(): void
    {
        $notMapped = self::inspect(
            "name,stage\nSYNTHETIC_A,SYNTHETIC_B\n",
            ['name', 'stage', 'email'],
            self::TENANT,
            ['email'],
        );
        self::assertSame('CONTRACT_INVALID', $notMapped->decision->reasonCode);

        $noMatchingHeaders = self::inspect(
            "source\nSYNTHETIC_ONLY\n",
            ['name'],
            self::TENANT,
            ['name'],
        );
        self::assertSame([], $noMatchingHeaders->preview->mappingPreview->positions);
        self::assertSame('CONTRACT_INVALID', $noMatchingHeaders->decision->reasonCode);
    }

    public function test_invalid_fields_and_authorization_claims_are_rejected(): void
    {
        foreach ([
            [], ['name', 'name'], ['*'], ['email'], ['grant'], [true],
            [['allow' => true]], ['name' => 'stage'], 'name', null, 1,
        ] as $fields) {
            $result = self::inspect(fields: $fields);
            self::assertSame('CONTRACT_INVALID', $result->decision->reasonCode);
            self::assertFalse($result->canImport());
        }
    }

    public function test_exact_repeat_positions_flow_through_denied_preflight(): void
    {
        $report = self::inspect(
            csv: "name,stage\n"
                . "SYNTHETIC_A,SYNTHETIC_STAGE\n"
                . "SYNTHETIC_B,SYNTHETIC_STAGE\n"
                . "SYNTHETIC_A,SYNTHETIC_STAGE\n",
        );

        self::assertNotNull($report->repeatPreview);
        self::assertTrue($report->hasRepeatWarnings());
        self::assertSame([['firstRow' => 1, 'repeatedRow' => 3]],
            $report->repeatPreview->repeatedRowPositions);
        self::assertSame($report->preview->csvPreview, $report->repeatPreview->csvPreview);
        self::assertTrue($report->preview->isMappingComplete());
        self::assertSame('SCOPE_NOT_VERIFIED', $report->decision->reasonCode);
        self::assertFalse($report->decision->allowed);
        self::assertFalse($report->canImport());
        self::assertFalse($report->canPersistLead());
        self::assertFalse($report->canEnableImportRoute());
        self::assertFalse($report->repeatPreview->canDeduplicateContacts());

        $encoded = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['SYNTHETIC_A', 'SYNTHETIC_B', 'SYNTHETIC_STAGE', self::TENANT] as $private) {
            self::assertStringNotContainsString($private, $encoded);
        }
    }

    public function test_semicolon_row_repeats_remain_warnings_with_tenant_mismatch_denied(): void
    {
        $result = (new SyntheticCsvImportPreflight())->inspect(
            "name;note\nSYNTHETIC_A;\"2,50\"\nSYNTHETIC_A;\"2,50\"\n",
            ['name', 'note'],
            self::context(),
            self::OTHER,
            ['name', 'note'],
            ';',
        );

        self::assertTrue($result->hasRepeatWarnings());
        self::assertSame([['firstRow' => 1, 'repeatedRow' => 2]],
            $result->repeatPreview->repeatedRowPositions);
        self::assertSame('CONTRACT_INVALID', $result->decision->reasonCode);
        self::assertFalse($result->decision->allowed);
        self::assertFalse($result->canImport());
        self::assertFalse($result->repeatPreview->canDeduplicateContacts());
    }

    public function test_similar_contact_like_rows_are_not_contact_deduplication_or_authority(): void
    {
        $result = self::inspect(
            csv: "name,stage\nSYNTHETIC_A,STAGE_ONE\nSYNTHETIC_A,STAGE_TWO\n",
        );
        self::assertNotNull($result->repeatPreview);
        self::assertFalse($result->hasRepeatWarnings());
        self::assertSame([], $result->repeatPreview->repeatedRowPositions);
        self::assertFalse($result->repeatPreview->canDeduplicateContacts());
        self::assertFalse($result->canImport());
        self::assertSame('SCOPE_NOT_VERIFIED', $result->decision->reasonCode);
    }

    public function test_untrusted_correlation_is_scrubbed_and_rejected_csv_is_not_reflected(): void
    {
        $withFakeCorrelation = self::inspect(
            context: self::context(self::TENANT, 'private@example.test'),
        );
        self::assertNull($withFakeCorrelation->decision->signal()['correlation_id']);
        self::assertFalse($withFakeCorrelation->decision->allowed);

        try {
            self::inspect(csv: "name,stage\n\"private@example.test\n");
            self::fail('Malformed input accepted');
        } catch (InvalidArgumentException $error) {
            self::assertSame('Estrutura CSV de pre-visualizacao invalida.', $error->getMessage());
            self::assertStringNotContainsString('private@example.test', $error->getMessage());
        }
    }
}
