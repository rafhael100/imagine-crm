<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/Core/Leads/TrustedServiceBindingCandidateResolver.php';
require_once dirname(__DIR__,3).'/app/Core/Leads/SignedServiceHandoffBoundary.php';

use App\Core\Leads\TrustedServiceBindingCandidateResolver;
use App\Core\Leads\SignedServiceHandoffBoundary;

if (getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('CRM_F360_BINDING_CI')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='f360_binding_ci'
    || !extension_loaded('pdo_pgsql')) {
    throw new RuntimeException('F3-60 restricted to disposable GitHub PG CI');
}
function db(string $user,string $password):PDO {
    return new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f360_binding_ci',
      $user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
$count=0;
function check(bool $pass,string $name):void {
    global $count; ++$count;
    if(!$pass)throw new RuntimeException('F3-60 FAIL: '.$name);
}
$now=1763000000;
$orgA='11111111-1111-4111-8111-111111111111';
$principalA='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$principalB='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$a=db('f360_worker_a','f360-synthetic-a-only');
$b=db('f360_worker_b','f360-synthetic-b-only');
foreach([[$a,'f360_worker_a'],[$b,'f360_worker_b']] as [$db,$expected]) {
    check($db->query('SELECT current_user')->fetchColumn()===$expected,'non-admin principal role');
    $priv=$db->query('SELECT rolsuper,rolbypassrls FROM pg_roles WHERE rolname=current_user')->fetch(PDO::FETCH_ASSOC);
    check(!in_array($priv['rolsuper'],[true,'t','1',1],true)
       && !in_array($priv['rolbypassrls'],[true,'t','1',1],true),'role cannot bypass RLS');
}
$lookup=new TrustedServiceBindingCandidateResolver();
$binding=$lookup->resolve($a,$principalA,$now);
check(is_array($binding) && $binding['organization_reference']===$orgA,'authoritative A binding loaded');
check($lookup->resolve($a,$principalB,$now)===null,'A cannot resolve B principal');
check($lookup->resolve($b,$principalA,$now)===null,'B cannot resolve A principal');
check($lookup->resolve($b,$principalB,$now)['organization_reference']==='99999999-9999-4999-8999-999999999999',
  'B resolves only B principal');
check($lookup->resolve($a,'cccccccc-cccc-4ccc-8ccc-cccccccccccc',$now)===null,'revoked principal denied');
check($lookup->resolve($a,'dddddddd-dddd-4ddd-8ddd-dddddddddddd',$now)===null,'expired principal denied');
check($lookup->resolve($a,'not-a-principal',$now)===null,'invalid principal denied');
check($lookup->resolve($a,'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',$now)===null,'unknown principal denied');
check($lookup->resolve($a,$principalA,2000000001)===null,'current principal expired after deadline');
try {
    $a->exec("DELETE FROM public.crm_service_bindings");
    throw new RuntimeException('unexpected DELETE permission');
} catch (PDOException $e) {
    check($e->getCode()==='42501','service role cannot delete binding');
}
try {
    $a->exec("UPDATE public.crm_service_bindings SET state='revoked'");
    throw new RuntimeException('unexpected UPDATE permission');
} catch (PDOException $e) {
    check($e->getCode()==='42501','service role cannot revoke binding');
}
$frame=[
    'protocol'=>'imagine.lead-intake','schema_version'=>1,
    'organization_reference'=>$binding['organization_reference'],
    'binding_reference'=>$binding['binding_reference'],
    'submission_reference'=>'f9f9f9f9-f9f9-4f9f-8f9f-f9f9f9f9f9f9',
    'site_reference'=>$binding['site_reference'],
    'form_reference'=>$binding['form_reference'],
    'destination_reference'=>$binding['destination_reference'],
];
$key=random_bytes(48);$nonce='eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';
function authFor(array $f,string $key,int $now,string $nonce):array {
    return ['issued_at'=>$now,'expires_at'=>$now+60,'nonce'=>$nonce,
      'mac'=>hash_hmac('sha256',SignedServiceHandoffBoundary::canonical($f,$now,$now+60,$nonce),$key)];
}
$gate=new SignedServiceHandoffBoundary();
$verified=$gate->inspect($frame,$binding,authFor($frame,$key,$now,$nonce),$key,$now);
check($verified['reasonCode']==='SIGNED_TECHNICAL_FRAME_ONLY','signed candidate matched DB-scoped binding');
foreach(['authenticatedServicePrincipal','authenticatedOperationalTenant','atomicNonceClaimed','realLeadPersisted',
         'canAcknowledgeVisitor','canDeploy'] as $flag)check($verified[$flag]===false,'no operational authority: '.$flag);
$forged=[...$frame,'organization_reference'=>'99999999-9999-4999-8999-999999999999'];
$denied=$gate->inspect($forged,$binding,authFor($forged,$key,$now,$nonce),$key,$now);
check($denied['reasonCode']==='SERVER_TENANT_CONTEXT_DENIED','sender cannot override server tenant');
echo "CRM F3-60 PASS: $count principal binding/RLS/revocation/deny checks (CI-only).\n";
