<?php
declare(strict_types=1);

/**
 * PERF-CALC-PII-04 — CI-only fake contact encrypted-at-rest roundtrip.
 * No real PII; the random 32-byte key is not persisted or printed.
 */
require_once __DIR__.'/../../app/Core/Leads/SyntheticCalculatorContactBoundary.php';
require_once __DIR__.'/../../app/Core/Leads/SyntheticCalculatorContactCipher.php';
use App\Core\Leads\SyntheticCalculatorContactCipher as Cipher;
use App\Core\Leads\SyntheticCalculatorContactBoundary as Boundary;

if (getenv('CI')!=='true' || getenv('GITHUB_ACTIONS')!=='true' ||
    getenv('GITHUB_REPOSITORY')!=='rafhael100/imagine-crm' ||
    getenv('PGHOST')!=='127.0.0.1' ||
    getenv('PGDATABASE')!=='imagine_calc_ci' ||
    getenv('CRM_CALCULATOR_LAB')!=='synthetic-only') {
    throw new RuntimeException('Synthetic-only CI encryption test required.');
}
function must(bool $value,string $label):void{
    if(!$value)throw new RuntimeException('Synthetic vault test failed: '.$label);
}
$tenant='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$otherTenant='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$submission='123e4567-e89b-42d3-a456-426614174000';
$disposable='IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$fixture=[
    'submission_id'=>$submission,
    'contact_name'=>'Sample Customer',
    'contact_email'=>'client@example.test',
    'report_requested'=>true,
    'marketing_requested'=>false,
    'privacy_notice_version'=>'lab-notice-v1',
];
$key=random_bytes(32);
$sealed=Cipher::seal($fixture,$disposable,$tenant,$submission,
    'lab-key-rotation-01',$key);
$metadata=Boundary::inspect($fixture,$disposable);
$db=new PDO('pgsql:host=127.0.0.1;port=5432;dbname=imagine_calc_ci',
    'crm_calc_ci','ci-only-role',[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
function tenantQuery(PDO $db,string $tenant,callable $fn):mixed{
    $db->beginTransaction();
    try{
        $scope=$db->prepare("SELECT set_config('app.tenant_id',?,true)");
        $scope->execute([$tenant]);
        $result=$fn($db);
        $db->commit();
        return $result;
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}
$insert=tenantQuery($db,$tenant,static function(PDO $db) use ($tenant,$submission,$sealed):int {
    $st=$db->prepare(
        'INSERT INTO public.crm_calc_lab_contact_ciphertext
           (organization_id,submission_id,key_id,nonce_b64,tag_b64,ciphertext_b64)
         VALUES (?::uuid,?::uuid,?,?,?,?)
         ON CONFLICT(organization_id,submission_id) DO NOTHING
         RETURNING submission_id'
    );
    $st->execute([
        $tenant,$submission,$sealed['key_id'],$sealed['nonce_b64'],
        $sealed['tag_b64'],$sealed['ciphertext_b64'],
    ]);
    return count($st->fetchAll(PDO::FETCH_ASSOC));
});
must($insert===1,'first encrypted fixture inserted');
$loaded=tenantQuery($db,$tenant,static function(PDO $db)use($tenant,$submission):array {
    $st=$db->prepare(
       'SELECT key_id,nonce_b64,tag_b64,ciphertext_b64,production_authorized
        FROM public.crm_calc_lab_contact_ciphertext
        WHERE organization_id=?::uuid AND submission_id=?::uuid'
    );
    $st->execute([$tenant,$submission]);
    $row=$st->fetch(PDO::FETCH_ASSOC);
    if(!is_array($row))throw new RuntimeException('Synthetic row invisible to own scope');
    return $row;
});
must(!str_contains(json_encode($loaded,JSON_THROW_ON_ERROR),'client@example.test'),
    'DB fetch has no clear email');
must(!str_contains(json_encode($loaded,JSON_THROW_ON_ERROR),'Sample Customer'),
    'DB fetch has no clear name');
must(in_array($loaded['production_authorized'],[false,'f','false','0'],true),
    'production authorization stays false');
$forOpening=[
  'schema'=>'synthetic.calc.contact.aes256gcm.v1',
  'key_id'=>$loaded['key_id'],
  'tenant_id'=>$tenant,
  'submission_id'=>$submission,
  'nonce_b64'=>$loaded['nonce_b64'],
  'tag_b64'=>$loaded['tag_b64'],
  'ciphertext_b64'=>$loaded['ciphertext_b64'],
  'production_authorized'=>false,
  'contact_identity_verified'=>false,
  'marketing_consent_proven'=>false,
  'can_send_report'=>false,
  'can_send_marketing'=>false
];
$decoded=Cipher::openFixture($forOpening,$tenant,$submission,$sealed['key_id'],$key);
must($decoded['contact_name']==='Sample Customer' &&
     $decoded['contact_email']==='client@example.test',
    'correct key and tenant can privately unseal fake contact');
$denied=false;
try{Cipher::openFixture($forOpening,$otherTenant,$submission,$sealed['key_id'],$key);}
catch(InvalidArgumentException){$denied=true;}
must($denied,'other tenant cannot unseal');
$otherCount=tenantQuery($db,$otherTenant,static fn(PDO $db):int=>
    (int)$db->query('SELECT count(*) FROM public.crm_calc_lab_contact_ciphertext')->fetchColumn());
must($otherCount===0,'cross tenant RLS read blocked');
$replay=tenantQuery($db,$tenant,static function(PDO $db)use($tenant,$submission,$sealed):int {
    $st=$db->prepare(
        'INSERT INTO public.crm_calc_lab_contact_ciphertext
           (organization_id,submission_id,key_id,nonce_b64,tag_b64,ciphertext_b64)
         VALUES (?::uuid,?::uuid,?,?,?,?)
         ON CONFLICT(organization_id,submission_id) DO NOTHING RETURNING submission_id'
    );
    $st->execute([$tenant,$submission,$sealed['key_id'],$sealed['nonce_b64'],
        $sealed['tag_b64'],$sealed['ciphertext_b64']]);
    return count($st->fetchAll(PDO::FETCH_ASSOC));
});
must($replay===0,'duplicate sealed contact cannot create another row');
$priv=tenantQuery($db,$tenant,static fn(PDO $db):array=>$db->query(
    "SELECT has_table_privilege(current_user,'public.crm_calc_lab_contact_ciphertext','UPDATE'),
            has_table_privilege(current_user,'public.crm_calc_lab_contact_ciphertext','DELETE')"
)->fetch(PDO::FETCH_NUM));
must(!in_array(true,$priv,true)&&!in_array('t',$priv,true)&&!in_array('1',$priv,true),
    'no edit or removal privileges');
must($metadata['marketing_consent_proven']===false &&
  $metadata['can_send_report']===false &&
  $metadata['can_send_marketing']===false,
  'ciphertext does not create opt-in or permission to email');
echo "PASS: fake contact sealed AES-GCM to disposable PG16; own scope may decrypt in CI, other tenant denied, no cleartext at rest and no sending\n";
