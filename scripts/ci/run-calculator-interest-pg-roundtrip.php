<?php
declare(strict_types=1);

/**
 * PERF-CALC-PG02: real PostgreSQL transaction in GITHUB disposable CI ONLY.
 * Consumes the Node producer's synthetic, PII-free envelope from stdin.
 * This is NOT a web route, mailer, lead intake, production migration,
 * consent engine or authenticated external source.
 */
require_once __DIR__.'/../../app/Core/Leads/SyntheticCalculatorInterestInteropInspector.php';
use App\Core\Leads\SyntheticCalculatorInterestInteropInspector as Inspector;
require_once __DIR__.'/../../app/Core/Leads/SyntheticCalculatorPgDelivery.php';
use App\Core\Leads\SyntheticCalculatorPgDelivery;


if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
    || getenv('CRM_CALCULATOR_LAB') !== 'synthetic-only'
    || getenv('PGDATABASE') !== 'imagine_calc_ci'
    || getenv('PGHOST') !== '127.0.0.1'
    || $argc !== 1) {
    throw new RuntimeException('Disposable CI only, no external side effects.');
}
$raw = stream_get_contents(STDIN, 4097);
if (!is_string($raw) || strlen($raw) < 50 || strlen($raw) > 4096) {
    throw new RuntimeException('Invalid synthetic CLI fixture length.');
}
$envelope = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
if (!is_array($envelope)) {
    throw new RuntimeException('Invalid synthetic CLI fixture.');
}
$ciKey = 'IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$tenant = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'; // bound by CI, NEVER browser input
$now=1794672030; // fixed, synthetic test clock
$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=imagine_calc_ci',
    'crm_calc_ci','ci-only-role',[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);

function required(bool $condition,string $label): void
{
    if (!$condition) throw new RuntimeException('Disposable calculator roundtrip failed: '.$label);
}

$first=SyntheticCalculatorPgDelivery::record($pdo,$envelope,$ciKey,$tenant,$now);
required($first['state']==='SYNTHETIC_PG_INTENT_RECORDED','first durable synthetic insert');
$repeat=SyntheticCalculatorPgDelivery::record($pdo,$envelope,$ciKey,$tenant,$now);
required($repeat['state']==='SYNTHETIC_REPLAY_CONFIRMED','same submission and hash replay');

$tampered=$envelope;
$tampered['payload']['marketing_requested']=true;
required(SyntheticCalculatorPgDelivery::record($pdo,$tampered,$ciKey,$tenant,$now)['state']==='SYNTHETIC_MAC_DENIED',
    'forged marketing is denied without hitting SQL');
$changed=$envelope;
$changed['payload']['media_total_cents']=500000;
$changed['signature']=hash_hmac('sha256',Inspector::signingBytes($changed['payload']),$ciKey);
required(SyntheticCalculatorPgDelivery::record($pdo,$changed,$ciKey,$tenant,$now)['state']==='SYNTHETIC_CONFLICT_DENIED',
    'same submission, signed different budget never overwrites');

$nonce=$envelope;
$nonce['payload']['submission_id']='33333333-3333-4333-8333-333333333333';
$nonce['signature']=hash_hmac('sha256',Inspector::signingBytes($nonce['payload']),$ciKey);
required(SyntheticCalculatorPgDelivery::record($pdo,$nonce,$ciKey,$tenant,$now)['state']==='SYNTHETIC_SQL_CONFLICT_DENIED',
    'nonce reused for distinct submission rejected');

$pdo->beginTransaction();
$pdo->prepare("SELECT set_config('app.tenant_id',?,true)")->execute([$tenant]);
$count=(int)$pdo->query('SELECT count(*) FROM public.crm_calc_lab_intents')->fetchColumn();
$events=(int)$pdo->query('SELECT count(*) FROM public.crm_calc_lab_consent_events')->fetchColumn();
$marketing=(int)$pdo->query("SELECT count(*) FROM public.crm_calc_lab_consent_events
    WHERE intent_kind='marketing_requested'")->fetchColumn();
$pdo->commit();
required($count===1 && $events===1 && $marketing===0,
    'exactly one metadata intent, one report claim, zero marketing');
foreach ([$first,$repeat] as $receipt) {
    required($receipt['real_crm_receipt_proven']===false &&
        $receipt['can_store_pii']===false &&
        $receipt['can_send_report']===false &&
        $receipt['can_send_marketing']===false,
        'ephemeral DB must not grant real business effects');
}
echo "PASS: signed Node synthetic intent -> restricted PG16 RLS immutable metadata receipt, replay/conflict/nonce tests; NO CRM/PII/email/ads\n";
