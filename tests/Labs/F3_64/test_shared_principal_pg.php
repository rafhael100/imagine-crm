<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/Core/Leads/SharedServiceSiteBindingCandidateResolver.php';
require_once dirname(__DIR__,3).'/app/Core/Leads/SignedServiceHandoffBoundary.php';

use App\Core\Leads\SharedServiceSiteBindingCandidateResolver;
use App\Core\Leads\SignedServiceHandoffBoundary;

if(getenv('GITHUB_ACTIONS')!=='true'
 ||getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
 ||getenv('CRM_F364_SHARED_PRINCIPAL_LAB')!=='1'
 ||getenv('DB_HOST')!=='127.0.0.1'
 ||getenv('DB_DATABASE')!=='f364_shared_ci'
 ||!extension_loaded('pdo_pgsql'))throw new RuntimeException('F3-64 only disposable CI');
function connection(string $user,string $password):PDO {
 return new PDO('pgsql:host=127.0.0.1;port=5432;dbname=f364_shared_ci',
 $user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
$checks=0;
function verify(bool $ok,string $why):void {
 global $checks;++$checks;if(!$ok)throw new RuntimeException('F3-64 FAIL: '.$why);
}
$resolver=new SharedServiceSiteBindingCandidateResolver();
$shared='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$other='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$siteA='44444444-4444-4444-8444-444444444444';
$siteB='77777777-7777-4777-8777-777777777777';
$siteOther='88888888-8888-4888-8888-888888888888';
$a=connection('f364_portal_shared','f364-synthetic-shared-only');
$b=connection('f364_portal_other','f364-synthetic-other-only');
$now=1763000000;
$bindingA=$resolver->resolve($a,$shared,$siteA,$now);
$bindingB=$resolver->resolve($a,$shared,$siteB,$now);
verify(is_array($bindingA)&&is_array($bindingB),'shared Portal principal resolves two independent sites');
verify($bindingA['organization_reference']==='11111111-1111-4111-8111-111111111111','site A tenant from DB');
verify($bindingB['organization_reference']==='99999999-9999-4999-8999-999999999999','site B tenant from DB');
verify($bindingA['organization_reference']!==$bindingB['organization_reference'],'two companies remain distinct');
verify($resolver->resolve($a,$shared,$siteOther,$now)===null,'no unassigned site');
verify($resolver->resolve($a,$other,$siteOther,$now)===null,'shared role does not see other principal');
verify($resolver->resolve($b,$shared,$siteA,$now)===null,'other role cannot read shared principal A');
verify(is_array($resolver->resolve($b,$other,$siteOther,$now)),'other principal has own site');
verify($resolver->resolve($a,$shared,'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaae',$now)===null,'revoked site denied');
verify($resolver->resolve($a,$shared,'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaf',$now)===null,'expired site denied');
verify($resolver->resolve($a,$shared,'bad-site',$now)===null,'invalid site format denied');
foreach([$a,$b] as $conn){
  $role=$conn->query('SELECT rolsuper,rolbypassrls FROM pg_roles WHERE rolname=current_user')
    ->fetch(PDO::FETCH_ASSOC);
  verify(!in_array($role['rolsuper'],[true,'t','1',1],true)
      &&!in_array($role['rolbypassrls'],[true,'t','1',1],true),'roles cannot bypass RLS');
}
try{$a->exec('DELETE FROM public.crm_service_site_bindings');
 throw new RuntimeException('unexpected delete');}
catch(PDOException $e){verify($e->getCode()==='42501','worker cannot delete mapping');}

function frame(array $binding):array{
 return [
  'protocol'=>'imagine.lead-intake','schema_version'=>1,
  'organization_reference'=>$binding['organization_reference'],
  'binding_reference'=>$binding['binding_reference'],
  'submission_reference'=>'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
  'site_reference'=>$binding['site_reference'],
  'form_reference'=>$binding['form_reference'],
  'destination_reference'=>$binding['destination_reference']
 ];
}
$key=random_bytes(48);$gate=new SignedServiceHandoffBoundary();
foreach([$bindingA,$bindingB] as $trusted){
 $frame=frame($trusted);$nonce='dddddddd-dddd-4ddd-8ddd-dddddddddddd';
 $auth=['issued_at'=>$now,'expires_at'=>$now+60,'nonce'=>$nonce,
  'mac'=>hash_hmac('sha256',SignedServiceHandoffBoundary::canonical($frame,$now,$now+60,$nonce),$key)];
 $decision=$gate->inspect($frame,$trusted,$auth,$key,$now);
 verify($decision['reasonCode']==='SIGNED_TECHNICAL_FRAME_ONLY','signed server-matched technical frame');
 verify($decision['authenticatedOperationalTenant']===false && $decision['canDeploy']===false,
   'no production status from shared principal');
 $fraud=[...$frame,'organization_reference'=>'33333333-3333-4333-8333-333333333333'];
 $fraud['site_reference']=$trusted['site_reference'];
 $auth['mac']=hash_hmac('sha256',SignedServiceHandoffBoundary::canonical($fraud,$now,$now+60,$nonce),$key);
 verify($gate->inspect($fraud,$trusted,$auth,$key,$now)['reasonCode']==='SERVER_TENANT_CONTEXT_DENIED',
   'sender cannot choose tenant by signing arbitrary organization');
}
echo "CRM F3-64 PASS: $checks multi-company principal/site/RLS/binding checks.\n";
