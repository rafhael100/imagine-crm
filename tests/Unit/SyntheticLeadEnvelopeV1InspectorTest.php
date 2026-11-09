<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadEnvelopeV1Inspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadEnvelopeV1InspectorTest extends TestCase
{
    private static function candidate(): array
    {
        return [
            'protocol' => 'imagine.lead-intake',
            'schema_version' => 1,
            'organization_reference' => '11111111-1111-4111-8111-111111111111',
            'binding_reference' => '22222222-2222-4222-8222-222222222222',
            'submission_reference' => '33333333-3333-4333-8333-333333333333',
            'site_reference' => '44444444-4444-4444-8444-444444444444',
            'form_reference' => '55555555-5555-4555-8555-555555555555',
            'destination_reference' => '66666666-6666-4666-8666-666666666666',
        ];
    }

    public function test_valid_metadata_is_still_untrusted_and_never_accepted(): void
    {
        $result = (new SyntheticLeadEnvelopeV1Inspector())->inspect(self::candidate());

        self::assertSame('STRUCTURE_ONLY_UNVERIFIED', $result->reasonCode);
        self::assertTrue($result->isStructurallyCompatible());
        self::assertFalse($result->canAcceptSubmission());
        self::assertFalse($result->canAcknowledgeDurableReceipt());
        self::assertFalse($result->canDeliverToCrm());
        self::assertStringNotContainsString('11111111', json_encode($result, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('33333333', json_encode($result, JSON_THROW_ON_ERROR));
    }

    public function test_key_order_is_not_authority_or_a_contract_requirement(): void
    {
        $reordered = array_reverse(self::candidate(), true);
        $result = (new SyntheticLeadEnvelopeV1Inspector())->inspect($reordered);

        self::assertTrue($result->isStructurallyCompatible());
        self::assertFalse($result->canAcceptSubmission());
    }

    public static function invalidCandidates(): array
    {
        $good = self::candidate();
        return [
            'not a map' => ['example', 'ENVELOPE_STRUCTURE_INVALID'],
            'list instead of map' => [array_values($good), 'ENVELOPE_STRUCTURE_INVALID'],
            'empty map' => [[], 'ENVELOPE_STRUCTURE_INVALID'],
            'missing binding' => [array_diff_key($good, ['binding_reference' => true]), 'ENVELOPE_STRUCTURE_INVALID'],
            'missing site' => [array_diff_key($good, ['site_reference' => true]), 'ENVELOPE_STRUCTURE_INVALID'],
            'extra payload' => [$good + ['payload' => ['email' => 'example@example.invalid']], 'ENVELOPE_STRUCTURE_INVALID'],
            'extra privacy flag' => [$good + ['consent' => true], 'ENVELOPE_STRUCTURE_INVALID'],
            'extra caller timestamp' => [$good + ['first_persisted_at' => '2026-11-08T20:00:00Z'], 'ENVELOPE_STRUCTURE_INVALID'],
            'wrong protocol' => [array_replace($good, ['protocol' => 'fake']), 'PROTOCOL_UNSUPPORTED'],
            'future protocol' => [array_replace($good, ['protocol' => 'imagine.lead-intake.v2']), 'PROTOCOL_UNSUPPORTED'],
            'string version' => [array_replace($good, ['schema_version' => '1']), 'VERSION_UNSUPPORTED'],
            'future version' => [array_replace($good, ['schema_version' => 2]), 'VERSION_UNSUPPORTED'],
            'null version' => [array_replace($good, ['schema_version' => null]), 'VERSION_UNSUPPORTED'],
            'null org' => [array_replace($good, ['organization_reference' => null]), 'REFERENCE_FORMAT_INVALID'],
            'forged nested tenant' => [array_replace($good, ['organization_reference' => ['id' => $good['organization_reference']]]), 'REFERENCE_FORMAT_INVALID'],
            'invalid destination' => [array_replace($good, ['destination_reference' => 'other-tenant']), 'REFERENCE_FORMAT_INVALID'],
            'nil submission' => [array_replace($good, ['submission_reference' => '00000000-0000-0000-0000-000000000000']), 'REFERENCE_FORMAT_INVALID'],
            'whitespace id' => [array_replace($good, ['site_reference' => " ".$good['site_reference']]), 'REFERENCE_FORMAT_INVALID'],
        ];
    }

    #[DataProvider('invalidCandidates')]
    public function test_malformed_or_extra_fields_fail_closed(mixed $candidate, string $reason): void
    {
        $result = (new SyntheticLeadEnvelopeV1Inspector())->inspect($candidate);

        self::assertSame($reason, $result->reasonCode);
        self::assertFalse($result->isStructurallyCompatible());
        self::assertFalse($result->canAcceptSubmission());
        self::assertFalse($result->canAcknowledgeDurableReceipt());
        self::assertFalse($result->canDeliverToCrm());
    }

    public function test_distinct_submission_ids_are_not_deduplicated_by_shape(): void
    {
        $first = self::candidate();
        $other = array_replace($first, [
            'submission_reference' => '77777777-7777-4777-8777-777777777777',
        ]);
        $inspector = new SyntheticLeadEnvelopeV1Inspector();

        self::assertTrue($inspector->inspect($first)->isStructurallyCompatible());
        self::assertTrue($inspector->inspect($other)->isStructurallyCompatible());
        self::assertNotSame($first['submission_reference'], $other['submission_reference']);
        self::assertFalse($inspector->inspect($other)->canDeliverToCrm());
    }
}
