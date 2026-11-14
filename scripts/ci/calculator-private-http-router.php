<?php
declare(strict_types=1);

/**
 * PERF-CALC-HTTP-01 — PRIVATE, EPHEMERAL CI ONLY.
 *
 * Not registered in Laravel bootstrap or any public route. This file is
 * reachable ONLY when an explicitly started localhost php -S process runs
 * in GitHub Actions with flags below. Optional ephemeral PG16 metadata lab;
 * never a production database, PII, SMTP, CRM lead or marketing.
 */
require_once __DIR__ . '/../../app/Core/Leads/SyntheticCalculatorInterestInteropInspector.php';

use App\Core\Leads\SyntheticCalculatorInterestInteropInspector as Inspector;

function respond(int $status, string $state): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Content-Security-Policy: default-src \'none\'');
    echo json_encode([
        'schema' => 'synthetic.calculator.private-http-no-go.v1',
        'state' => $state,
        'consent_proven' => false,
        'tenant_authorized' => false,
        'crm_durable_receipt_proven' => false,
        'can_store_pii' => false,
        'can_send_report' => false,
        'can_send_marketing' => false,
        'can_charge' => false,
    ], JSON_THROW_ON_ERROR);
}

if (PHP_SAPI !== 'cli-server' ||
    getenv('CI') !== 'true' ||
    getenv('GITHUB_ACTIONS') !== 'true' ||
    getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm' ||
    getenv('CRM_CALCULATOR_HTTP_CI') !== 'synthetic-loopback' ||
    !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    respond(404, 'UNAVAILABLE_OUTSIDE_EPHEMERAL_LAB');
    return;
}
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($path === '/_lab/health' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    respond(200, 'SYNTHETIC_LOOPBACK_READY_NO_RECEIVER');
    return;
}
if ($path !== '/_lab/calculator-interest') {
    respond(404, 'UNKNOWN_LAB_PATH');
    return;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, 'METHOD_NOT_ALLOWED');
    return;
}
if (($_SERVER['CONTENT_TYPE'] ?? '') !== 'application/json' ||
    (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096 ||
    (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) < 10) {
    respond(415, 'INVALID_BODY_TYPE_OR_SIZE');
    return;
}
$body = file_get_contents('php://input', false, null, 0, 4097);
if (!is_string($body) || strlen($body) > 4096 || strlen($body) < 10) {
    respond(413, 'INVALID_BODY_SIZE');
    return;
}
try {
    $envelope = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    respond(400, 'INVALID_JSON');
    return;
}

$now = 1794672030; // fixed synthetic clock, no production date/clock.
$key = 'IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$assessment = Inspector::inspect($envelope, $key, $now);
if ($assessment['synthetic_mac_matches'] !== true) {
    respond(403, 'SYNTHETIC_SIGNATURE_REJECTED');
    return;
}
// Optional CI-only database exercise: unlike HTTP review-only, this writes
// IMMUTABLE SYNTHETIC METADATA to the disposed PostgreSQL service. It is never
// reachable from a Laravel route, browser or actual customer environment.
if (getenv('CRM_CALCULATOR_HTTP_PG_CI') === 'synthetic-pg') {
    if (getenv('PGDATABASE') !== 'imagine_calc_ci' ||
        getenv('PGHOST') !== '127.0.0.1' ||
        getenv('GITHUB_ACTIONS') !== 'true') {
        respond(404, 'SYNTHETIC_PG_DISABLED');
        return;
    }
    require_once __DIR__ . '/../../app/Core/Leads/SyntheticCalculatorPgDelivery.php';
    try {
        $db = new PDO(
            'pgsql:host=127.0.0.1;port=5432;dbname=imagine_calc_ci',
            'crm_calc_ci',
            'ci-only-role',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
        $result = \App\Core\Leads\SyntheticCalculatorPgDelivery::record(
            $db, $envelope, $key,
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $now
        );
        $state = $result['state'];
        $status = match($state) {
            'SYNTHETIC_PG_INTENT_RECORDED' => 201,
            'SYNTHETIC_REPLAY_CONFIRMED' => 200,
            'SYNTHETIC_CONFLICT_DENIED','SYNTHETIC_SQL_CONFLICT_DENIED' => 409,
            default => 503,
        };
        respond($status, $state);
    } catch (Throwable) {
        respond(503, 'SYNTHETIC_PG_UNAVAILABLE');
    }
    return;
}

// A correct fixture MAC CANNOT authorize a real client, PII or consent.
// 202 Accepted denotes ONLY a structurally reviewed synthetic HTTP request;
// not persisted, queued or delivered.
respond(202, 'SYNTHETIC_HTTP_REVIEW_ONLY_NO_RECEIPT');
