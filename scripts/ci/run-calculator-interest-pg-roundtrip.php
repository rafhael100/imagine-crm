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

function strictPgBool(mixed $value): ?bool
{
    if ($value === true || $value === 't' || $value === 'true' || $value === '1') return true;
    if ($value === false || $value === 'f' || $value === 'false' || $value === '0') return false;
    return null;
}

function delivery(PDO $db, array $envelope, string $key, string $tenant, int $now): array
{
    $deny = static fn(string $state): array=>[
        'state'=>$state,'synthetic_pg_persisted'=>false,
        'real_crm_receipt_proven'=>false,'can_store_pii'=>false,
        'can_send_report'=>false,'can_send_marketing'=>false,
    ];
    $assessment=Inspector::inspect($envelope,$key,$now);
    if ($assessment['synthetic_mac_matches'] !== true) {
        return $deny('SYNTHETIC_MAC_DENIED');
    }
    $p=$envelope['payload'];
    $payloadHash=hash('sha256',Inspector::signingBytes($p));
    $db->beginTransaction();
    try {
        $scope=$db->prepare("SELECT set_config('app.tenant_id',?,true)");
        $scope->execute([$tenant]);

        $insert=$db->prepare(
            'INSERT INTO public.crm_calc_lab_intents
              (organization_id,submission_id,signed_nonce,payload_sha256,
               media_total_cents,report_requested,marketing_requested)
             VALUES (?::uuid,?::uuid,?::uuid,?,?,?,?)
             ON CONFLICT (organization_id,submission_id) DO NOTHING
             RETURNING submission_id'
        );
        $insert->execute([
            $tenant,$p['submission_id'],$p['nonce'],$payloadHash,
            $p['media_total_cents'],
            $p['report_requested'] ? 'true' : 'false',
            $p['marketing_requested'] ? 'true' : 'false',
        ]);
        $inserted=$insert->fetch(PDO::FETCH_ASSOC);
        if (is_array($inserted)) {
            // No operational marketing event: these are explicitly *claimed*
            // interests, never proof of opt-in or communication permission.
            $event=$db->prepare(
                'INSERT INTO public.crm_calc_lab_consent_events
                   (organization_id,submission_id,event_id,intent_kind)
                 VALUES (?::uuid,?::uuid,?::uuid,?)'
            );
            if ($p['report_requested']) {
                $event->execute([$tenant,$p['submission_id'],
                    '44444444-4444-4444-8444-444444444444','report_requested']);
            }
            if ($p['marketing_requested']) {
                $event->execute([$tenant,$p['submission_id'],
                    '77777777-7777-4777-8777-777777777777','marketing_requested']);
            }
            $db->commit();
            return array_replace($deny('SYNTHETIC_PG_INTENT_RECORDED'),[
                'synthetic_pg_persisted'=>true
            ]);
        }
        $read=$db->prepare(
            'SELECT signed_nonce,payload_sha256,media_total_cents,
               report_requested,marketing_requested
             FROM public.crm_calc_lab_intents
             WHERE organization_id=?::uuid AND submission_id=?::uuid'
        );
        $read->execute([$tenant,$p['submission_id']]);
        $stored=$read->fetch(PDO::FETCH_ASSOC);
        $exact=is_array($stored) &&
            $stored['signed_nonce']===$p['nonce'] &&
            $stored['payload_sha256']===$payloadHash &&
            (int)$stored['media_total_cents']===$p['media_total_cents'] &&
            strictPgBool($stored['report_requested'])===$p['report_requested'] &&
            strictPgBool($stored['marketing_requested'])===$p['marketing_requested'];
        $db->commit();
        return $exact
            ? array_replace($deny('SYNTHETIC_REPLAY_CONFIRMED'),['synthetic_pg_persisted'=>true])
            : $deny('SYNTHETIC_CONFLICT_DENIED');
    } catch (PDOException) {
        if ($db->inTransaction()) $db->rollBack();
        return $deny('SYNTHETIC_SQL_CONFLICT_DENIED');
    } catch (Throwable) {
        if ($db->inTransaction()) $db->rollBack();
        return $deny('SYNTHETIC_UNAVAILABLE');
    }
}
function required(bool $condition,string $label): void
{
    if (!$condition) throw new RuntimeException('Disposable calculator roundtrip failed: '.$label);
}

$first=delivery($pdo,$envelope,$ciKey,$tenant,$now);
required($first['state']==='SYNTHETIC_PG_INTENT_RECORDED','first durable synthetic insert');
$repeat=delivery($pdo,$envelope,$ciKey,$tenant,$now);
required($repeat['state']==='SYNTHETIC_REPLAY_CONFIRMED','same submission and hash replay');

$tampered=$envelope;
$tampered['payload']['marketing_requested']=true;
required(delivery($pdo,$tampered,$ciKey,$tenant,$now)['state']==='SYNTHETIC_MAC_DENIED',
    'forged marketing is denied without hitting SQL');
$changed=$envelope;
$changed['payload']['media_total_cents']=500000;
$changed['signature']=hash_hmac('sha256',Inspector::signingBytes($changed['payload']),$ciKey);
required(delivery($pdo,$changed,$ciKey,$tenant,$now)['state']==='SYNTHETIC_CONFLICT_DENIED',
    'same submission, signed different budget never overwrites');

$nonce=$envelope;
$nonce['payload']['submission_id']='33333333-3333-4333-8333-333333333333';
$nonce['signature']=hash_hmac('sha256',Inspector::signingBytes($nonce['payload']),$ciKey);
required(delivery($pdo,$nonce,$ciKey,$tenant,$now)['state']==='SYNTHETIC_SQL_CONFLICT_DENIED',
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
