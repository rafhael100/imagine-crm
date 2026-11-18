<?php

declare(strict_types=1);

use App\Homologation\S2S\AgentsReceipt;

require dirname(__DIR__, 3).'/app/Homologation/S2S/StrictJson.php';
require dirname(__DIR__, 3).'/app/Homologation/S2S/AgentsReceipt.php';

// Exercise the actual consumer without booting Laravel, SQL or a provider.
// The two fixed HMAC vectors were independently computed with Python hmac.
$company = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$payloadHash = '6beaaf0f7043d43b156be32b40087a6db4b24f21580d958c19eb4b8fcd2fc3ae';
$ingressKey = 'synthetic-ingress-test-key-with-32-bytes-minimum';
$receiptKey = 'synthetic-receipt-test-key-with-32-bytes-minimum';
$now = 1800000000;
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) throw new RuntimeException('AGENTS_RECEIPT_TEST_FAILED: '.$label);
    $checks++;
};
$encode = static fn (array $value): string => json_encode(
    $value, JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION
);
$query = AgentsReceipt::query($company, 'SYNTHETIC_TENANT_A', 'SYNTHETIC_EVENT_A',
    $payloadHash, $ingressKey, $now, 'SYNTHETIC_REQUEST_A', 'SYNTHETIC_NONCE_A');
$expectedQuery = ['schema'=>'AUTO_IA_RECEIPT_QUERY_V1','issuer'=>'imagine-crm',
    'audience'=>'imagine-ai-agents','product'=>'imagine-crm','operation'=>'event.receipt.read',
    'company_id'=>$company,'tenant_ref'=>'SYNTHETIC_TENANT_A','event_ref'=>'SYNTHETIC_EVENT_A',
    'payload_hash'=>$payloadHash,'request_ref'=>'SYNTHETIC_REQUEST_A','nonce_ref'=>'SYNTHETIC_NONCE_A',
    'issued_at'=>$now,'expires_at'=>$now+30,'key_id'=>'SYNTHETIC_KEY_A',
    'signature'=>'8f562d4016e9aba1ebf3848238181af85cedc69ec95cf0d1af87e7c5eedbc4a9'];
$check($query === $expectedQuery, 'closed query and independent query HMAC vector');

$fields = ['schema','issuer','audience','company_id','tenant_ref','event_ref','payload_hash',
    'state','dispatch','processing','request_ref','nonce_ref','issued_at','expires_at','key_id'];
$sign = static function (array $receipt, ?string $key = null) use ($fields, $receiptKey): array {
    $values = array_map(static fn ($field) => (string)$receipt[$field], $fields);
    $receipt['signature'] = hash_hmac('sha256', "IMAGINE_AGENTS_EVENT_RECEIPT_V1\n".
        implode("\n", $values), $key ?? $receiptKey);
    return $receipt;
};
$receipt = $sign(['schema'=>'AUTO_IA_EVENT_RECEIPT_V1','issuer'=>'imagine-ai-agents',
    'audience'=>'imagine-crm','company_id'=>$company,'tenant_ref'=>$query['tenant_ref'],
    'event_ref'=>$query['event_ref'],'payload_hash'=>$payloadHash,'state'=>'RECEIVED',
    'dispatch'=>'DISABLED','processing'=>'NOT_DISPATCHED','request_ref'=>$query['request_ref'],
    'nonce_ref'=>$query['nonce_ref'],'issued_at'=>$now+1,'expires_at'=>$now+29,
    'key_id'=>'SYNTHETIC_P27_RECEIPT_KEY']);
$check($receipt['signature'] === '901616a7ce15446511d9950d5efa7f03d7046df327670e9423d8a5afd1c02134',
    'independent response HMAC vector');
$wire = $encode($receipt);
$check(AgentsReceipt::validate($wire, $query, $receiptKey, $now+2) === hash('sha256', $wire),
    'accepted signed receipt hashes exact received bytes');
$pretty = "\n".json_encode(array_reverse($receipt, true), JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
$check(AgentsReceipt::validate($pretty, $query, $receiptKey, $now+2) === hash('sha256', $pretty)
    && hash('sha256', $pretty) !== hash('sha256', $wire), 'key order independent and wire hash preserved');

$reject = static function (string $raw, string $label, ?array $request = null,
    ?int $clock = null, ?string $key = null) use ($query, $receiptKey, $now, $check): void {
    try {
        AgentsReceipt::validate($raw, $request ?? $query, $key ?? $receiptKey, $clock ?? $now+2);
    } catch (RuntimeException|JsonException|InvalidArgumentException) {
        $check(true, $label);
        return;
    }
    $check(false, $label);
};

// A signed positive response must not authorize another event, query or execution.
foreach (['schema'=>'AUTO_IA_EVENT_RECEIPT_V2','issuer'=>'imagine-portal','audience'=>'imagine-portal',
    'company_id'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb','tenant_ref'=>'SYNTHETIC_TENANT_B',
    'event_ref'=>'SYNTHETIC_EVENT_B','payload_hash'=>str_repeat('f',64),'state'=>'PROCESSED',
    'dispatch'=>'ENABLED','processing'=>'DISPATCHED','request_ref'=>'SYNTHETIC_REQUEST_B',
    'nonce_ref'=>'SYNTHETIC_NONCE_B','key_id'=>'SYNTHETIC_OTHER_KEY'] as $field=>$wrong) {
    $bad = $receipt; $bad[$field] = $wrong;
    $reject($encode($sign($bad)), 'signed wrong domain or binding '.$field);
}
foreach (array_keys($receipt) as $field) {
    $bad = $receipt; unset($bad[$field]);
    $reject($encode($bad), 'missing '.$field);
}
$bad = $receipt; $bad['payload'] = 'not-permitted';
$reject($encode($bad), 'extra payload field');
foreach (array_keys($receipt) as $field) {
    $bad = $receipt; $bad[$field] = null;
    $reject($encode($bad), 'null field '.$field);
}
foreach (['issued_at','expires_at'] as $field) {
    foreach ([(string)$receipt[$field], (float)$receipt[$field], true, []] as $wrongType) {
        $bad = $receipt; $bad[$field] = $wrongType;
        $reject($encode($bad), 'wrong temporal type '.$field);
    }
}

$expected = $receipt; ksort($expected);
foreach (['company_id','tenant_ref','event_ref','payload_hash','request_ref','nonce_ref',
    'state','dispatch','processing','signature'] as $field) {
    $prefix = substr($encode([$field=>'ambiguous']), 0, -1).',';
    $raw = $prefix.substr($wire, 1);
    $native = json_decode($raw, true, 8, JSON_THROW_ON_ERROR); ksort($native);
    $check($native === $expected, 'duplicate fixture retains valid final values '.$field);
    $reject($raw, 'duplicate '.$field);
}
$reject('{"request_\\u0072ef":"SYNTHETIC_OTHER",'.substr($wire,1), 'Unicode duplicate request');
$reject('{"signat\\u0075re":"'.str_repeat('0',64).'",'.substr($wire,1), 'Unicode duplicate signature');

foreach ([['issued_at'=>$now+8], ['expires_at'=>$now+31], ['expires_at'=>$now+2],
    ['expires_at'=>$now+1], ['issued_at'=>$now-2,'expires_at'=>$now+29],
    ['issued_at'=>-1], ['expires_at'=>4102444801]] as $change) {
    $reject($encode($sign(array_replace($receipt,$change))), 'signed invalid response window');
}
$reject($wire, 'response expires at the current second', null, $receipt['expires_at']);
$reject($wire, 'response from the future beyond clock allowance', null, $now-5);
foreach (['company_id','tenant_ref','event_ref','payload_hash','request_ref','nonce_ref'] as $field) {
    $other = $query; $other[$field] = $field==='company_id'
        ? 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'
        : ($field==='payload_hash' ? str_repeat('f',64) : 'SYNTHETIC_OTHER');
    $reject($wire, 'cannot reuse receipt for different expected '.$field, $other);
}

foreach (['',str_repeat('0',64),strtoupper($receipt['signature']),$receipt['signature'].'0'] as $signature) {
    $bad = $receipt; $bad['signature'] = $signature;
    $reject($encode($bad), 'invalid signature encoding/value');
}
$reject($encode($sign($receipt, $ingressKey)), 'ingress key cannot sign a receipt');
$reject($wire, 'wrong configured response key', null, null, $ingressKey);
$bad = $receipt; $bad['signature'] = hash_hmac('sha256', "AUTO_IA_ACK_V1\n".
    implode("\n", array_slice($receipt,0,15)), $receiptKey);
$reject($encode($bad), 'wrong signature domain');
foreach (['[]','null','true','"receipt"','{"schema":'] as $raw) $reject($raw, 'invalid JSON/object');
$reject($wire.str_repeat(' ',4097-strlen($wire)), 'wire byte limit includes whitespace');

echo 'S2S_AGENTS_RECEIPT_PASS checks='.$checks."\n";
