<?php

declare(strict_types=1);

use App\Homologation\S2S\AgentsExecutionReceipt;

require dirname(__DIR__, 3).'/app/Homologation/S2S/StrictJson.php';
require dirname(__DIR__, 3).'/app/Homologation/S2S/AgentsExecutionReceipt.php';

// Standalone tests of the real verifier. Fixed vectors were independently computed in Python.
$company = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$payloadHash = '6beaaf0f7043d43b156be32b40087a6db4b24f21580d958c19eb4b8fcd2fc3ae';
$ingressKey = 'synthetic-ingress-test-key-with-32-bytes-minimum';
$executionKey = 'synthetic-execution-test-key-with-32-bytes-minimum';
$now = 1800000000;
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) throw new RuntimeException('AGENTS_EXECUTION_TEST_FAILED: '.$label);
    $checks++;
};
$encode = static fn (array $value): string => json_encode($value, JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION);
$query = AgentsExecutionReceipt::query($company, 'SYNTHETIC_TENANT_A', 'SYNTHETIC_EVENT_A',
    $payloadHash, $ingressKey, $now, 'SYNTHETIC_REQUEST_A', 'SYNTHETIC_NONCE_A');
$check(array_keys($query) === ['schema','issuer','audience','product','operation','company_id',
    'tenant_ref','event_ref','payload_hash','request_ref','nonce_ref','issued_at','expires_at','key_id','signature'],
    'closed ordered query');
$check($query['signature'] === 'a1aae1704bf054b033c0dd172bf8f93de582275cbe4a1884d1c74be5feac12e1',
    'independent query HMAC vector');

$fields = ['schema','issuer','audience','company_id','tenant_ref','event_ref','payload_hash',
    'lead_id','task_payload_hash','execution_state','queue_state','result_code','observation_hash',
    'attempt','request_ref','nonce_ref','issued_at','expires_at','key_id'];
$facts = ['company_id','tenant_ref','event_ref','payload_hash','lead_id','task_payload_hash',
    'execution_state','queue_state','result_code','attempt'];
$canonical = static fn (string $prefix, array $names, array $message): string => $prefix."\n".
    implode("\n", array_map(static fn ($name) => (string)$message[$name], $names));
$sign = static function (array $status, ?string $key = null, bool $rehash = true)
    use ($facts, $fields, $executionKey, $canonical): array {
    if ($rehash) $status['observation_hash'] = hash('sha256',
        $canonical('IMAGINE_AGENTS_EXECUTION_OBSERVATION_V1', $facts, $status));
    $status['signature'] = hash_hmac('sha256',
        $canonical('IMAGINE_AGENTS_EXECUTION_STATUS_V1', $fields, $status), $key ?? $executionKey);
    return $status;
};
$status = $sign(['schema'=>'AUTO_IA_EXECUTION_STATUS_V1','issuer'=>'imagine-ai-agents',
    'audience'=>'imagine-crm','company_id'=>$company,'tenant_ref'=>$query['tenant_ref'],
    'event_ref'=>$query['event_ref'],'payload_hash'=>$payloadHash,
    'lead_id'=>'11111111-1111-1111-1111-111111111111', 'task_payload_hash'=>str_repeat('a',64),
    'execution_state'=>'ACKED','queue_state'=>'ACKED','result_code'=>'PREPARED',
    'observation_hash'=>'','attempt'=>1,'request_ref'=>$query['request_ref'],'nonce_ref'=>$query['nonce_ref'],
    'issued_at'=>$now+1,'expires_at'=>$now+29,'key_id'=>'SYNTHETIC_EXECUTION_STATUS_KEY']);
$check($status['observation_hash'] === 'd53d49baeeebc8d7cf41c0f99504a3979a45721a6b0d9fe993e7055569dc2a15',
    'independent stable observation vector');
$check($status['signature'] === '1bb548c29f73d18a14818519eb72497cde4db5fd720ddfc2d9e55f66828a92f2',
    'independent response HMAC vector');
$wire = $encode($status);
$check(AgentsExecutionReceipt::validate($wire, $query, $executionKey, $now+2) === $status,
    'verified status preserves all twenty fields and a non-v4 canonical lead UUID');
$pretty = "\n".json_encode(array_reverse($status, true), JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
$check(AgentsExecutionReceipt::validate($pretty, $query, $executionKey, $now+2) == $status,
    'canonical signature independent of wire whitespace or field order');

$reject = static function (string $raw, string $label, ?array $request = null,
    ?int $clock = null, ?string $key = null) use ($query, $executionKey, $now, $check): void {
    try {
        AgentsExecutionReceipt::validate($raw, $request ?? $query, $key ?? $executionKey, $clock ?? $now+2);
    } catch (RuntimeException|JsonException|InvalidArgumentException) {
        $check(true, $label);
        return;
    }
    $check(false, $label);
};

foreach ([
    ['NOT_STAGED','NONE',0,'NONE',''],
    ['NOT_STARTED','PENDING',0,'NONE',str_repeat('a',64)],
    ['NOT_STARTED','LEASED',0,'NONE',str_repeat('a',64)],
    ['NOT_STARTED','DEAD',0,'NONE',str_repeat('a',64)],
    ['STARTED','LEASED',1,'NONE',str_repeat('a',64)],
    ['STARTED','DEAD',3,'NONE',str_repeat('a',64)],
    ['RESULT','LEASED',1,'PREPARED',str_repeat('a',64)],
    ['RESULT','DEAD',3,'PREPARED',str_repeat('a',64)],
    ['UNKNOWN','LEASED',1,'NONE',str_repeat('a',64)],
    ['UNKNOWN','DEAD',3,'NONE',str_repeat('a',64)],
    ['ACKED','ACKED',3,'PREPARED',str_repeat('a',64)],
] as [$state,$queue,$attempt,$result,$task]) {
    $valid = $sign(array_replace($status, ['execution_state'=>$state,'queue_state'=>$queue,
        'attempt'=>$attempt,'result_code'=>$result,'task_payload_hash'=>$task]));
    $check(AgentsExecutionReceipt::validate($encode($valid), $query, $executionKey, $now+2) === $valid,
        'closed valid state '.$state.'/'.$queue);
}
foreach ([
    ['NOT_STAGED','PENDING',0,'NONE',''], ['NOT_STAGED','NONE',0,'NONE',str_repeat('a',64)],
    ['NOT_STAGED','NONE',1,'NONE',''], ['NOT_STARTED','NONE',0,'NONE',str_repeat('a',64)],
    ['NOT_STARTED','PENDING',1,'NONE',str_repeat('a',64)], ['NOT_STARTED','ACKED',0,'PREPARED',str_repeat('a',64)],
    ['STARTED','PENDING',1,'NONE',str_repeat('a',64)], ['STARTED','LEASED',0,'NONE',str_repeat('a',64)],
    ['STARTED','LEASED',4,'NONE',str_repeat('a',64)], ['STARTED','LEASED',1,'PREPARED',str_repeat('a',64)],
    ['RESULT','LEASED',1,'NONE',str_repeat('a',64)], ['RESULT','ACKED',1,'PREPARED',str_repeat('a',64)],
    ['ACKED','LEASED',1,'PREPARED',str_repeat('a',64)], ['ACKED','ACKED',0,'PREPARED',str_repeat('a',64)],
    ['UNKNOWN','LEASED',1,'PREPARED',str_repeat('a',64)], ['UNKNOWN','PENDING',1,'NONE',str_repeat('a',64)],
    ['COMPLETED','ACKED',1,'PREPARED',str_repeat('a',64)], ['ACKED','ACKED',1,'PREPARED',''],
    ['ACKED','ACKED',1,'PREPARED',str_repeat('A',64)],
] as [$state,$queue,$attempt,$result,$task]) {
    $bad = $sign(array_replace($status, ['execution_state'=>$state,'queue_state'=>$queue,
        'attempt'=>$attempt,'result_code'=>$result,'task_payload_hash'=>$task]));
    $reject($encode($bad), 'signed contradictory state '.$state.'/'.$queue);
}

foreach (['schema'=>'AUTO_IA_EVENT_RECEIPT_V1','issuer'=>'imagine-portal','audience'=>'imagine-portal',
    'company_id'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb','tenant_ref'=>'SYNTHETIC_TENANT_B',
    'event_ref'=>'SYNTHETIC_EVENT_B','payload_hash'=>str_repeat('f',64),
    'lead_id'=>'11111111-1111-1111-1111-11111111111X',
    'request_ref'=>'SYNTHETIC_REQUEST_B','nonce_ref'=>'SYNTHETIC_NONCE_B','key_id'=>'SYNTHETIC_P27_RECEIPT_KEY'] as $field=>$wrong) {
    $reject($encode($sign(array_replace($status, [$field=>$wrong]))), 'signed wrong binding '.$field);
}
foreach (array_keys($status) as $field) {
    $bad = $status; unset($bad[$field]);
    $reject($encode($bad), 'missing field '.$field);
    $bad = $status; $bad[$field] = null;
    $reject($encode($bad), 'null field '.$field);
}
foreach (['dispatch'=>'DISABLED','processing'=>'NOT_DISPATCHED','crm_mutated'=>true,'payload'=>'extra'] as $field=>$value) {
    $reject($encode(array_replace($status, [$field=>$value])), 'unknown field '.$field);
}
foreach (['issued_at','expires_at','attempt'] as $field) {
    foreach ([(string)$status[$field], (float)$status[$field], true, []] as $wrong) {
        $reject($encode(array_replace($status, [$field=>$wrong])), 'wrong numeric type '.$field);
    }
}
foreach (array_keys($status) as $field) {
    $prefix = substr($encode([$field=>'ambiguous']),0,-1).',';
    $reject($prefix.substr($wire,1), 'duplicate name '.$field);
}
$reject('{"lead_\\u0069d":"other",'.substr($wire,1), 'escaped duplicate lead');
$reject('{"observation_\\u0068ash":"other",'.substr($wire,1), 'escaped duplicate observation');
$reject('{"signat\\u0075re":"'.str_repeat('0',64).'",'.substr($wire,1), 'escaped duplicate signature');
foreach ([['issued_at'=>$now+8], ['expires_at'=>$now+31], ['expires_at'=>$now+2],
    ['expires_at'=>$now+1], ['issued_at'=>$now-2,'expires_at'=>$now+29],
    ['issued_at'=>-1], ['expires_at'=>4102444801]] as $change) {
    $reject($encode($sign(array_replace($status,$change))), 'signed invalid window');
}
$reject($wire, 'response expires at current second', null, $status['expires_at']);
$reject($wire, 'old response cannot outlive freshness', null, $now+12);
foreach (['company_id','tenant_ref','event_ref','payload_hash','request_ref','nonce_ref'] as $field) {
    $other = $query; $other[$field] = $field==='company_id' ? 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'
        : ($field==='payload_hash' ? str_repeat('f',64) : 'SYNTHETIC_OTHER');
    $reject($wire, 'query binding '.$field, $other);
}
$other = $query; $other['operation'] = 'event.receipt.read';
$reject($wire, 'admission query cannot authorize execution lookup', $other);
$other = $query; $other['schema'] = 'AUTO_IA_RECEIPT_QUERY_V1';
$reject($wire, 'admission schema cannot authorize execution lookup', $other);
$bad = $status; $bad['observation_hash'] = str_repeat('0',64);
$reject($encode($sign($bad, null, false)), 'signed but incorrect observation hash');
$bad = $status; $bad['signature'] = hash_hmac('sha256',
    $canonical('IMAGINE_AGENTS_EVENT_RECEIPT_V1', $fields, $bad), $executionKey);
$reject($encode($bad), 'admission MAC domain cannot sign execution');
$reject($encode($sign($status, $ingressKey)), 'ingress key cannot sign execution status');
$reject($wire, 'wrong configured verification key', null, null, $ingressKey);
foreach (['',str_repeat('0',64),strtoupper($status['signature']),$status['signature'].'0'] as $wrong) {
    $reject($encode(array_replace($status, ['signature'=>$wrong])), 'invalid signature');
}
foreach (['[]','null','true','"status"','{"schema":'] as $raw) $reject($raw, 'invalid object');
$reject($wire.str_repeat(' ',4097-strlen($wire)), 'wire size includes whitespace');

$renewedQuery = AgentsExecutionReceipt::query($company, $query['tenant_ref'], $query['event_ref'],
    $payloadHash, $ingressKey, $now+1, 'SYNTHETIC_REQUEST_NEW', 'SYNTHETIC_NONCE_NEW');
$renewed = $sign(array_replace($status, ['request_ref'=>$renewedQuery['request_ref'],
    'nonce_ref'=>$renewedQuery['nonce_ref'],'issued_at'=>$now+2,'expires_at'=>$now+30]));
$check(AgentsExecutionReceipt::validate($encode($renewed), $renewedQuery, $executionKey, $now+3) === $renewed,
    'fresh signed query can observe existing result');
$check($renewed['observation_hash'] === $status['observation_hash']
    && $renewed['signature'] !== $status['signature'], 'transport freshness does not create new observed facts');

echo 'S2S_AGENTS_EXECUTION_RECEIPT_PASS checks='.$checks."\n";
