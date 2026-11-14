<?php
declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * PERF-CALC-F1: deterministic parity checker for synthetic Commercial
 * interest events ONLY. No HTTP, DB, contact fields, queues, marketing or email.
 * Caller-supplied CI key is not a production credential. A valid MAC cannot
 * establish origin, account authority, consent or a durable CRM receipt.
 */
final class SyntheticCalculatorInterestInteropInspector
{
    private const DOMAIN = 'imagine.comercial.calculator.synthetic-interest.v1';
    private const FIELDS = [
        'schema','source','submission_id','channel_ids','currency',
        'media_total_cents','report_requested','marketing_requested',
        'notice_version','issued_at','expires_at','nonce',
    ];
    private const CHANNELS = ['chatgpt_ads','google_ads','meta_ads'];

    /** @return array<string,mixed> */
    public static function inspect(mixed $envelope, mixed $syntheticKey, mixed $now): array
    {
        try {
            if (!is_array($envelope)) {
                return self::denied('DENIED_INVALID_ENVELOPE');
            }
            self::exact($envelope, ['payload','signature']);
            if (!is_array($envelope['payload'])) {
                return self::denied('DENIED_INVALID_ENVELOPE');
            }
            $payload = $envelope['payload'];
            $message = self::signingBytes($payload);
            if (!is_string($syntheticKey) || strlen($syntheticKey) < 32) {
                return self::denied('DENIED_INVALID_ENVELOPE');
            }
            if (!is_string($envelope['signature']) ||
                preg_match('/\A[a-f0-9]{64}\z/D', $envelope['signature']) !== 1 ||
                !is_int($now) || $now <= 0 ||
                $payload['issued_at'] > $now + 30 ||
                $payload['expires_at'] <= $now) {
                return self::denied('DENIED_INVALID_SIGNATURE_OR_WINDOW');
            }
            $expected = hash_hmac('sha256', $message, $syntheticKey);
            if (!hash_equals($expected, $envelope['signature'])) {
                return self::denied('DENIED_MAC_MISMATCH');
            }
            return [
                'state' => 'SYNTHETIC_MAC_MATCH_UNTRUSTED',
                'synthetic_mac_matches' => true,
                'tenant_authorized' => false,
                'consent_proven' => false,
                'crm_durable_receipt_proven' => false,
                'can_send_report' => false,
                'can_send_marketing' => false,
                'can_store_lead' => false,
            ];
        } catch (InvalidArgumentException) {
            return self::denied('DENIED_INVALID_ENVELOPE');
        }
    }

    /** Explicitly ordered, LF-only canonical text, equals the Node lab. */
    public static function signingBytes(array $p): string
    {
        self::exact($p, self::FIELDS);
        if ($p['schema'] !== 'imagine.calculator.synthetic-interest.v1' ||
            $p['source'] !== 'imagine_comercial_calculadora_performance' ||
            $p['currency'] !== 'BRL' ||
            !is_string($p['submission_id']) || !self::uuid($p['submission_id']) ||
            !is_string($p['nonce']) || !self::uuid($p['nonce']) ||
            $p['submission_id'] === $p['nonce'] ||
            $p['notice_version'] !== 'lab-notice-v1' ||
            !is_array($p['channel_ids']) ||
            !array_is_list($p['channel_ids']) ||
            count($p['channel_ids']) < 1 || count($p['channel_ids']) > 3 ||
            !is_int($p['media_total_cents']) ||
            $p['media_total_cents'] < 70000 ||
            $p['media_total_cents'] > 300000000 ||
            !is_bool($p['report_requested']) ||
            !is_bool($p['marketing_requested']) ||
            (!$p['report_requested'] && !$p['marketing_requested']) ||
            !is_int($p['issued_at']) || !is_int($p['expires_at']) ||
            $p['issued_at'] <= 0 || $p['expires_at'] <= $p['issued_at'] ||
            $p['expires_at'] - $p['issued_at'] > 300) {
            throw new InvalidArgumentException('Invalid synthetic interest payload.');
        }
        foreach ($p['channel_ids'] as $id) {
            if (!is_string($id) || !in_array($id, self::CHANNELS, true)) {
                throw new InvalidArgumentException('Unsupported synthetic calculator channel.');
            }
        }
        $sorted = array_values(array_unique($p['channel_ids']));
        sort($sorted, SORT_STRING);
        if ($p['channel_ids'] !== $sorted) {
            throw new InvalidArgumentException('Noncanonical or duplicated channel selection.');
        }
        return implode("\n", [
            self::DOMAIN, $p['schema'], $p['source'], $p['submission_id'],
            implode(',', $p['channel_ids']), $p['currency'],
            (string) $p['media_total_cents'],
            $p['report_requested'] ? '1' : '0',
            $p['marketing_requested'] ? '1' : '0',
            $p['notice_version'], (string) $p['issued_at'],
            (string) $p['expires_at'], $p['nonce'],
        ]);
    }

    private static function uuid(string $x): bool
    {
        return preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $x) === 1;
    }

    /** @param list<string> $expected */
    private static function exact(array $value, array $expected): void
    {
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw new InvalidArgumentException('Unsupported synthetic envelope fields.');
        }
    }

    /** @return array<string,mixed> */
    private static function denied(string $state): array
    {
        return [
            'state' => $state,
            'synthetic_mac_matches' => false,
            'tenant_authorized' => false,
            'consent_proven' => false,
            'crm_durable_receipt_proven' => false,
            'can_send_report' => false,
            'can_send_marketing' => false,
            'can_store_lead' => false,
        ];
    }
}
