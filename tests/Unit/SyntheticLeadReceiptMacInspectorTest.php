<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticLeadReceiptMacInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadReceiptMacInspectorTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const BINDING = '22222222-2222-4222-8222-222222222222';
    private const SUBMISSION = '33333333-3333-4333-8333-333333333333';
    private const SITE = '44444444-4444-4444-8444-444444444444';
    private const FORM = '55555555-5555-4555-8555-555555555555';
    private const DESTINATION = '66666666-6666-4666-8666-666666666666';
    private const ALTERNATIVE = '77777777-7777-4777-8777-777777777777';
    private const NONCE = '88888888-8888-4888-8888-888888888888';
    private const OTHER_NONCE = '99999999-9999-4999-8999-999999999999';
    private const KEY = 'fixture-only-no-production-secret-32-bytes-minimum';
    private const ISSUED = 1800000000;
    private const EXPIRES = 1800000300;

    private static function claim(
        string $tenant = self::TENANT,
        string $binding = self::BINDING,
        string $submission = self::SUBMISSION,
        string $site = self::SITE,
        string $form = self::FORM,
        string $destination = self::DESTINATION,
    ): SyntheticIdempotencyClaim {
        return new SyntheticIdempotencyClaim(
            new LeadSubmissionIdentity($tenant, $binding, $submission),
            $site,
            $form,
            $destination,
        );
    }

    /** Test fixture only. This is NOT a signing API or real key management. */
    private static function signed(
        ?SyntheticIdempotencyClaim $claim = null,
        int $issuedAt = self::ISSUED,
        int $expiresAt = self::EXPIRES,
        string $nonce = self::NONCE,
        string $key = self::KEY,
    ): string {
        $claim ??= self::claim();
        return hash_hmac('sha256', implode("\n", [
            'imagine.lead-intake.synthetic-receipt.v1',
            $claim->identity->organizationReference,
            $claim->identity->bindingReference,
            $claim->identity->submissionReference,
            $claim->siteReference,
            $claim->formReference,
            $claim->destinationReference,
            (string) $issuedAt,
            (string) $expiresAt,
            $nonce,
        ]), $key);
    }

    private static function inspect(
        ?SyntheticIdempotencyClaim $expected = null,
        ?SyntheticIdempotencyClaim $reported = null,
        int $issuedAt = self::ISSUED,
        int $expiresAt = self::EXPIRES,
        int $now = self::ISSUED + 60,
        mixed $nonce = self::NONCE,
        mixed $signature = null,
        string $key = self::KEY,
    ): \App\Core\Leads\SyntheticLeadReceiptMacAssessment {
        return (new SyntheticLeadReceiptMacInspector())->inspect(
            $expected ?? self::claim(),
            $reported ?? self::claim(),
            $issuedAt,
            $expiresAt,
            $now,
            $nonce,
            $signature ?? self::signed(),
            $key,
        );
    }

    public function test_matching_fixture_hmac_still_does_not_prove_access_or_receipt(): void
    {
        $result = self::inspect();

        self::assertSame('LAB_MAC_MATCH_UNTRUSTED', $result->reasonCode);
        self::assertTrue($result->isMacConsistentWithFixture());
        self::assertFalse($result->canAcceptLead());
        self::assertFalse($result->canAcknowledgeDurableReceipt());
        self::assertFalse($result->canDeliverToCrm());
        self::assertFalse($result->canTreatAsReplaySafe());
    }

    public static function pinnedContextChanges(): array
    {
        return [
            'tenant' => [self::ALTERNATIVE, self::BINDING, self::SUBMISSION, self::SITE, self::FORM, self::DESTINATION],
            'binding' => [self::TENANT, self::ALTERNATIVE, self::SUBMISSION, self::SITE, self::FORM, self::DESTINATION],
            'submission' => [self::TENANT, self::BINDING, self::ALTERNATIVE, self::SITE, self::FORM, self::DESTINATION],
            'site' => [self::TENANT, self::BINDING, self::SUBMISSION, self::ALTERNATIVE, self::FORM, self::DESTINATION],
            'form' => [self::TENANT, self::BINDING, self::SUBMISSION, self::SITE, self::ALTERNATIVE, self::DESTINATION],
            'CRM destination' => [self::TENANT, self::BINDING, self::SUBMISSION, self::SITE, self::FORM, self::ALTERNATIVE],
        ];
    }

    #[DataProvider('pinnedContextChanges')]
    public function test_changes_to_original_routing_always_fail_closed(
        string $tenant, string $binding, string $submission,
        string $site, string $form, string $destination,
    ): void {
        $forged = self::claim($tenant, $binding, $submission, $site, $form, $destination);
        $result = self::inspect(reported: $forged, signature: self::signed($forged));
        self::assertSame('PINNED_CONTEXT_MISMATCH', $result->reasonCode);
        self::assertFalse($result->canDeliverToCrm());
    }

    public function test_wrong_key_and_tampered_nonce_do_not_match_hmac(): void
    {
        self::assertSame('MAC_MISMATCH', self::inspect(
            signature: self::signed(), key: str_repeat('x', 40),
        )->reasonCode);
        self::assertSame('MAC_MISMATCH', self::inspect(
            nonce: self::OTHER_NONCE, signature: self::signed(),
        )->reasonCode);
    }

    public function test_replay_of_identical_mac_cannot_be_detected_without_atomic_nonce_store(): void
    {
        $a = self::inspect();
        $b = self::inspect();

        self::assertSame('LAB_MAC_MATCH_UNTRUSTED', $a->reasonCode);
        self::assertSame($a->reasonCode, $b->reasonCode);
        self::assertFalse($a->canTreatAsReplaySafe());
        self::assertFalse($b->canAcceptLead());
    }

    public static function invalidClockWindows(): array
    {
        return [
            'expired equal now' => [self::ISSUED, self::EXPIRES, self::EXPIRES],
            'expired after now' => [self::ISSUED, self::EXPIRES, self::EXPIRES + 1],
            'issue in future beyond skew' => [self::ISSUED + 90, self::EXPIRES, self::ISSUED],
            'negative issue timestamp' => [-1, self::EXPIRES, self::ISSUED],
            'zero now' => [self::ISSUED, self::EXPIRES, 0],
            'inverted expiration' => [self::ISSUED, self::ISSUED, self::ISSUED],
            'too long TTL' => [self::ISSUED, self::ISSUED + 301, self::ISSUED + 1],
        ];
    }

    #[DataProvider('invalidClockWindows')]
    public function test_invalid_or_expired_windows_fail_closed(int $issued, int $expires, int $now): void
    {
        $result = self::inspect(issuedAt: $issued, expiresAt: $expires, now: $now);
        self::assertSame('TIME_WINDOW_INVALID', $result->reasonCode);
        self::assertFalse($result->canAcknowledgeDurableReceipt());
    }

    public function test_future_skew_of_thirty_seconds_is_a_provisional_lab_boundary(): void
    {
        $issued = self::ISSUED + 30;
        $expires = $issued + 300;
        $result = self::inspect(
            issuedAt: $issued, expiresAt: $expires, now: self::ISSUED,
            signature: self::signed(issuedAt: $issued, expiresAt: $expires),
        );
        self::assertSame('LAB_MAC_MATCH_UNTRUSTED', $result->reasonCode);
        self::assertFalse($result->canAcceptLead());
    }

    public function test_invalid_nonce_signature_and_short_key_fail_closed(): void
    {
        self::assertSame('NONCE_FORMAT_INVALID', self::inspect(nonce: 'not-a-uuid')->reasonCode);
        self::assertSame('NONCE_FORMAT_INVALID', self::inspect(nonce: null)->reasonCode);
        self::assertSame('NONCE_FORMAT_INVALID', self::inspect(
            nonce: '00000000-0000-0000-0000-000000000000',
        )->reasonCode);
        self::assertSame('CRYPTO_INPUT_INVALID', self::inspect(signature: 'not-a-mac')->reasonCode);
        self::assertSame('CRYPTO_INPUT_INVALID', self::inspect(signature: strtoupper(self::signed()))->reasonCode);
        self::assertSame('CRYPTO_INPUT_INVALID', self::inspect(key: 'weak')->reasonCode);
    }

    public function test_result_contains_only_code_and_never_leaks_fixture_secret_or_ids(): void
    {
        $result = self::inspect();
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        foreach ([self::KEY, self::TENANT, self::SUBMISSION, self::DESTINATION, self::NONCE] as $sensitive) {
            self::assertStringNotContainsString($sensitive, $json);
        }
        self::assertSame(['reasonCode'], array_keys((array) $result));
        self::assertFalse($result->canDeliverToCrm());
    }
}
