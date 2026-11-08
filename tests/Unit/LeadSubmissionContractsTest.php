<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadDraftFields;
use App\Core\Leads\LeadSourceSnapshot;
use App\Core\Leads\LeadSubmissionIdentity;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LeadSubmissionContractsTest extends TestCase
{
    private const ORG = '11111111-1111-4111-8111-111111111111';
    private const BINDING = '22222222-2222-4222-8222-222222222222';
    private const SUBMISSION_A = '33333333-3333-4333-8333-333333333333';
    private const SUBMISSION_B = '44444444-4444-4444-8444-444444444444';

    public function test_same_submission_retry_keeps_a_stable_tuple(): void
    {
        $first = new LeadSubmissionIdentity(self::ORG, self::BINDING, self::SUBMISSION_A);
        $retry = new LeadSubmissionIdentity(self::ORG, self::BINDING, self::SUBMISSION_A);

        self::assertTrue($first->sameLogicalDeliveryAs($retry));
        self::assertSame(self::SUBMISSION_A, $retry->submissionReference);
    }

    public function test_identical_contact_fields_do_not_mean_same_submission(): void
    {
        $sameSyntheticFields = new LeadDraftFields('Lead Exemplo', 'lead@example.invalid');
        $anotherSyntheticCopy = new LeadDraftFields('Lead Exemplo', 'lead@example.invalid');

        $a = new LeadSubmissionIdentity(self::ORG, self::BINDING, self::SUBMISSION_A);
        $b = new LeadSubmissionIdentity(self::ORG, self::BINDING, self::SUBMISSION_B);

        self::assertSame($sameSyntheticFields->email, $anotherSyntheticCopy->email);
        self::assertFalse($a->sameLogicalDeliveryAs($b));
    }

    public function test_binding_change_does_not_silently_redirect_old_delivery(): void
    {
        $before = new LeadSubmissionIdentity(self::ORG, self::BINDING, self::SUBMISSION_A);
        $after = new LeadSubmissionIdentity(self::ORG, '55555555-5555-4555-8555-555555555555', self::SUBMISSION_A);

        self::assertFalse($before->sameLogicalDeliveryAs($after));
    }

    public function test_another_organization_never_matches_the_same_delivery_key(): void
    {
        $a = new LeadSubmissionIdentity(self::ORG, self::BINDING, self::SUBMISSION_A);
        $b = new LeadSubmissionIdentity('66666666-6666-4666-8666-666666666666', self::BINDING, self::SUBMISSION_A);

        self::assertFalse($a->sameLogicalDeliveryAs($b));
    }

    public function test_uuid_case_is_normalized_without_granting_any_right(): void
    {
        $upper = new LeadSubmissionIdentity(
            strtoupper('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'), self::BINDING, self::SUBMISSION_A,
        );
        $lower = new LeadSubmissionIdentity('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', self::BINDING, self::SUBMISSION_A);

        self::assertSame('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $upper->organizationReference);
        self::assertTrue($upper->sameLogicalDeliveryAs($lower));
    }

    public static function invalidIds(): array
    {
        return [
            'empty' => [''],
            'whitespace' => [' '],
            'not uuid' => ['my-submission'],
            'nil uuid' => ['00000000-0000-0000-0000-000000000000'],
            'long' => ['11111111-1111-4111-8111-111111111111-x'],
            'line break' => ["11111111-1111-4111-8111-111111111111\n"],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_malformed_reference_is_rejected(string $bad): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LeadSubmissionIdentity(self::ORG, self::BINDING, $bad);
    }

    public function test_missing_origin_fields_are_not_invented(): void
    {
        $source = new LeadSourceSnapshot(site: 'site-exemplo', form: 'contato');

        self::assertSame('site-exemplo', $source->site);
        self::assertSame('contato', $source->form);
        self::assertNull($source->page);
        self::assertNull($source->formVersion);
        self::assertNull($source->campaign);
    }

    public function test_every_origin_attribute_may_be_absent(): void
    {
        $source = new LeadSourceSnapshot();

        self::assertNull($source->site);
        self::assertNull($source->campaign);
    }

    public static function malformedSources(): array
    {
        return [
            'empty text' => [''],
            'spaces only' => ['   '],
            'multiline' => ["fake\nsource"],
            'null byte' => ["source\x00"],
            'too long' => [str_repeat('x', 256)],
        ];
    }

    #[DataProvider('malformedSources')]
    public function test_invalid_origin_text_is_not_used_as_evidence(string $bad): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LeadSourceSnapshot(campaign: $bad);
    }

    public function test_universal_lead_draft_does_not_invent_form_requirements(): void
    {
        $empty = new LeadDraftFields();
        self::assertNull($empty->name);
        self::assertNull($empty->email);
        self::assertNull($empty->phone);

        $onlyName = new LeadDraftFields(name: 'Lead Exemplo');
        self::assertSame('Lead Exemplo', $onlyName->name);
        self::assertNull($onlyName->email);
    }

    public function test_blank_supplied_contact_fails_closed_without_automatic_repair(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LeadDraftFields(email: ' ');
    }

    public function test_control_characters_are_not_accepted_in_optional_contact(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LeadDraftFields(phone: "+55\n");
    }

    public function test_source_and_contact_drafts_do_not_create_or_change_delivery_identity(): void
    {
        $key = new LeadSubmissionIdentity(self::ORG, self::BINDING, self::SUBMISSION_A);
        new LeadSourceSnapshot(campaign: 'example');
        new LeadDraftFields(name: 'Lead Exemplo');

        self::assertSame(self::SUBMISSION_A, $key->submissionReference);
    }
}
