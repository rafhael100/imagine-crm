<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use RuntimeException;

/** Verify the existing signed NO_DELIVERY acknowledgement before any outbox update. */
final class AgentsAcknowledgement
{
    public static function validate(string $raw, string $company, string $eventRef,
        string $payloadHash, string $key): string
    {
        $ack = StrictJson::decodeObject($raw, 4096, 8);
        $keys = array_keys($ack);
        sort($keys);
        if ($keys !== ['company_id', 'decision', 'dispatch', 'event_ref', 'payload_hash', 'schema', 'signature']) {
            throw new RuntimeException('ACK_SCHEMA');
        }
        foreach ($ack as $value) {
            if (!is_string($value)) {
                throw new RuntimeException('ACK_TYPE');
            }
        }
        if ($ack['schema'] !== 'AUTO_IA_ACK_V1'
            || $ack['company_id'] !== $company
            || $ack['event_ref'] !== $eventRef
            || $ack['payload_hash'] !== $payloadHash
            || $ack['dispatch'] !== 'DISABLED'
            || !in_array($ack['decision'], ['LAB_RECEIVED_NO_DELIVERY', 'LAB_DUPLICATE_NO_DELIVERY'], true)) {
            throw new RuntimeException('ACK_SCOPE');
        }
        $canonical = implode("\n", array_map(static fn (string $name): string => $ack[$name],
            ['schema', 'company_id', 'event_ref', 'payload_hash', 'decision', 'dispatch']));
        if (!hash_equals(hash_hmac('sha256', $canonical, $key), $ack['signature'])) {
            throw new RuntimeException('ACK_SIGNATURE');
        }

        return hash('sha256', $raw);
    }
}
