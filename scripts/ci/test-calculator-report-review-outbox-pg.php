<?php
declare(strict_types=1);

/**
 * PERF-CALC-OUTBOX-01 — disposable PostgreSQL report-review plan ONLY.
 *
 * A candidate is not a job, a delivery receipt, consent or authorization.
 * No SMTP, mail adapter, queued worker, contact recovery or operational route.
 * Synthetic source quote/contact/ciphertext must have been created in CI.
 */
if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true' ||
    getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm' ||
    getenv('CRM_CALCULATOR_LAB') !== 'synthetic-only' ||
    getenv('PGHOST') !== '127.0.0.1' ||
    getenv('PGDATABASE') !== 'imagine_calc_ci') {
    throw new RuntimeException('Refuse to run report outbox outside disposable CI.');
}
function requireReport(bool $condition,string $label): void {
    if (!$condition) throw new RuntimeException('Calculator report outbox: '.$label);
}
$pdo=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=imagine_calc_ci',
    'crm_calc_ci','ci-only-role', [
       PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
$tenantA='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$tenantB='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$submission='123e4567-e89b-42d3-a456-426614174000';

function scopedReport(PDO $db,string $tenant,callable $callback): mixed {
    $db->beginTransaction();
    try {
        $db->prepare("SELECT set_config('app.tenant_id',?,true)")->execute([$tenant]);
        $result=$callback($db);
        $db->commit();
        return $result;
    } catch (Throwable $e) {
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}

function planReport(PDO $db,string $tenant,string $submission): string {
    return scopedReport($db,$tenant,static function(PDO $db)use($tenant,$submission): string {
        $insert=$db->prepare(
            'INSERT INTO public.crm_calc_lab_report_outbox
               (organization_id,submission_id,event_kind,intent_payload_sha256)
             SELECT i.organization_id,i.submission_id,\'report_request_review\',i.payload_sha256
             FROM public.crm_calc_lab_intents AS i
             JOIN public.crm_calc_lab_contact_metadata AS m
               ON m.organization_id=i.organization_id AND m.submission_id=i.submission_id
             JOIN public.crm_calc_lab_contact_ciphertext AS c
               ON c.organization_id=i.organization_id AND c.submission_id=i.submission_id
             WHERE i.organization_id=?::uuid AND i.submission_id=?::uuid
               AND i.report_requested IS TRUE
               AND m.report_request_claimed IS TRUE
               AND m.contact_identity_verified IS FALSE
               AND m.marketing_consent_proven IS FALSE
               AND c.production_authorized IS FALSE
             ON CONFLICT (organization_id,submission_id,event_kind) DO NOTHING
             RETURNING submission_id'
        );
        $insert->execute([$tenant,$submission]);
        if (is_array($insert->fetch(PDO::FETCH_ASSOC))) return 'LAB_REPORT_REVIEW_PLANNED';
        $lookup=$db->prepare(
            'SELECT o.state,o.send_enabled,o.contact_verified,
               o.marketing_consent_verified,
               (o.intent_payload_sha256=i.payload_sha256) AS same_hash
             FROM public.crm_calc_lab_report_outbox AS o
             JOIN public.crm_calc_lab_intents AS i
               ON i.organization_id=o.organization_id AND i.submission_id=o.submission_id
             WHERE o.organization_id=?::uuid AND o.submission_id=?::uuid
               AND o.event_kind=\'report_request_review\''
        );
        $lookup->execute([$tenant,$submission]);
        $row=$lookup->fetch(PDO::FETCH_ASSOC);
        $isFalse=static fn(mixed $x):bool=>in_array($x,[false,'f','false','0'],true);
        $isTrue=static fn(mixed $x):bool=>in_array($x,[true,'t','true','1'],true);
        return is_array($row) &&
            $row['state']==='NO_DISPATCH_REVIEW_ONLY' &&
            $isFalse($row['send_enabled']) &&
            $isFalse($row['contact_verified']) &&
            $isFalse($row['marketing_consent_verified']) &&
            $isTrue($row['same_hash'])
            ? 'LAB_REPORT_REVIEW_IDEMPOTENT' : 'LAB_REPORT_REVIEW_UNAVAILABLE';
    });
}

requireReport(planReport($pdo,$tenantA,$submission)==='LAB_REPORT_REVIEW_PLANNED',
    'first report request creates one non-dispatch candidate');
requireReport(planReport($pdo,$tenantA,$submission)==='LAB_REPORT_REVIEW_IDEMPOTENT',
    'identical repeated report request cannot duplicate row');
requireReport(planReport($pdo,$tenantB,$submission)==='LAB_REPORT_REVIEW_UNAVAILABLE',
    'another tenant cannot see or plan tenant A report');
requireReport(planReport($pdo,$tenantA,'99999999-9999-4999-8999-999999999999')
    ==='LAB_REPORT_REVIEW_UNAVAILABLE','quote without approved matching contact/cipher not scheduled');
requireReport(planReport($pdo,'',$submission)==='LAB_REPORT_REVIEW_UNAVAILABLE',
    'missing tenant marker cannot see report plan');

$summary=scopedReport($pdo,$tenantA,static fn(PDO $db):array=>$db->query(
    'SELECT event_kind,state,send_enabled,contact_verified,
       marketing_consent_verified FROM public.crm_calc_lab_report_outbox'
)->fetchAll(PDO::FETCH_ASSOC));
requireReport(count($summary)===1,'exactly one report-review candidate');
requireReport($summary[0]['event_kind']==='report_request_review' &&
    $summary[0]['state']==='NO_DISPATCH_REVIEW_ONLY','no delivery jobs produced');
requireReport(in_array($summary[0]['send_enabled'],[false,'f','false','0'],true) &&
    in_array($summary[0]['marketing_consent_verified'],[false,'f','false','0'],true),
    'outbox cannot authorize email or marketing');

$priv=scopedReport($pdo,$tenantA,static fn(PDO $db):array=>$db->query(
    "SELECT has_table_privilege(current_user,
        'public.crm_calc_lab_report_outbox','UPDATE'),
            has_table_privilege(current_user,
        'public.crm_calc_lab_report_outbox','DELETE')"
)->fetch(PDO::FETCH_NUM));
requireReport(!in_array(true,$priv,true) && !in_array('t',$priv,true) &&
    !in_array('1',$priv,true),'restricted role cannot change or delete outbox rows');
requireReport(strpos(json_encode($summary,JSON_THROW_ON_ERROR),'example.test')===false,
    'no contact data in reviewed outbox records');
echo "PASS: CI report review outbox is immutable, idempotent and tenant isolated; no recipient, dispatch, SMTP, marketing or real delivery receipt\n";
