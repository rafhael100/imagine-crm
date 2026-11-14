<?php

declare(strict_types=1);

namespace App\Core\Billing;

use InvalidArgumentException;
use JsonException;

/**
 * BILL-MULTIPSP-01: a signed, non-operational cross-product decision preview.
 * Validation authenticates an OFFLINE message only. It does not authorize
 * a real charge, invoice, subscription, entitlement, or bank API call.
 */
final class PortalGatewayDecisionEnvelope
{
    private const SCHEMA = 'imagine.payment-routing.review.v1';
    private const GATEWAYS = ['asaas', 'c6'];
    private const METHODS = ['pix', 'boleto', 'credit_card', 'pix_automatico'];

    public static function sign(array $fields, #[\SensitiveParameter] string $key): array
    {
        self::validKey($key);
        self::validate($fields);
        return [
            'payload' => $fields,
            'mac' => hash_hmac('sha256', self::canonical($fields), $key),
        ];
    }

    public static function verify(array $envelope, #[\SensitiveParameter] string $key, string $expectedOrganization): array
    {
        self::validKey($key);
        if (array_keys($envelope) !== ['payload', 'mac']
            || !is_array($envelope['payload'])
            || !is_string($envelope['mac'])
            || preg_match('/\A[a-f0-9]{64}\z/D', $envelope['mac']) !== 1) {
            throw new InvalidArgumentException('Invalid gateway message');
        }
        $fields = $envelope['payload'];
        self::validate($fields);
        if (!hash_equals(hash_hmac('sha256', self::canonical($fields), $key), $envelope['mac'])
            || $fields['organization_ref'] !== $expectedOrganization) {
            throw new InvalidArgumentException('Untrusted gateway message');
        }
        return $fields;
    }

    private static function validate(array $fields): void
    {
        $expected = [
            'schema_version', 'organization_ref', 'order_ref', 'gateway',
            'method', 'currency', 'amount_cents', 'estimated_fee_cents',
            'fee_version', 'policy_revision', 'can_charge', 'can_activate', 'environment',
        ];
        $actual = array_keys($fields);
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($actual !== $expected
            || ($fields['schema_version'] ?? null) !== self::SCHEMA
            || ($fields['currency'] ?? null) !== 'BRL'
            || ($fields['environment'] ?? null) !== 'offline_review'
            || ($fields['can_charge'] ?? null) !== false
            || ($fields['can_activate'] ?? null) !== false
            || !in_array($fields['gateway'] ?? null, self::GATEWAYS, true)
            || !in_array($fields['method'] ?? null, self::METHODS, true)
            || !self::token($fields['organization_ref'] ?? null)
            || !self::token($fields['order_ref'] ?? null)
            || !self::token($fields['fee_version'] ?? null)
            || !is_int($fields['policy_revision']) || $fields['policy_revision'] < 1
            || !is_int($fields['amount_cents']) || $fields['amount_cents'] < 1
            || $fields['amount_cents'] > 100_000_000
            || !is_int($fields['estimated_fee_cents']) || $fields['estimated_fee_cents'] < 0
            || $fields['estimated_fee_cents'] > 100_000_000) {
            throw new InvalidArgumentException('Invalid gateway review contract');
        }
    }

    private static function token(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[A-Za-z0-9._:-]{3,128}\z/D', $value) === 1;
    }

    private static function validKey(string $key): void
    {
        if (strlen($key) < 32 || strlen($key) > 128) {
            throw new InvalidArgumentException('Review signing key unavailable');
        }
    }

    private static function canonical(array $payload): string
    {
        ksort($payload, SORT_STRING);
        try {
            return "IMAGINE-GATEWAY-OFFLINE-REVIEW-V1\n".json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid review payload');
        }
    }
}
