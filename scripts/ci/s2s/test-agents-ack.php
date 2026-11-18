<?php

declare(strict_types=1);

use App\Homologation\S2S\AgentsAcknowledgement;

require dirname(__DIR__, 3).'/app/Homologation/S2S/StrictJson.php';
require dirname(__DIR__, 3).'/app/Homologation/S2S/AgentsAcknowledgement.php';

// Exercise the validator used by the sender. No framework, SQL or transport.
$company = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$event = 'SYNTHETIC_EVENT_A';
$payloadHash = '6beaaf0f7043d43b156be32b40087a6db4b24f21580d958c19eb4b8fcd2fc3ae';
$key = 'synthetic-ack-test-key-with-32-bytes-minimum';
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException('AGENTS_ACK_TEST_FAILED: '.$label);
    }
    $checks++;
};
$encode = static fn (array $value): string => json_encode($value, JSON_THROW_ON_ERROR);
$sign = static function (array $ack) use ($key): array {
    $canonical = implode("\n", [$ack['schema'], $ack['company_id'], $ack['event_ref'],
        $ack['payload_hash'], $ack['decision'], $ack['dispatch']]);
    $ack['signature'] = hash_hmac('sha256', $canonical, $key);
    return $ack;
};
$reject = static function (string $raw, string $label, ?string $reason = null) use (
    $company, $event, $payloadHash, $key, $check
): void {
    try {
        AgentsAcknowledgement::validate($raw, $company, $event, $payloadHash, $key);
    } catch (RuntimeException|JsonException $error) {
        $check($reason === null || $error->getMessage() === $reason, $label);
        return;
    }
    $check(false, $label);
};

$received = $sign(['schema' => 'AUTO_IA_ACK_V1', 'company_id' => $company,
    'event_ref' => $event, 'payload_hash' => $payloadHash,
    'decision' => 'LAB_RECEIVED_NO_DELIVERY', 'dispatch' => 'DISABLED']);
// Fixed interoperability vector independently computed with Python hashlib/hmac.
$check($received['signature'] === '2a9a415b8ec45679444dafe37ff8ad9c2984a66daaa1350b73d7ce6970de1c87',
    'known HMAC vector');
foreach (['LAB_RECEIVED_NO_DELIVERY', 'LAB_DUPLICATE_NO_DELIVERY'] as $decision) {
    $ack = $received;
    $ack['decision'] = $decision;
    $wire = $encode($sign($ack));
    $check(AgentsAcknowledgement::validate($wire, $company, $event, $payloadHash, $key)
        === hash('sha256', $wire), 'accepted signed '.$decision);
}
$wire = $encode($received);
$reordered = "\n".json_encode(array_reverse($received, true), JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
$reorderedHash = AgentsAcknowledgement::validate($reordered, $company, $event, $payloadHash, $key);
$check($reorderedHash === hash('sha256', $reordered) && $reorderedHash !== hash('sha256', $wire),
    'canonical signature and original-wire receipt hash');

// Last values, schema and HMAC are all valid after ordinary json_decode.
// Reject the ambiguous wire before it can be acknowledged in the database.
$ambiguous = [
    'literal company' => '{"company_id":"bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",'.substr($wire, 1),
    'Unicode company' => '{"company_\\u0069d":"bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",'.substr($wire, 1),
    'literal signature' => '{"signature":"'.str_repeat('0', 64).'",'.substr($wire, 1),
    'Unicode signature' => '{"signat\\u0075re":"'.str_repeat('0', 64).'",'.substr($wire, 1),
    'dispatch' => '{"dispatch":"ENABLED",'.substr($wire, 1),
    'decision' => '{"decision":"LAB_DENY_GRANT",'.substr($wire, 1),
];
$expected = $received;
ksort($expected);
foreach ($ambiguous as $label => $raw) {
    $native = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    ksort($native);
    $check($native === $expected, 'duplicate fixture retains valid signed ACK: '.$label);
    $reject($raw, 'duplicate '.$label, 'DUPLICATE_JSON_KEY');
}

foreach (array_keys($received) as $field) {
    $bad = $received;
    unset($bad[$field]);
    $reject($encode($bad), 'missing '.$field, 'ACK_SCHEMA');
}
$bad = $received;
$bad['extra'] = 'ignored';
$reject($encode($bad), 'unknown field', 'ACK_SCHEMA');
foreach (['schema' => 1, 'company_id' => null, 'event_ref' => [], 'payload_hash' => true,
    'decision' => false, 'dispatch' => 0.0, 'signature' => 123] as $field => $wrongType) {
    $bad = $received;
    $bad[$field] = $wrongType;
    $reject($encode($bad), 'wrong type '.$field, 'ACK_TYPE');
}
foreach ([
    'schema' => 'AUTO_IA_ACK_V2',
    'company_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
    'event_ref' => 'SYNTHETIC_EVENT_B',
    'payload_hash' => str_repeat('f', 64),
    'decision' => 'LAB_DENY_GRANT',
    'dispatch' => 'ENABLED',
] as $field => $wrongScope) {
    $bad = $received;
    $bad[$field] = $wrongScope;
    $reject($encode($sign($bad)), 'signed wrong scope '.$field, 'ACK_SCOPE');
}
foreach (['', str_repeat('0', 64), strtoupper($received['signature']), $received['signature'].'0'] as $signature) {
    $bad = $received;
    $bad['signature'] = $signature;
    $reject($encode($bad), 'invalid signature encoding/value', 'ACK_SIGNATURE');
}
$bad = $received;
$bad['decision'] = 'LAB_DUPLICATE_NO_DELIVERY';
$reject($encode($bad), 'allowed decision changed without resigning', 'ACK_SIGNATURE');
$bad = $received;
$bad['signature'] = hash_hmac('sha256', implode("\n", array_slice($bad, 0, 6)), 'different-synthetic-key');
$reject($encode($bad), 'signed using wrong key', 'ACK_SIGNATURE');

foreach (['[]', 'null', 'true', '"ACK"', '{"schema":'] as $raw) {
    $reject($raw, 'invalid ACK JSON/object');
}
$reject($wire.str_repeat(' ', 4097 - strlen($wire)), 'ACK byte limit', 'JSON_SIZE_LIMIT');

echo 'S2S_AGENTS_ACK_PASS checks='.$checks."\n";
