<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\{
    AiaHmacFixturePreflight, AiaFixtureNonceLedger, AiaReadOnlyMockBoundary
};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AiaHmacFixturePreflightTest extends TestCase
{
    private const KEY = 'SYNTHETIC_PUBLIC_TEST_ONLY_F3_AIA_KNOWN_NON_SECRET_FIXTURE_KEY_123456789';
    private const TENANT = 'SYNTHETIC_TENANT_A';
    private const NOW = 1794690000;

    private static function bytes(): string
    {
        return json_encode([
            'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref' => 'SYNTHETIC_HMAC_REQUEST',
            'operation' => 'crm.lead.read_minimal',
            'tenant_ref' => self::TENANT,
            'principal_ref' => 'SYNTHETIC_PRINCIPAL',
            'resource_ref' => 'SYNTHETIC_LEAD_A',
            'purpose_ref' => 'SYNTHETIC_TEST',
            'fields' => ['ref', 'stage'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function proof(
        string $body,
        string $nonce = 'SYNTHETIC_NONCE_AAAA',
        int $issued = self::NOW - 5,
        int $expires = self::NOW + 20,
        string $key = self::KEY,
    ): array {
        $digest = hash('sha256', $body);
        $payload = [
            'issuer' => 'SYNTHETIC_AGENT_SERVICE',
            'audience' => 'SYNTHETIC_CRM_API',
            'tenant_ref' => self::TENANT,
            'nonce' => $nonce,
            'issued_at' => $issued,
            'expires_at' => $expires,
            'body_sha256' => $digest,
        ];
        $message = implode("\n", [
            'SYNTHETIC_AIA_HMAC_PROOF_V1',
            ...array_values($payload),
        ]);
        $payload['signature'] = hash_hmac('sha256', $message, $key);
        return $payload;
    }

    private static function matches(
        string $body,
        array $claims,
        ?AiaFixtureNonceLedger $ledger = null,
        string $expectedTenant = self::TENANT,
        int $now = self::NOW,
        string $key = self::KEY,
    ): bool {
        return AiaHmacFixturePreflight::matchesFixture(
            $body, $claims, $expectedTenant, $now,
            $key, $ledger ?? new AiaFixtureNonceLedger()
        );
    }

    public function test_signed_but_public_fixture_proves_only_bytes_not_access(): void
    {
        $body = self::bytes();
        $p = self::proof($body);
        $store = new AiaFixtureNonceLedger();
        self::assertTrue(self::matches($body, $p, $store));
        self::assertSame(1, $store->countConsumed());
        self::assertFalse(self::matches($body, $p, $store));
        self::assertSame(1, $store->countConsumed());
        $fresh = self::proof($body, 'SYNTHETIC_NONCE_BBBB');
        self::assertSame(
            AiaReadOnlyMockBoundary::inspectCanonicalJson($body),
            AiaHmacFixturePreflight::inspectFixture(
                $body, $fresh, self::TENANT, self::NOW, self::KEY, $store
            )
        );
        self::assertSame(2, $store->countConsumed());
        self::assertSame('DENIED_OR_NOT_FOUND',
            AiaHmacFixturePreflight::inspectFixture(
                $body, $fresh, self::TENANT, self::NOW, self::KEY, $store
            )['state']);
    }

    public static function invalidClaims(): array
    {
        return [
            'wrong issuer' => [['issuer' => 'SYNTHETIC_ANOTHER_ISSUER']],
            'wrong audience' => [['audience' => 'SYNTHETIC_ANOTHER_AUD']],
            'wrong tenant' => [['tenant_ref' => 'SYNTHETIC_TENANT_B']],
            'wrong nonce' => [['nonce' => 'SYNTHETIC_NONCE_CHANGED']],
            'missing nonce' => [['nonce' => null]],
            'not yet valid' => [['issued_at' => self::NOW + 1]],
            'expired' => [['expires_at' => self::NOW]],
            'excess lifetime' => [['expires_at' => self::NOW + 31]],
            'empty signature' => [['signature' => '']],
            'uppercase signature' => [['signature' => str_repeat('A',64)]],
            'digest forgery' => [['body_sha256' => str_repeat('0',64)]],
            'boolean time' => [['issued_at' => true]],
            'floating time' => [['issued_at' => 1794690000.0]],
            'unknown field' => [['role' => 'ADMIN']],
        ];
    }

    #[DataProvider('invalidClaims')]
    public function test_forged_claims_never_consume_nonce(array $overrides): void
    {
        $body = self::bytes();
        $proof = array_replace(self::proof($body), $overrides);
        $store = new AiaFixtureNonceLedger();
        self::assertFalse(self::matches($body, $proof, $store));
        self::assertSame(0, $store->countConsumed());
    }

    public function test_signed_payload_cannot_be_used_for_another_tenant_or_body(): void
    {
        $body = self::bytes();
        $p = self::proof($body);
        $store = new AiaFixtureNonceLedger();
        self::assertFalse(self::matches($body,$p,$store,'SYNTHETIC_TENANT_B'));
        self::assertFalse(self::matches(
            str_replace('SYNTHETIC_LEAD_A','SYNTHETIC_LEAD_B',$body),$p,$store));
        self::assertFalse(self::matches($body . '{}',$p,$store));
        self::assertSame(0, $store->countConsumed());
    }

    public function test_bad_key_and_signature_cannot_be_substituted(): void
    {
        $body=self::bytes();
        $p=self::proof($body);
        self::assertFalse(self::matches($body,$p,key:'not-a-secret'));
        self::assertFalse(self::matches($body,$p,key:
            'SYNTHETIC_PUBLIC_TEST_ONLY_ANOTHER_KNOWN_TEST_KEY_123456789'));
        self::assertFalse(self::matches($body,self::proof($body,key:
            'SYNTHETIC_PUBLIC_TEST_ONLY_OTHER_SIGNING_KEY_123456789')));
    }

    public function test_strict_expiration_includes_end_second_and_rejects_future(): void
    {
        $body=self::bytes();
        self::assertFalse(self::matches($body,self::proof($body),now:self::NOW+20));
        self::assertFalse(self::matches($body,self::proof($body),now:self::NOW-6));
        self::assertTrue(self::matches($body,self::proof($body),now:self::NOW+19));
    }

    public function test_no_replay_after_outage_or_saturation(): void
    {
        $body=self::bytes();
        $ledger=new AiaFixtureNonceLedger(1);
        $p=self::proof($body);
        self::assertTrue(self::matches($body,$p,$ledger));
        $ledger->setAvailable(false);
        self::assertFalse(self::matches(
            $body,self::proof($body,'SYNTHETIC_NONCE_BBBB'),$ledger));
        $ledger->setAvailable(true);
        self::assertFalse(self::matches($body,$p,$ledger));
        self::assertFalse(self::matches(
            $body,self::proof($body,'SYNTHETIC_NONCE_BBBB'),$ledger));
        self::assertSame(1,$ledger->countConsumed());
    }

    public function test_inconsistent_ledger_capacity_is_denied(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AiaFixtureNonceLedger(0);
    }
}
