<?php

declare(strict_types=1);

/**
 * Real PG18 synthetic consented-lead inbox: site-origin metadata only,
 * fake @example.invalid addresses, HMAC, active authoritative binding,
 * explicit consent, idempotency, revocation and RLS / SQL privilege gates.
 */
if(PHP_SAPI!=='cli'
  || getenv('GITHUB_ACTIONS')!=='true'
  || getenv('CRM_P0_CONSENT_LAB')!=='1'
  || getenv('DB_HOST')!=='127.0.0.1'
  || getenv('DB_DATABASE')!=='crm_p0_consent_lab') {
 fwrite(STDERR,"Refusing non-disposable CRM P0 lead experiment.\n");
 exit(2);
}
$dsn='pgsql:host=127.0.0.1;port=5432;connect_timeout=5;dbname=crm_p0_consent_lab';
$opt=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES=>false,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_TIMEOUT=>6];
$user=new PDO($dsn,'crm_p0_intake_ci','ci-fake-intake-sql-only',$opt);
$admin=new PDO($dsn,'postgres','ci-fake-admin-intake-only',$opt);
if($user->query('SELECT session_user')->fetchColumn()!=='crm_p0_intake_ci'
  ||$admin->query('SELECT current_database()')->fetchColumn()!=='crm_p0_consent_lab'
  ||(int)$admin->query('SHOW server_version_num')->fetchColumn()<180000) {
 throw new RuntimeException('Wrong CRM SQL principal or PostgreSQL version.');
}
$checks=0;
function gate(bool $condition,string $reason): void {
 global $checks;
 ++$checks;
 if(!$condition) throw new RuntimeException('CRM P0 synthetic: '.$reason);
}
function reject(callable $callback,string $reason): void {
 try{$callback();}
 catch(PDOException $e) {
  gate(in_array((string)$e->getCode(),['42501','23505','22023'],true),
    'Wrong SQLSTATE for refusal: '.$reason);
  return;
 }
 throw new RuntimeException('Unsafe CRM operation allowed: '.$reason);
}
$key='ci-fake-sites-crm-lead-intake-hmac-2026-11-only';
$submit=$user->prepare(
 'SELECT crm_p0.ingest_fake_consented_lead('
 .'?::uuid,?::uuid,?::uuid,?::uuid,?::uuid,?::uuid,'
 .'?::integer,?::boolean,?::text,?::text,?::bigint,?::text)'
);
$org='11111111-1111-4111-8111-111111111111';
$otherOrg='88888888-8888-4888-8888-888888888888';
$fixtures=[
 ['binding'=>'22222222-2222-4222-8222-222222222222',
  'site'=>'44444444-4444-4444-8444-444444444444',
  'form'=>'55555555-5555-4555-8555-555555555555',
  'dest'=>'66666666-6666-4666-8666-666666666666',
  'submission'=>'33333333-3333-4333-8333-333333333333',
  'email'=>'synthetic-a@example.invalid'],
 ['binding'=>'23232323-2323-4232-8232-232323232323',
  'site'=>'45454545-4545-4454-8454-454545454545',
  'form'=>'56565656-5656-4565-8565-565656565656',
  'dest'=>'67676767-6767-4676-8676-676767676767',
  'submission'=>'34343434-3434-4343-8343-343434343434',
  'email'=>'synthetic-b@example.invalid'],
];
$exp=time()+55;
function scope(array $x,string $org,int $exp,bool $consent=true,int $revision=1,
 ?string $notice=null,?string $email=null): string {
 global $key;
 $actualEmail=$email??$x['email'];
 $actualNotice=$notice??'ci-notice-1';
 return hash_hmac('sha256',implode('|',[
   'v1',$org,$x['binding'],$x['submission'],$x['site'],$x['form'],
   $x['dest'],(string)$revision,$consent?'consent-true':'consent-false',
   $actualNotice,$actualEmail,(string)$exp
 ]),$key);
}
function ingest(PDOStatement $stmt,array $x,string $org,int $exp,
 bool $consent=true,int $revision=1,?string $notice=null,?string $email=null,
 ?string $mac=null): string {
 $stmt->execute([
  $org,$x['binding'],$x['submission'],$x['site'],$x['form'],$x['dest'],
  $revision,$consent,$notice??'ci-notice-1',$email??$x['email'],
  $exp,$mac??scope($x,$org,$exp,$consent,$revision,$notice,$email),
 ]);
 $v=$stmt->fetchColumn();$stmt->closeCursor();
 return (string)$v;
}
$acl=$admin->query("SELECT
  has_table_privilege('crm_p0_intake_ci','crm_p0.leads','SELECT') AS read_lead,
  has_table_privilege('crm_p0_intake_ci','crm_p0.leads','INSERT') AS write_lead,
  has_table_privilege('crm_p0_intake_ci','crm_p0.bindings','SELECT') AS read_binding,
  has_table_privilege('crm_p0_intake_ci','crm_p0.key_material','SELECT') AS read_key
")->fetch();
gate($acl!==false&&!in_array(true,array_values($acl),true)
 &&!in_array('t',array_values($acl),true),'no direct table privileges');
reject(static fn()=>$user->query('SELECT * FROM crm_p0.leads'), 'raw contact-table read');
reject(static fn()=>$user->query('SELECT * FROM crm_p0.key_material'), 'secret-key read');
reject(static fn()=>$user->query('SELECT * FROM crm_p0.bindings'), 'binding-authority read');
reject(static fn()=>$user->exec('SET ROLE postgres'), 'privilege escalation');

foreach($fixtures as $f) {
 gate(ingest($submit,$f,$org,$exp)==='created','first receipt after durable insert');
 gate(ingest($submit,$f,$org,$exp)==='duplicate','idempotent retry receipt');
}
$changed=$fixtures[0];
$changed['email']='different@example.invalid';
reject(static fn()=>ingest($submit,$changed,$org,$exp),
 'same submission cannot silently overwrite email or binding');

reject(static fn()=>ingest($submit,$fixtures[0],$org,$exp,false),
 'explicit consent missing even when fixture HMAC matches');
reject(static fn()=>ingest($submit,$fixtures[0],$org,$exp,true,1,'outdated-notice'),
 'stale/unknown notice version');
reject(static fn()=>ingest($submit,$fixtures[0],$org,$exp,true,2),
 'binding revision changed without authority');
reject(static fn()=>ingest($submit,$fixtures[0],$otherOrg,$exp),
 'cross-tenant forged binding with valid synthetic HMAC');

$external=$fixtures[0];
$external['submission']='45454545-4545-4454-8454-454545454545';
$external['email']='customer@gmail.com';
reject(static fn()=>ingest($submit,$external,$org,$exp),
 'real email never enters synthetic-only inbox');

reject(static fn()=>ingest($submit,$fixtures[1],$org,time()-10),
 'expired signed request');
reject(static fn()=>ingest($submit,$fixtures[1],$org,time()+500),
 'signatures with excess TTL');
reject(static fn()=>ingest($submit,$fixtures[1],$org,$exp,
 true,1,null,null,str_repeat('a',64)),'unauthenticated MAC');

$revoked=[
 'binding'=>'99999999-9999-4999-8999-999999999999',
 'site'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
 'form'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
 'dest'=>'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
 'submission'=>'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
 'email'=>'synthetic-revoked@example.invalid'
];
reject(static fn()=>ingest($submit,$revoked,$otherOrg,$exp),
 'inactive binding cannot issue CRM lead receipt');
$admin->exec("UPDATE crm_p0.bindings SET active=false
 WHERE organization='11111111-1111-4111-8111-111111111111'
 AND binding='22222222-2222-4222-8222-222222222222'");
$after=$fixtures[0];
$after['submission']='eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';
reject(static fn()=>ingest($submit,$after,$org,$exp),
 'revocation blocks new submissions before INSERT');
$count=(int)$admin->query("SELECT count(*) FROM crm_p0.leads")->fetchColumn();
gate($count===2, 'only two synthetic committed leads; no rejected mutation persisted');
$consentCount=(int)$admin->query("SELECT count(*) FROM crm_p0.leads
 WHERE notice_revision='ci-notice-1' AND consented_at IS NOT NULL
 AND email LIKE '%@example.invalid'")->fetchColumn();
gate($consentCount===2,'server timestamp and fake consent notice persisted');
$force=$admin->query("SELECT relrowsecurity AND relforcerowsecurity AS forced FROM pg_class
 WHERE oid='crm_p0.leads'::regclass")->fetchColumn();
gate(in_array($force,[true,'t','1',1],true),'FORCE RLS preserved');
$reconnected=new PDO($dsn,'postgres','ci-fake-admin-intake-only',$opt);
gate((int)$reconnected->query('SELECT count(*) FROM crm_p0.leads')->fetchColumn()===2,
 'durable fake records survive new SQL connection');
echo "CRM P0 PASS: {$checks} checks, 2 synthetic consented leads, durable and idempotent.\n";
echo "NO-GO: fake consent and shared signing key do not authorize real intake or delivery.\n";
