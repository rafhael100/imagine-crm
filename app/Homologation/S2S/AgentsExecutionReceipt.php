<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use RuntimeException;

/** Closed execution observation. Admission receipts retain their original meaning. */
final class AgentsExecutionReceipt
{
    private const QUERY_FIELDS = [
        'schema', 'issuer', 'audience', 'product', 'operation', 'company_id',
        'tenant_ref', 'event_ref', 'payload_hash', 'request_ref', 'nonce_ref',
        'issued_at', 'expires_at', 'key_id',
    ];

    private const STATUS_FIELDS = [
        'schema', 'issuer', 'audience', 'company_id', 'tenant_ref', 'event_ref',
        'payload_hash', 'lead_id', 'task_payload_hash', 'execution_state',
        'queue_state', 'result_code', 'observation_hash', 'attempt', 'request_ref',
        'nonce_ref', 'issued_at', 'expires_at', 'key_id',
    ];

    private const OBSERVATION_FIELDS = [
        'company_id', 'tenant_ref', 'event_ref', 'payload_hash', 'lead_id',
        'task_payload_hash', 'execution_state', 'queue_state', 'result_code', 'attempt',
    ];

    public static function query(string $company, string $tenantRef, string $eventRef,
        string $payloadHash, string $ingressKey, int $now, string $requestRef,
        string $nonceRef): array
    {
        self::key($ingressKey);
        $query = [
            'schema' => 'AUTO_IA_EXECUTION_QUERY_V1',
            'issuer' => 'imagine-crm', 'audience' => 'imagine-ai-agents',
            'product' => 'imagine-crm', 'operation' => 'event.execution.read',
            'company_id' => $company, 'tenant_ref' => $tenantRef,
            'event_ref' => $eventRef, 'payload_hash' => $payloadHash,
            'request_ref' => $requestRef, 'nonce_ref' => $nonceRef,
            'issued_at' => $now, 'expires_at' => $now + 30,
            'key_id' => 'SYNTHETIC_KEY_A',
        ];
        $query['signature'] = hash_hmac('sha256',
            self::canonical('IMAGINE_AGENTS_EXECUTION_QUERY_V1', self::QUERY_FIELDS, $query),
            $ingressKey);
        self::queryScope($query, $now);

        return $query;
    }

    /** Verify the response and return its closed facts; the caller retains the exact wire bytes. */
    public static function validate(string $raw, array $query, string $executionKey, int $now): array
    {
        self::key($executionKey);
        self::queryScope($query, $now);
        $status = StrictJson::decodeObject($raw, 4096, 8);
        self::shape($status, self::STATUS_FIELDS);
        foreach ([
            'schema' => 'AUTO_IA_EXECUTION_STATUS_V1', 'issuer' => 'imagine-ai-agents',
            'audience' => 'imagine-crm', 'key_id' => 'SYNTHETIC_EXECUTION_STATUS_KEY',
        ] as $field => $expected) {
            if ($status[$field] !== $expected) {
                throw new RuntimeException('EXECUTION_STATUS_SCOPE');
            }
        }
        foreach (['company_id', 'tenant_ref', 'event_ref', 'payload_hash',
            'request_ref', 'nonce_ref'] as $field) {
            if ($status[$field] !== $query[$field]) {
                throw new RuntimeException('EXECUTION_STATUS_BINDING');
            }
        }
        if (!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\z/D', $status['lead_id'])
            || !preg_match('/\A[a-f0-9]{64}\z/D', $status['observation_hash'])) {
            throw new RuntimeException('EXECUTION_STATUS_FACTS');
        }
        self::state($status);
        if ($status['issued_at'] < $now - 10 || $status['issued_at'] > $now + 5
            || $status['expires_at'] <= $now
            || $status['expires_at'] <= $status['issued_at']
            || $status['expires_at'] > $status['issued_at'] + 30
            || $status['expires_at'] > $query['expires_at']) {
            throw new RuntimeException('EXECUTION_STATUS_WINDOW');
        }
        $observation = hash('sha256', self::canonical(
            'IMAGINE_AGENTS_EXECUTION_OBSERVATION_V1', self::OBSERVATION_FIELDS, $status));
        if (!hash_equals($observation, $status['observation_hash'])) {
            throw new RuntimeException('EXECUTION_OBSERVATION_HASH');
        }
        $signature = hash_hmac('sha256', self::canonical(
            'IMAGINE_AGENTS_EXECUTION_STATUS_V1', self::STATUS_FIELDS, $status), $executionKey);
        if (!hash_equals($signature, $status['signature'])) {
            throw new RuntimeException('EXECUTION_STATUS_SIGNATURE');
        }

        return $status;
    }

    private static function state(array $status): void
    {
        $state = $status['execution_state'];
        $queue = $status['queue_state'];
        $attempt = $status['attempt'];
        $result = $status['result_code'];
        if ($state === 'NOT_STAGED') {
            $valid = $queue === 'NONE' && $attempt === 0 && $result === 'NONE'
                && $status['task_payload_hash'] === '';
        } else {
            $valid = preg_match('/\A[a-f0-9]{64}\z/D', $status['task_payload_hash']) === 1
                && match ($state) {
                    'NOT_STARTED' => in_array($queue, ['PENDING', 'LEASED', 'DEAD'], true)
                        && $attempt === 0 && $result === 'NONE',
                    'STARTED', 'UNKNOWN' => in_array($queue, ['LEASED', 'DEAD'], true)
                        && $attempt >= 1 && $attempt <= 3 && $result === 'NONE',
                    'RESULT' => in_array($queue, ['LEASED', 'DEAD'], true)
                        && $attempt >= 1 && $attempt <= 3 && $result === 'PREPARED',
                    'ACKED' => $queue === 'ACKED' && $attempt >= 1
                        && $attempt <= 3 && $result === 'PREPARED',
                    default => false,
                };
        }
        if (!$valid) {
            throw new RuntimeException('EXECUTION_STATUS_STATE');
        }
    }

    private static function shape(array $message, array $fields): void
    {
        $expected = [...$fields, 'signature'];
        $actual = array_keys($message);
        sort($expected);
        sort($actual);
        if ($expected !== $actual) {
            throw new RuntimeException('EXECUTION_STATUS_SCHEMA');
        }
        foreach ($expected as $field) {
            if (in_array($field, ['issued_at', 'expires_at', 'attempt'], true)) {
                if (!is_int($message[$field]) || $message[$field] < 0
                    || $message[$field] > 4102444800) {
                    throw new RuntimeException('EXECUTION_STATUS_TYPE');
                }
            } elseif (!is_string($message[$field])) {
                throw new RuntimeException('EXECUTION_STATUS_TYPE');
            }
        }
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $message['signature'])) {
            throw new RuntimeException('EXECUTION_STATUS_SIGNATURE');
        }
    }

    private static function queryScope(array $query, int $now): void
    {
        self::shape($query, self::QUERY_FIELDS);
        foreach ([
            'schema' => 'AUTO_IA_EXECUTION_QUERY_V1', 'issuer' => 'imagine-crm',
            'audience' => 'imagine-ai-agents', 'product' => 'imagine-crm',
            'operation' => 'event.execution.read', 'key_id' => 'SYNTHETIC_KEY_A',
        ] as $field => $expected) {
            if ($query[$field] !== $expected) {
                throw new RuntimeException('EXECUTION_QUERY_SCOPE');
            }
        }
        if (!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $query['company_id'])
            || !preg_match('/\A[a-f0-9]{64}\z/D', $query['payload_hash'])) {
            throw new RuntimeException('EXECUTION_QUERY_SCOPE');
        }
        foreach (['tenant_ref', 'event_ref', 'request_ref', 'nonce_ref'] as $field) {
            if (!preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,48}\z/D', $query[$field])) {
                throw new RuntimeException('EXECUTION_QUERY_SCOPE');
            }
        }
        if ($query['issued_at'] < $now - 30 || $query['issued_at'] > $now + 5
            || $query['expires_at'] <= $now || $query['expires_at'] <= $query['issued_at']
            || $query['expires_at'] - $query['issued_at'] > 60) {
            throw new RuntimeException('EXECUTION_QUERY_WINDOW');
        }
    }

    private static function key(string $key): void
    {
        if (strlen($key) < 32 || strlen($key) > 256) {
            throw new RuntimeException('EXECUTION_STATUS_KEY');
        }
    }

    private static function canonical(string $domain, array $fields, array $message): string
    {
        return $domain . "\n" . implode("\n", array_map(
            static fn (string $field): string => (string) $message[$field], $fields));
    }
}
