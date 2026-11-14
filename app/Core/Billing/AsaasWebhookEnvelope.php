<?php

declare(strict_types=1);

namespace App\Core\Billing;

use InvalidArgumentException;
use JsonException;

/**
 * BILL-ASAAS-01: small, reusable security boundary for inbound Asaas v3 events.
 *
 * Pure PHP: no HTTP routes, HTTP calls, provisioning, payment settlement,
 * customer/tenant lookup, money, logs, permission grants or persistence.
 * In particular, RECEIVED/CONFIRMED are NOT authority to activate an account.
 *
 * The returned HMACs are still private pseudonymous identifiers; callers
 * must never put them in logs, API responses or public analytics.
 */
final readonly class AsaasWebhookEnvelope
{
    public const MAX_BODY_BYTES = 65536;

    private const EVENT_KINDS = [
        'PAYMENT_CREATED',
        'PAYMENT_UPDATED',
        'PAYMENT_CONFIRMED',
        'PAYMENT_RECEIVED',
        'PAYMENT_OVERDUE',
        'PAYMENT_DELETED',
        'PAYMENT_REFUNDED',
        'PAYMENT_PARTIALLY_REFUNDED',
        'PAYMENT_CHARGEBACK_REQUESTED',
        'SUBSCRIPTION_CREATED',
        'SUBSCRIPTION_UPDATED',
        'SUBSCRIPTION_INACTIVATED',
        'SUBSCRIPTION_DELETED',
    ];

    private function __construct(
        public string $eventKey,
        public string $resourceKey,
        public string $eventKind,
        public string $bodyDigest,
    ) {}

    /**
     * @param array<string,string|string[]> $headers Untrusted headers from HTTP adapter
     *
     * This is an *authenticator + structural filter*, not payment validation.
     * A real payment must be reconciled against a server-owned Asaas record
     * and tenant mapping, using a separately authenticated API client.
     */
    public static function parse(
        array $headers,
        #[\SensitiveParameter] string $body,
        #[\SensitiveParameter] string $webhookAuthToken,
        #[\SensitiveParameter] string $fingerprintKey,
    ): self {
        if (!self::validToken($webhookAuthToken)
            || strlen($fingerprintKey) < 32 || strlen($fingerprintKey) > 128) {
            throw new InvalidArgumentException('Configuracao de webhook indisponivel.');
        }

        $givenToken = self::singleHeader($headers, 'asaas-access-token');
        if (!is_string($givenToken) || !self::validToken($givenToken)
            || !hash_equals($webhookAuthToken, $givenToken)) {
            throw new InvalidArgumentException('Webhook nao autorizado.');
        }

        $type = self::singleHeader($headers, 'content-type');
        if (!is_string($type) || preg_match(
            '/\Aapplication\/json(?:;\s*charset=utf-8)?\z/iD', trim($type),
        ) !== 1) {
            throw new InvalidArgumentException('Evento de webhook invalido.');
        }

        if ($body === '' || strlen($body) > self::MAX_BODY_BYTES) {
            throw new InvalidArgumentException('Evento de webhook invalido.');
        }

        try {
            $json = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Evento de webhook invalido.');
        }

        if (!is_array($json) || array_is_list($json)) {
            throw new InvalidArgumentException('Evento de webhook invalido.');
        }

        $eventId = $json['id'] ?? null;
        $event = $json['event'] ?? null;
        if (!is_string($eventId)
            || preg_match('/\A[a-zA-Z0-9_&.-]{8,160}\z/D', $eventId) !== 1
            || !is_string($event)
            || !in_array($event, self::EVENT_KINDS, true)) {
            throw new InvalidArgumentException('Evento de webhook invalido.');
        }

        $resource = str_starts_with($event, 'PAYMENT_') ? 'payment' : 'subscription';
        $resourceValue = $json[$resource] ?? null;
        if (!is_array($resourceValue) || array_is_list($resourceValue)) {
            throw new InvalidArgumentException('Evento de webhook invalido.');
        }

        $resourceId = $resourceValue['id'] ?? null;
        if (!is_string($resourceId)
            || preg_match('/\A[a-zA-Z0-9_.-]{6,128}\z/D', $resourceId) !== 1) {
            throw new InvalidArgumentException('Evento de webhook invalido.');
        }

        return new self(
            hash_hmac('sha256', 'imagine:asaas:v3:event:'.$eventId, $fingerprintKey),
            hash_hmac('sha256', 'imagine:asaas:v3:'.$resource.':'.$resourceId, $fingerprintKey),
            $event,
            hash('sha256', $body),
        );
    }

    private static function validToken(string $token): bool
    {
        return strlen($token) >= 32 && strlen($token) <= 255
            && preg_match('/\s/', $token) !== 1;
    }

    /**
     * Duplicate case variants are rejected, never silently overwritten.
     * The HTTP adapter must reject multiple raw instances of the same header;
     * here even arrays/duplicate normalized keys are rejected.
     *
     * @param array<string,string|string[]> $headers
     */
    private static function singleHeader(array $headers, string $name): ?string
    {
        $result = null;
        $seen = false;

        foreach ($headers as $key => $value) {
            if (!is_string($key) || strcasecmp($key, $name) !== 0) {
                continue;
            }

            if ($seen || !is_string($value) || $value === '') {
                return null;
            }

            $seen = true;
            $result = $value;
        }

        return $result;
    }
}
