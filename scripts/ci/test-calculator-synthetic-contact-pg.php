<?php
declare(strict_types=1);
/**
 * CI-only fake contact receipt in PG. NO public endpoint, NO real PII,
 * NO delivery, NO marketing. Requires an already recorded Node quote fixture.
 */
require_once __DIR__.'/../../app/Core/Leads/SyntheticCalculatorContactBoundary.php';
use App\Core\Leads\SyntheticCalculatorContactBoundary as Contact;

if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true' ||
    getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm' ||
    getenv('CRM_CALCULATOR_LAB') !== 'synthetic-only' ||
    getenv('PGHOST') !== '127.0.0.1' ||
    getenv('PGDATABASE') !== 'imagine_calc_ci') {
    throw new RuntimeException('Not disposable CI: fake contact lab denied');
}
$key='IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$tenant='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$data=[
    'submission_id'=>'123e4567-e89b-42d3-a456-426614174000',
    'contact_name'=>'Sample Customer',
    'contact_email'=>'client@example.test',
    'report_requested'=>true,
    'marketing_requested'=>false,
    'privacy_notice_version'=>'lab-notice-v1',
];
$preview=Contact::inspect($data,$key);
$pdo=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=imagine_calc_ci',
    'crm_calc_ci','ci-only-role',[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
function checked(bool $ok,string $label): void {
    if (!$ok) throw new RuntimeException('Fake-contact roundtrip: '.$label);
}
function scoped(PDO $db,string $tenant,callable $task): mixed {
    $db->beginTransaction();
    try {
        $db->prepare("SELECT set_config('app.tenant_id',?,true)")->execute([$tenant]);
        $result=$task($db);
        $db->commit();
        return $result;
    } catch (Throwable $e) {
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}
function store(PDO $db,string $tenant,array $p): array {
    return scoped($db,$tenant,static function(PDO $conn)use($tenant,$p):array {
        $st=$conn->prepare(
            'INSERT INTO public.crm_calc_lab_contact_metadata
              (organization_id,submission_id,contact_fingerprint,
               report_request_claimed,marketing_interest_claimed)
             VALUES (?::uuid,?::uuid,?,?,?)
             ON CONFLICT (organization_id,submission_id) DO NOTHING
             RETURNING submission_id'
        );
        $st->execute([
            $tenant,$p['submission_id'],$p['contact_fingerprint'],
            $p['report_request_claimed']?'true':'false',
            $p['marketing_interest_claimed']?'true':'false',
        ]);
        if(is_array($st->fetch(PDO::FETCH_ASSOC)))return ['state'=>'SYNTHETIC_CONTACT_METADATA_INSERTED'];
        $read=$conn->prepare(
            'SELECT contact_fingerprint,report_request_claimed,
                marketing_interest_claimed,contact_identity_verified,marketing_consent_proven
             FROM public.crm_calc_lab_contact_metadata
             WHERE organization_id=?::uuid AND submission_id=?::uuid'
        );
        $read->execute([$tenant,$p['submission_id']]);
        $row=$read->fetch(PDO::FETCH_ASSOC);
        $bool=static function(mixed $value): ?bool{
            if(in_array($value,[true,'t','true','1'],true))return true;
            if(in_array($value,[false,'f','false','0'],true))return false;
            return null;
        };
        return ['state'=>is_array($row) &&
            $row['contact_fingerprint']===$p['contact_fingerprint'] &&
            $bool($row['report_request_claimed'])===$p['report_request_claimed'] &&
            $bool($row['marketing_interest_claimed'])===$p['marketing_interest_claimed'] &&
            $bool($row['contact_identity_verified'])===false &&
            $bool($row['marketing_consent_proven'])===false
            ? 'SYNTHETIC_CONTACT_IDEMPOTENT_REPLAY' : 'SYNTHETIC_CONTACT_CONFLICT_DENIED'];
    });
}

checked(store($pdo,$tenant,$preview)['state']==='SYNTHETIC_CONTACT_METADATA_INSERTED','insert first fixture');
checked(store($pdo,$tenant,$preview)['state']==='SYNTHETIC_CONTACT_IDEMPOTENT_REPLAY','no duplicate');
$changed=$data;$changed['marketing_requested']=true;
$forgedMarketing=Contact::inspect($changed,$key);
checked(store($pdo,$tenant,$forgedMarketing)['state']==='SYNTHETIC_CONTACT_CONFLICT_DENIED',
    'changed checkbox cannot rewrite original intent');
$changed=$data;$changed['contact_email']='other@example.invalid';
$other=Contact::inspect($changed,$key);
checked(store($pdo,$tenant,$other)['state']==='SYNTHETIC_CONTACT_CONFLICT_DENIED',
    'changed email cannot overwrite fingerprint');
$rejected=$data;$rejected['contact_email']='real@gmail.com';
try{Contact::inspect($rejected,$key);throw new RuntimeException('Real email accepted');}
catch (InvalidArgumentException) {}
$counts=scoped($pdo,$tenant,static function(PDO $db):array{
    $c=(int)$db->query('SELECT count(*) FROM public.crm_calc_lab_contact_metadata')->fetchColumn();
    $verified=(int)$db->query('SELECT count(*) FROM public.crm_calc_lab_contact_metadata
          WHERE contact_identity_verified OR marketing_consent_proven')->fetchColumn();
    return [$c,$verified];
});
checked($counts===[1,0],'one fake contact and no consent');
$otherTenant=scoped($pdo,'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
    static fn(PDO $db)=>(int)$db->query(
        'SELECT count(*) FROM public.crm_calc_lab_contact_metadata')->fetchColumn());
checked($otherTenant===0,'RLS blocks cross-tenant contact metadata');
$noTenant=scoped($pdo,'',
    static fn(PDO $db)=>(int)$db->query(
        'SELECT count(*) FROM public.crm_calc_lab_contact_metadata')->fetchColumn());
checked($noTenant===0,'no ambient tenant cannot read');
$priv=scoped($pdo,$tenant,static fn(PDO $db):array=>$db->query(
    "SELECT has_table_privilege(current_user,'public.crm_calc_lab_contact_metadata','UPDATE'),
            has_table_privilege(current_user,'public.crm_calc_lab_contact_metadata','DELETE')"
)->fetch(PDO::FETCH_NUM));
checked(!in_array(true,$priv,true) && !in_array('t',$priv,true) &&
    !in_array('1',$priv,true),'no mutation privileges');
echo "PASS: fictitious example.test contact -> HMAC-only PG metadata; replay and forged opt-in rejected, RLS verified; NO REAL PII/email/marketing\n";
