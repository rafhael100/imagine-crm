<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use RuntimeException;

/** Closed P27 receipt contract. A received event is not a dispatched event. */
final class AgentsReceipt
{
    private const QUERY_FIELDS = [
        'schema', 'issuer', 'audience', 'product', 'operation', 'company_id',
        'tenant_ref', 'event_ref', 'payload_hash', 'request_ref', 'nonce_ref',
        'issued_at', 'expires_at', 'key_id',
    ];

    private const RECEIPT_FIELDS = [
        'schema', 'issuer', 'audience', 'company_id', 'tenant_ref', 'event_ref',
        'payload_hash', 'state', 'dispatch', 'processing', 'request_ref',
        'nonce_ref', 'issued_at', 'expires_at', 'key_id',
    ];

    public static function query(string $company, string $tenantRef, string $eventRef,
        string $payloadHash, string $ingressKey, int $now, string $requestRef,
        string $nonceRef): array
    {
        self::key($ingressKey);
        $query = [
            'schema' => 'AUTO_IA_RECEIPT_QUERY_V1',
            'issuer' => 'imagine-crm',
            'audience' => 'imagine-ai-agents',
            'product' => 'imagine-crm',
            'operation' => 'event.receipt.read',
            'company_id' => $company,
            'tenant_ref' => $tenantRef,
            'event_ref' => $eventRef,
            'payload_hash' => $payloadHash,
            'request_ref' => $requestRef,
            'nonce_ref' => $nonceRef,
            'issued_at' => $now,
            'expires_at' => $now + 30,
            'key_id' => 'SYNTHETIC_KEY_A',
        ];
        $query['signature'] = hash_hmac('sha256',
            self::canonical('IMAGINE_AGENTS_EVENT_RECEIPT_QUERY_V1', self::QUERY_FIELDS, $query),
            $ingressKey);
        self::queryScope($query, $now);

        return $query;
    }

    /** Return the verified wire digest, suitable for the existing durable receipt column. */
    public static function validate(string $raw, array $query, string $receiptKey, int $now): string
    {
        self::key($receiptKey);
        self::queryScope($query, $now);
        $receipt = StrictJson::decodeObject($raw, 4096, 8);
        self::shape($receipt, self::RECEIPT_FIELDS);
        foreach ([
            'schema' => 'AUTO_IA_EVENT_RECEIPT_V1',
            'issuer' => 'imagine-ai-agents',
            'audience' => 'imagine-crm',
            'state' => 'RECEIVED',
            'dispatch' => 'DISABLED',
            'processing' => 'NOT_DISPATCHED',
            'key_id' => 'SYNTHETIC_P27_RECEIPT_KEY',
        ] as $field => $expected) {
            if ($receipt[$field] !== $expected) {
                throw new RuntimeException('RECEIPT_SCOPE');
            }
        }
        foreach (['company_id', 'tenant_ref', 'event_ref', 'payload_hash',
            'request_ref', 'nonce_ref'] as $field) {
            if ($receipt[$field] !== $query[$field]) {
                throw new RuntimeException('RECEIPT_BINDING');
            }
        }
        if ($receipt['issued_at'] < $now - 10 || $receipt['issued_at'] > $now + 5
            || $receipt['expires_at'] <= $now
            || $receipt['expires_at'] <= $receipt['issued_at']
            || $receipt['expires_at'] > $receipt['issued_at'] + 30
            || $receipt['expires_at'] > $query['expires_at']) {
            throw new RuntimeException('RECEIPT_WINDOW');
        }
        $expected = hash_hmac('sha256',
            self::canonical('IMAGINE_AGENTS_EVENT_RECEIPT_V1', self::RECEIPT_FIELDS, $receipt),
            $receiptKey);
        if (!hash_equals($expected, $receipt['signature'])) {
            throw new RuntimeException('RECEIPT_SIGNATURE');
        }

        return hash('sha256', $raw);
    }

    private static function key(string $key): void
    {
        if (strlen($key) < 32 || strlen($key) > 256) {
            throw new RuntimeException('RECEIPT_KEY');
        }
    }

    private static function shape(array $message, array $fields): void
    {
        $expected = [...$fields, 'signature'];
        $actual = array_keys($message);
        sort($expected);
        sort($actual);
        if ($expected !== $actual) {
            throw new RuntimeException('RECEIPT_SCHEMA');
        }
        foreach ($expected as $field) {
            if (in_array($field, ['issued_at', 'expires_at'], true)) {
                if (!is_int($message[$field]) || $message[$field] < 0
                    || $message[$field] > 4102444800) {
                    throw new RuntimeException('RECEIPT_TYPE');
                }
            } elseif (!is_string($message[$field])) {
                throw new RuntimeException('RECEIPT_TYPE');
            }
        }
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $message['signature'])) {
            throw new RuntimeException('RECEIPT_SIGNATURE');
        }
    }

    private static function queryScope(array $query, int $now): void
    {
        self::shape($query, self::QUERY_FIELDS);
        foreach ([
            'schema' => 'AUTO_IA_RECEIPT_QUERY_V1', 'issuer' => 'imagine-crm',
            'audience' => 'imagine-ai-agents', 'product' => 'imagine-crm',
            'operation' => 'event.receipt.read', 'key_id' => 'SYNTHETIC_KEY_A',
        ] as $field => $expected) {
            if ($query[$field] !== $expected) {
                throw new RuntimeException('RECEIPT_QUERY_SCOPE');
            }
        }
        if (!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $query['company_id'])
            || !preg_match('/\A[a-f0-9]{64}\z/D', $query['payload_hash'])) {
            throw new RuntimeException('RECEIPT_QUERY_SCOPE');
        }
        foreach (['tenant_ref', 'event_ref', 'request_ref', 'nonce_ref'] as $field) {
            if (!preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,48}\z/D', $query[$field])) {
                throw new RuntimeException('RECEIPT_QUERY_SCOPE');
            }
        }
        if ($query['issued_at'] < $now - 30 || $query['issued_at'] > $now + 5
            || $query['expires_at'] <= $now || $query['expires_at'] <= $query['issued_at']
            || $query['expires_at'] - $query['issued_at'] > 60) {
            throw new RuntimeException('RECEIPT_QUERY_WINDOW');
        }
    }

    private static function canonical(string $domain, array $fields, array $message): string
    {
        return $domain . "\n" . implode("\n", array_map(
            static fn (string $field): string => (string) $message[$field], $fields));
    }
}
