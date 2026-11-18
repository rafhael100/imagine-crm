<?php

declare(strict_types=1);

// Real mTLS adversarial requests to the isolated CRM application only.
$mode = $argv[1] ?? '';
$company = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$request = bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(2)).'-4'
    .substr(bin2hex(random_bytes(2)), 1).'-8'.substr(bin2hex(random_bytes(2)), 1)
    .'-'.bin2hex(random_bytes(6));
$v = ['schema' => 'CENTRAL_AUTO_MTLS_REQUEST_V2', 'issuer' => 'portal-automation-admin',
    'audience' => 'crm-automation-state', 'operation' => 'automation.state.read',
    'company_id' => $company, 'request_id' => $request,
    'nonce' => bin2hex(random_bytes(32)), 'issued_at' => time()];
$key = file_get_contents('/certs/signing.key');
$v['mac'] = hash_hmac('sha256', implode('|', $v), $key);
$raw = json_encode($v, JSON_THROW_ON_ERROR);
$raw = match ($mode) {
    'valid-whitespace' => " \t\n".$raw."\r ",
    'duplicate-company' => '{"company_id":"bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",'.substr($raw, 1),
    'duplicate-escaped-company' => '{"company_\u0069d":"bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",'.substr($raw, 1),
    'duplicate-mac' => '{"mac":"'.str_repeat('0', 64).'",'.substr($raw, 1),
    'duplicate-issued-at' => '{"issued_at":0,'.substr($raw, 1),
    'malformed' => substr($raw, 0, -1),
    'array-root' => '['.$raw.']',
    'over-limit' => $raw.str_repeat(' ', 4097),
    default => throw new RuntimeException('UNKNOWN_SYNTHETIC_JSON_PROBE'),
};
$ctx = stream_context_create([
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true,
        'allow_self_signed' => false, 'peer_name' => 'crm-s2s',
        'cafile' => '/certs/ca.crt', 'local_cert' => '/certs/portal.crt',
        'local_pk' => '/certs/portal.key'],
    'http' => ['method' => 'POST',
        'header' => "Content-Type: application/json\r\nConnection: close\r\n",
        'content' => $raw, 'timeout' => 3, 'follow_location' => 0,
        'max_redirects' => 0, 'ignore_errors' => true],
]);
@file_get_contents('https://crm-s2s:8443/private/automations/state', false, $ctx, 0, 16385);
$status = preg_match('/\AHTTP\/\S+ ([0-9]{3})(?: |$)/', $http_response_header[0] ?? '', $m)
    ? (int) $m[1] : 0;
echo json_encode(['status' => $status, 'request_id' => $request], JSON_THROW_ON_ERROR)."\n";
