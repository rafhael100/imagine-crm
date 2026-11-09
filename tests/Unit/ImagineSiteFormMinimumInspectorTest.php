<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\ImagineSiteFormMinimumAssessment;
use App\Core\Leads\ImagineSiteFormMinimumInspector;
use App\Core\Leads\LeadDraftFields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ImagineSiteFormMinimumInspectorTest extends TestCase
{
    public static function validSyntheticSamples(): array
    {
        return [
            'email only' => ['Lead Exemplo', 'lead@example.invalid', null],
            'phone only' => ['Lead Exemplo', null, '+55 (85) 99999-9999'],
            'both contacts' => ['Lead Exemplo', 'lead@example.invalid', '11999998888'],
            'phone with 8 digits' => ['Lead Exemplo', null, '12345678'],
        ];
    }

    #[DataProvider('validSyntheticSamples')]
    public function test_site_form_minimum_is_described_but_never_authorizes_delivery(
        string $name,
        ?string $email,
        ?string $phone,
    ): void {
        $assessment = (new ImagineSiteFormMinimumInspector())->inspect(
            new LeadDraftFields($name, $email, $phone),
        );

        self::assertInstanceOf(ImagineSiteFormMinimumAssessment::class, $assessment);
        self::assertTrue($assessment->meetsStructuralMinimum());
        self::assertFalse($assessment->canDeliver());
        self::assertFalse($assessment->invalidSuppliedContact);
    }

    public static function insufficientSyntheticSamples(): array
    {
        return [
            'no contact' => ['Lead Exemplo', null, null],
            'no name' => [null, 'lead@example.invalid', null],
            'invalid email' => ['Lead Exemplo', 'not-an-email', null],
            'short phone' => ['Lead Exemplo', null, '1234'],
            'oversized phone' => ['Lead Exemplo', null, '1234567890123456'],
            'letters in phone' => ['Lead Exemplo', null, '+55 OI 9999'],
            'invalid email despite valid phone' => ['Lead Exemplo', 'invalid', '11999998888'],
            'invalid phone despite valid email' => ['Lead Exemplo', 'lead@example.invalid', 'not-phone'],
            'no fields at all' => [null, null, null],
            'email padding not automatically normalized' => ['Lead Exemplo', ' lead@example.invalid ', null],
        ];
    }

    #[DataProvider('insufficientSyntheticSamples')]
    public function test_incomplete_or_malformed_site_draft_never_meets_minimum(
        ?string $name,
        ?string $email,
        ?string $phone,
    ): void {
        $assessment = (new ImagineSiteFormMinimumInspector())->inspect(
            new LeadDraftFields($name, $email, $phone),
        );

        self::assertFalse($assessment->meetsStructuralMinimum());
        self::assertFalse($assessment->canDeliver());
    }

    public function test_general_crm_draft_must_not_inherit_site_specific_requirements(): void
    {
        $general = new LeadDraftFields(name: 'Lead Exemplo');

        self::assertSame('Lead Exemplo', $general->name);
        self::assertNull($general->email);
        self::assertNull($general->phone);

        $siteOnly = (new ImagineSiteFormMinimumInspector())->inspect($general);
        self::assertFalse($siteOnly->meetsStructuralMinimum());
    }

    public function test_assessment_never_contains_raw_contact_details(): void
    {
        $email = 'lead@example.invalid';
        $phone = '+55 (85) 99999-9999';
        $assessment = (new ImagineSiteFormMinimumInspector())->inspect(
            new LeadDraftFields('Lead Exemplo', $email, $phone),
        );

        $json = json_encode($assessment, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('Lead Exemplo', $json);
        self::assertStringNotContainsString($email, $json);
        self::assertStringNotContainsString($phone, $json);
        self::assertTrue($assessment->meetsStructuralMinimum());
        self::assertFalse($assessment->canDeliver());
    }

    public function test_structural_completeness_does_not_infer_consent_or_legal_basis(): void
    {
        $assessment = (new ImagineSiteFormMinimumInspector())->inspect(
            new LeadDraftFields('Lead Exemplo', 'lead@example.invalid'),
        );

        self::assertTrue($assessment->meetsStructuralMinimum());
        self::assertFalse($assessment->canDeliver());
        self::assertFalse(property_exists($assessment, 'marketingConsent'));
        self::assertFalse(property_exists($assessment, 'legalBasis'));
        self::assertFalse(property_exists($assessment, 'tenantAuthorized'));
    }
}
