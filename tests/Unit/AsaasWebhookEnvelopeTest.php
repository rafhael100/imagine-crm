<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Billing\AsaasWebhookEnvelope;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AsaasWebhookEnvelopeTest extends TestCase
{
    private const TOKEN = 'test-only-asaas-webhook-auth-token-over-32-bytes';
    private const KEY = 'test-only-hmac-key-for-asaas-identity-binding-32bytes';

    private static function body(
        string $id = 'evt_0123456789abcdef',
        string $kind = 'PAYMENT_RECEIVED',
        string $resource = 'pay_111111111111',
    ): string {
        return json_encode([
            'id' => $id, 'event' => $kind,
            'payment' => [
                'id' => $resource, 'customer' => 'cus_should_not_persist',
                'description' => 'PERSONALLY IDENTIFIABLE should never reach envelope',
                'value' => 999.90,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private static function headers(): array
    {
        return [
            'Asaas-Access-Token' => self::TOKEN,
            'Content-Type' => 'application/json',
        ];
    }

    public function test_authentication_and_provider_resource_bindings_are_purpose_separated(): void
    {
        $event = AsaasWebhookEnvelope::parse(
            self::headers(), self::body(), self::TOKEN, self::KEY,
        );
        self::assertSame('PAYMENT_RECEIVED', $event->eventKind);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $event->eventKey);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $event->resourceKey);
        self::assertNotSame($event->eventKey, $event->resourceKey);
        self::assertNotSame(hash('sha256', 'evt_0123456789abcdef'), $event->eventKey);

        $serialized = json_encode($event, JSON_THROW_ON_ERROR);
        foreach (['cus_should_not_persist', 'PERSONALLY IDENTIFIABLE', 'pay_111111111111',
            'evt_0123456789abcdef', self::KEY, self::TOKEN, '999.9'] as $private) {
            self::assertStringNotContainsString($private, $serialized);
        }
        self::assertFalse(method_exists($event, 'activate'));
        self::assertFalse(method_exists($event, 'settlePayment'));
    }

    public function test_exact_same_event_has_same_binding_even_on_repeated_delivery(): void
    {
        $original = AsaasWebhookEnvelope::parse(self::headers(), self::body(), self::TOKEN, self::KEY);
        $duplicate = AsaasWebhookEnvelope::parse(self::headers(), self::body(), self::TOKEN, self::KEY);
        self::assertSame($original->eventKey, $duplicate->eventKey);
        self::assertSame($original->resourceKey, $duplicate->resourceKey);
        self::assertSame($original->bodyDigest, $duplicate->bodyDigest);

        $other = AsaasWebhookEnvelope::parse(
            self::headers(), self::body('evt_abcdef1234567890'), self::TOKEN, self::KEY,
        );
        self::assertNotSame($original->eventKey, $other->eventKey);
    }

    public function test_subscription_is_read_as_subscription_not_payment(): void
    {
        $json = json_encode([
            'id' => 'evt_sub_123456789', 'event' => 'SUBSCRIPTION_INACTIVATED',
            'subscription' => ['id' => 'sub_009988776655', 'customer' => 'private'],
        ], JSON_THROW_ON_ERROR);
        $out = AsaasWebhookEnvelope::parse(
            ['asaas-access-token' => self::TOKEN, 'content-type' => 'application/json; charset=utf-8'],
            $json, self::TOKEN, self::KEY,
        );
        self::assertSame('SUBSCRIPTION_INACTIVATED', $out->eventKind);
        self::assertStringNotContainsString('private', json_encode($out, JSON_THROW_ON_ERROR));
    }

    public function test_tokens_headers_and_content_type_are_fail_closed(): void
    {
        foreach ([
            [],
            ['Content-Type' => 'application/json'],
            ['asaas-access-token' => 'wrong-password', 'Content-Type' => 'application/json'],
            ['asaas-access-token' => [self::TOKEN, self::TOKEN], 'Content-Type' => 'application/json'],
            ['asaas-access-token' => self::TOKEN,
                'Asaas-Access-Token' => self::TOKEN, 'Content-Type' => 'application/json'],
            ['asaas-access-token' => self::TOKEN, 'Content-Type' => 'application/x-www-form-urlencoded'],
            ['asaas-access-token' => self::TOKEN, 'Content-Type' => 'text/plain'],
        ] as $headers) {
            try {
                AsaasWebhookEnvelope::parse($headers, self::body(), self::TOKEN, self::KEY);
                self::fail('Untrusted webhook headers accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertStringNotContainsString(self::TOKEN, $e->getMessage());
            }
        }

        foreach (['short', str_repeat('a', 256), 'white space token '.str_repeat('a', 32)] as $token) {
            $this->expectInvalidToken($token);
        }
    }

    private function expectInvalidToken(string $token): void
    {
        try {
            AsaasWebhookEnvelope::parse(self::headers(), self::body(), $token, self::KEY);
            self::fail('Invalid configured webhook token accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Configuracao de webhook indisponivel.', $e->getMessage());
        }
    }

    public function test_invalid_payload_is_rejected_without_returning_values(): void
    {
        foreach ([
            '', '{', '[]', '{}',
            json_encode(['id' => 'evt_1234567890', 'event' => 'PAYMENT_RECEIVED']),
            json_encode(['id' => 'evt_1234567890', 'event' => 'PAYMENT_RECEIVED',
                'payment' => ['id' => 'short']]),
            json_encode(['id' => 'evt_1234567890', 'event' => 'BANK_ACCOUNT_CHANGED',
                'payment' => ['id' => 'pay_0123456789']]),
            json_encode(['id' => 'evt_1234567890', 'event' => 'PAYMENT_RECEIVED',
                'payment' => ['id' => 'pay_0123456789; SQL DROP TABLE']]),
            json_encode(['id' => str_repeat('e', 161), 'event' => 'PAYMENT_RECEIVED',
                'payment' => ['id' => 'pay_0123456789']]),
            str_repeat('x', AsaasWebhookEnvelope::MAX_BODY_BYTES + 1),
        ] as $body) {
            try {
                AsaasWebhookEnvelope::parse(self::headers(), (string) $body, self::TOKEN, self::KEY);
                self::fail('Bad incoming event accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Evento de webhook invalido.', $e->getMessage());
            }
        }
    }

    public function test_no_current_webhook_event_is_equivalent_to_activation_authority(): void
    {
        foreach ([
            'PAYMENT_CREATED', 'PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED',
            'PAYMENT_OVERDUE', 'PAYMENT_REFUNDED', 'PAYMENT_CHARGEBACK_REQUESTED',
        ] as $kind) {
            $event = AsaasWebhookEnvelope::parse(
                self::headers(), self::body('evt_0123456789abcdef', $kind), self::TOKEN, self::KEY,
            );
            self::assertSame($kind, $event->eventKind);
            self::assertFalse(method_exists($event, 'register'));
            self::assertFalse(method_exists($event, 'activateSubscription'));
        }
    }
}
