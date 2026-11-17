<?php

declare(strict_types=1);

use App\Homologation\S2S\AgentsReceipt;

require '/var/www/html/vendor/autoload.php';

// The probe talks to the real pinned Agents endpoint. It never updates either DB.
try {
    if (getenv('APP_ENV') !== 'staging'
        || getenv('CRM_AGENTS_RECEIPT_PROFILE') !== 'synthetic-private-v1'
        || count($argv) !== 6) {
        throw new RuntimeException('RECEIPT_PROBE_DISABLED');
    }
    [$script, $mode, $company, $tenant, $event, $hash] = $argv;
    $allowed = ['valid', 'wrong-signature', 'wrong-payload', 'wrong-company',
        'wrong-tenant', 'wrong-event', 'wrong-operation', 'expired-query',
        'duplicate-key', 'unicode-duplicate-key', 'cookie', 'authorization',
        'no-cert-forged-header', 'expect-denial'];
    if (!in_array($mode, $allowed, true)) {
        throw new RuntimeException('RECEIPT_PROBE_MODE');
    }
    $ingressKey = file_get_contents('/certs/agents-signing.key');
    $receiptKey = file_get_contents('/certs/agents-receipt.key');
    if (hash_equals($ingressKey, $receiptKey)) {
        throw new RuntimeException('RECEIPT_PROBE_SEPARATE_KEYS_REQUIRED');
    }
    $query = AgentsReceipt::query($company, $tenant, $event, $hash, $ingressKey,
        time(), 'SYNTHETIC_' . strtoupper(bin2hex(random_bytes(16))),
        'SYNTHETIC_' . strtoupper(bin2hex(random_bytes(16))));
    switch ($mode) {
        case 'wrong-payload':
            $query['payload_hash'] = str_repeat($hash === str_repeat('f', 64) ? 'e' : 'f', 64);
            break;
        case 'wrong-company':
            $query['company_id'] = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
            break;
        case 'wrong-tenant':
            $query['tenant_ref'] = 'SYNTHETIC_TENANT_P22_OTHER';
            break;
        case 'wrong-event':
            $query['event_ref'] = 'SYNTHETIC_P22_MISSING_' . strtoupper(bin2hex(random_bytes(8)));
            break;
        case 'wrong-operation':
            $query['operation'] = 'lead.ingest';
            break;
        case 'expired-query':
            $query['issued_at'] = time() - 31;
            $query['expires_at'] = time() - 1;
            break;
    }
    // Re-sign negative scope fixtures so only the intended predicate rejects them.
    unset($query['signature']);
    $query['signature'] = hash_hmac('sha256',
        "IMAGINE_AGENTS_EVENT_RECEIPT_QUERY_V1\n" . implode("\n", $query), $ingressKey);
    if ($mode === 'wrong-signature') {
        $query['signature'] = str_repeat('0', 64);
    }
    $body = json_encode($query, JSON_THROW_ON_ERROR);
    if ($mode === 'duplicate-key') {
        $body = '{"company_id":"' . $company . '",' . substr($body, 1);
    } elseif ($mode === 'unicode-duplicate-key') {
        $body = '{"company_\\u0069d":"' . $company . '",' . substr($body, 1);
    }
    $headers = "Content-Type: application/json\r\nConnection: close\r\n";
    if ($mode === 'cookie') $headers .= "Cookie: synthetic=forbidden\r\n";
    if ($mode === 'authorization') $headers .= "Authorization: Bearer SYNTHETIC_FORBIDDEN\r\n";
    $tls = ['verify_peer' => true, 'verify_peer_name' => true,
        'allow_self_signed' => false, 'peer_name' => 'agents-s2s',
        'cafile' => '/certs/ca.crt', 'local_cert' => '/certs/crm.crt',
        'local_pk' => '/certs/crm.key'];
    if ($mode === 'no-cert-forged-header') {
        unset($tls['local_cert'], $tls['local_pk']);
        $headers .= "IMAGINE_MTLS_VERIFY: SUCCESS\r\nIMAGINE_MTLS_CERT: forged\r\n";
    }
    $context = stream_context_create(['ssl' => $tls, 'http' => [
        'method' => 'POST', 'header' => $headers, 'content' => $body,
        'timeout' => 3, 'ignore_errors' => true, 'follow_location' => 0,
        'max_redirects' => 0,
    ]]);
    $raw = @file_get_contents('https://agents-s2s:8443/private/events/receipt',
        false, $context, 0, 4097);
    preg_match('/\AHTTP\/\S+ ([0-9]{3})(?: |$)/', $http_response_header[0] ?? '', $status);
    $status = isset($status[1]) ? (int) $status[1] : 0;
    if ($mode === 'valid') {
        if ($status !== 200 || !is_string($raw)) throw new RuntimeException('RECEIPT_PROBE_STATUS');
        AgentsReceipt::validate($raw, $query, $receiptKey, time());
        echo '{"status":200,"receipt_verified":true}';
    } elseif ($mode === 'no-cert-forged-header') {
        if (!in_array($status, [0, 400, 403], true)) throw new RuntimeException('RECEIPT_PROBE_MTLS');
        echo '{"status":' . $status . ',"denied":true}';
    } else {
        if ($status !== 403 || $raw !== '{"error":"RECEIPT_DENIED"}') {
            throw new RuntimeException('RECEIPT_PROBE_UNIFORM_DENIAL');
        }
        echo '{"status":403,"denied":true}';
    }
    echo "\n";
} catch (Throwable) {
    fwrite(STDERR, "P22_RECEIPT_PROBE_FAILED\n");
    exit(2);
}
