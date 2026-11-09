<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\AuthorizationDecision;
use App\Core\Portability\SyntheticImportPreviewInspector;
use App\Core\Portability\SyntheticImportPreviewSummary;
use App\Core\Portability\SyntheticPortabilityDenyGate;
use App\Core\Tenancy\TenantContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticPortabilityDenyGateTest extends TestCase
{
    private const OWN = '11111111-1111-4111-8111-111111111111';
    private const OTHER = '22222222-2222-4222-8222-222222222222';
    private const PERSON = '33333333-3333-4333-8333-333333333333';
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';

    private static function context(string $tenant = self::OWN): TenantContext
    {
        return new TenantContext($tenant, self::PERSON, self::CORRELATION);
    }

    private static function preview(): SyntheticImportPreviewSummary
    {
        return (new SyntheticImportPreviewInspector())->inspect(
            ['label', 'source'],
            [['Lead Exemplo A', 'site-exemplo'], ['Lead Exemplo B', '=1+1']],
        );
    }

    public function test_even_exact_tenant_reference_and_clean_shape_never_allow_import(): void
    {
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(), self::preview(), self::OWN, 'import', ['label', 'source'],
        );

        self::assertInstanceOf(AuthorizationDecision::class, $decision);
        self::assertFalse($decision->allowed);
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
        self::assertSame(['code', 'correlation_id'], array_keys($decision->signal()));
        self::assertSame(self::CORRELATION, $decision->signal()['correlation_id']);
    }

    public function test_even_exact_tenant_reference_and_clean_shape_never_allow_export(): void
    {
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(), self::preview(), self::OWN, 'export', ['label'],
        );

        self::assertFalse($decision->allowed);
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
    }

    public function test_mismatched_target_tenant_is_rejected_without_identifying_tenant(): void
    {
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(), self::preview(), self::OTHER, 'import', ['label'],
        );

        self::assertFalse($decision->allowed);
        self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
        $signal = json_encode($decision->signal(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::OWN, $signal);
        self::assertStringNotContainsString(self::OTHER, $signal);
        self::assertStringNotContainsString('Lead Exemplo', $signal);
    }

    public function test_constructed_context_with_other_tenant_never_becomes_authority(): void
    {
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(self::OTHER), self::preview(), self::OTHER, 'import', ['label'],
        );
        self::assertFalse($decision->allowed);
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
    }

    public function test_empty_or_unverified_tenant_in_context_is_not_accepted_as_proof(): void
    {
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context('tenant-from-query-string'),
            self::preview(),
            'tenant-from-query-string',
            'import',
            ['label'],
        );

        self::assertFalse($decision->allowed);
        self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
    }

    public static function malformedRequests(): array
    {
        return [
            'missing tenant' => [null, 'import', ['label']],
            'invalid tenant' => ['company-a', 'import', ['label']],
            'all zero tenant' => ['00000000-0000-0000-0000-000000000000', 'import', ['label']],
            'wrong tenant' => [self::OTHER, 'export', ['label']],
            'forged tenant object' => [['id' => self::OWN, 'allow' => true], 'import', ['label']],
            'unsupported action' => [self::OWN, 'delete', ['label']],
            'descriptive allow action' => [self::OWN, 'ALLOW', ['label']],
            'boolean action' => [self::OWN, true, ['label']],
            'no fields' => [self::OWN, 'export', []],
            'wildcard field' => [self::OWN, 'export', ['*']],
            'duplicate fields' => [self::OWN, 'export', ['label', 'label']],
            'fields not a list' => [self::OWN, 'export', ['field' => 'label']],
            'field from nested payload' => [self::OWN, 'import', [['label' => 'value']]],
            'too many fields' => [self::OWN, 'import', ['a','b','c','d','e','f','g','h','i']],
            'invalid field' => [self::OWN, 'import', ['contact.email']],
        ];
    }

    #[DataProvider('malformedRequests')]
    public function test_invalid_or_forged_descriptions_always_fail_closed(
        mixed $target,
        mixed $operation,
        mixed $fields,
    ): void {
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(),
            self::preview(),
            $target,
            $operation,
            $fields,
        );

        self::assertFalse($decision->allowed);
        self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
    }

    public function test_empty_preview_does_not_bypass_tenant_and_field_checks(): void
    {
        $empty = (new SyntheticImportPreviewInspector())->inspect(['label'], []);
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(), $empty, self::OWN, 'import', ['label'],
        );

        self::assertSame(0, $empty->rowsInspected);
        self::assertFalse($empty->canImport());
        self::assertFalse($decision->allowed);
    }

    public function test_preview_with_formula_warning_never_becomes_authorized(): void
    {
        $preview = self::preview();
        self::assertSame(1, $preview->potentiallyExecutableCellCount());
        self::assertFalse($preview->canImport());
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(), $preview, self::OWN, 'export', ['source'],
        );

        self::assertFalse($decision->allowed);
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
    }

    public function test_preview_cannot_supply_allow_evidence_even_if_caller_forges_its_numbers(): void
    {
        $fake = new SyntheticImportPreviewSummary(0, 1, 0, []);
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            self::context(), $fake, self::OWN, 'import', ['label'],
        );

        self::assertFalse($decision->allowed);
        self::assertFalse($fake->canImport());
        self::assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
        self::assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_correlation_id_is_not_trusted_and_is_scrubbed_if_not_uuid(): void
    {
        $context = new TenantContext(self::OWN, self::PERSON, 'person@example.invalid');
        $decision = (new SyntheticPortabilityDenyGate())->evaluate(
            $context, self::preview(), self::OWN, 'import', ['label'],
        );

        self::assertNull($decision->signal()['correlation_id']);
        self::assertFalse($decision->allowed);
    }
}
