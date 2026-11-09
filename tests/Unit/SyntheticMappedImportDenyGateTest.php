<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\AuthorizationDecision;
use App\Core\Portability\SyntheticColumnMappingSummary;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use App\Core\Portability\SyntheticImportMappedPreviewSummary;
use App\Core\Portability\SyntheticImportPreviewSummary;
use App\Core\Portability\SyntheticMappedImportDenyGate;
use App\Core\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

final class SyntheticMappedImportDenyGateTest extends TestCase
{
    private const OWN = '11111111-1111-4111-8111-111111111111';
    private const OTHER = '22222222-2222-4222-8222-222222222222';
    private const PERSON = '33333333-3333-4333-8333-333333333333';
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';

    private static function context(
        string $tenant = self::OWN,
        string $principal = self::PERSON,
        string $correlation = self::CORRELATION,
    ): TenantContext {
        return new TenantContext($tenant, $principal, $correlation);
    }

    private static function preview(
        string $csv = "label,origin\nExample,website\n",
        array $pairs = [['source' => 1, 'target' => 2], ['source' => 2, 'target' => 1]],
    ): SyntheticImportMappedPreviewSummary {
        return (new SyntheticCsvPreviewDecoder())->previewMapped(
            $csv, ['description', 'source_system'], $pairs,
        );
    }

    private static function verdict(
        TenantContext $context,
        SyntheticImportMappedPreviewSummary $preview,
        mixed $tenant = self::OWN,
        mixed $operation = 'import',
        mixed $fields = [1, 2],
    ): AuthorizationDecision {
        return (new SyntheticMappedImportDenyGate())->evaluate(
            $context, $preview, $tenant, $operation, $fields,
        );
    }

    public function test_clean_and_complete_mapping_is_still_denied(): void
    {
        $decision = self::verdict(self::context(), self::preview());
        self::assertFalse($decision->allowed);
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
        self::assertSame([
            'code' => 'SCOPE_NOT_VERIFIED',
            'correlation_id' => self::CORRELATION,
        ], $decision->signal());
        self::assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_exact_fields_in_different_order_do_not_create_any_authority(): void
    {
        $decision = self::verdict(self::context(), self::preview(), self::OWN, 'import', [2, 1]);
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
        self::assertFalse($decision->allowed);
    }

    public function test_matching_spoofed_context_does_not_make_a_trusted_membership(): void
    {
        $decision = self::verdict(self::context(self::OTHER), self::preview(), self::OTHER);
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
        self::assertFalse($decision->allowed);
    }

    public function test_tenant_mismatch_is_invalid_even_when_field_mapping_is_clean(): void
    {
        $decision = self::verdict(self::context(), self::preview(), self::OTHER);
        self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
        self::assertFalse($decision->allowed);
        $signal = json_encode($decision->signal(), JSON_THROW_ON_ERROR);
        foreach ([self::OWN, self::OTHER, 'Example', 'website'] as $secret) {
            self::assertStringNotContainsString($secret, $signal);
        }
    }

    public function test_invalid_correlation_is_omitted_from_failure_signal(): void
    {
        $decision = self::verdict(self::context(correlation: 'customer@example.invalid'), self::preview());
        self::assertFalse($decision->allowed);
        self::assertNull($decision->signal()['correlation_id']);
    }

    public function test_an_empty_synthetic_csv_has_no_import_authority(): void
    {
        $preview = self::preview("label,origin\n");
        $decision = self::verdict(self::context(), $preview);
        self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
    }

    public function test_formula_warning_and_partial_mapping_are_invalid_for_this_lab(): void
    {
        $formula = self::preview("label,origin\n=1+1,website\n");
        self::assertSame(
            'CONTRACT_INVALID',
            self::verdict(self::context(), $formula)->reasonCode,
        );

        $partial = self::preview("label,origin\nExample,website\n", [['source'=>1, 'target'=>2]]);
        self::assertSame(
            'CONTRACT_INVALID',
            self::verdict(self::context(), $partial, self::OWN, 'import', [2])->reasonCode,
        );
    }

    public function test_forged_public_summary_is_rejected_without_throwing_or_granting(): void
    {
        $badPreview = new SyntheticImportMappedPreviewSummary(
            new SyntheticImportPreviewSummary(1, 2, 0, [['row'=>2, 'column'=>1]]),
            new SyntheticColumnMappingSummary(
                2, 2,
                [['source'=>1, 'target'=>1], ['source'=>2, 'target'=>2]],
                [], [],
            ),
        );
        $decision = self::verdict(self::context(), $badPreview);
        self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
        self::assertFalse($decision->allowed);
    }

    public function test_invalid_tenants_principals_actions_and_target_fields_fail_closed(): void
    {
        $cases = [
            [self::context(), self::OTHER, 'import', [1, 2]],
            [self::context(), 'not-a-uuid', 'import', [1, 2]],
            [self::context(), null, 'import', [1, 2]],
            [self::context(), ['tenant_id' => self::OWN], 'import', [1, 2]],
            [self::context('tenant-from-request'), 'tenant-from-request', 'import', [1, 2]],
            [self::context(principal: 'spoofed-principal'), self::OWN, 'import', [1, 2]],
            [self::context(principal: '00000000-0000-0000-0000-000000000000'), self::OWN, 'import', [1, 2]],
            [self::context(), self::OWN, 'export', [1, 2]],
            [self::context(), self::OWN, 'merge', [1, 2]],
            [self::context(), self::OWN, 'ALLOW', [1, 2]],
            [self::context(), self::OWN, true, [1, 2]],
            [self::context(), self::OWN, 'import', []],
            [self::context(), self::OWN, 'import', [1]],
            [self::context(), self::OWN, 'import', [1, 1]],
            [self::context(), self::OWN, 'import', [1, 3]],
            [self::context(), self::OWN, 'import', [0, 2]],
            [self::context(), self::OWN, 'import', [-1, 2]],
            [self::context(), self::OWN, 'import', [PHP_INT_MAX, 2]],
            [self::context(), self::OWN, 'import', ['1', 2]],
            [self::context(), self::OWN, 'import', [true, 2]],
            [self::context(), self::OWN, 'import', ['*', 2]],
            [self::context(), self::OWN, 'import', ['field' => 1, 'second' => 2]],
            [self::context(), self::OWN, 'import', [['index' => 1], 2]],
            [self::context(), self::OWN, 'import', '1,2'],
            [self::context(), self::OWN, 'import', [1, 2, 3]],
        ];

        $preview = self::preview();
        foreach ($cases as [$context, $tenant, $operation, $fields]) {
            $decision = self::verdict($context, $preview, $tenant, $operation, $fields);
            self::assertFalse($decision->allowed);
            self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
            self::assertSame(['code', 'correlation_id'], array_keys($decision->signal()));
        }
    }

    public function test_no_write_persist_export_or_allow_method_is_exposed(): void
    {
        $gate = new SyntheticMappedImportDenyGate();
        foreach (['allow', 'grant', 'import', 'export', 'upload', 'store',
            'persist', 'delete', 'merge', 'charge', 'commit', 'apply'] as $method) {
            self::assertFalse(method_exists($gate, $method), $method);
        }
        self::assertSame(['evaluate'], get_class_methods($gate));
    }
}
