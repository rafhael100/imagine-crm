<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/Core/Operations/PrivateStagingPreflight.php';
use App\Core\Operations\PrivateStagingPreflight;
$config=[
 'APP_ENV'=>'homologation','APP_DEBUG'=>'false',
 'IMAGINE_HOMOLOGATION_PREPARE'=>'1','IMAGINE_PUBLIC_INTAKE_ENABLED'=>'0',
 'IMAGINE_DELIVERY_WORKER_ENABLED'=>'0','IMAGINE_SYNTHETIC_DATA_ONLY'=>'1',
 'DB_CONNECTION'=>'pgsql'
];
$facts=array_fill_keys([
 'private_database_address','database_connected','database_name_matched',
 'postgres16_or_later','non_privileged_role','membership_rls_enabled',
 'membership_rls_forced','tenant_context_empty','tls_or_loopback'
],true);
function check(array $r,string $code):void{
 global $checks;$checks++;
 if($r['status']!==$code)throw new RuntimeException('F3-58 invalid: '.json_encode($r));
 foreach(['canDeploy','canEnablePublicIntake','canActivateWorker',
          'realLeadPersistenceVerified','functionalHomologationVerified'] as $flag)
   if($r[$flag]!==false)throw new RuntimeException('Unsafe claim: '.$flag);
}
$checks=0;
check(PrivateStagingPreflight::assess($config,$facts),'READ_ONLY_PREFLIGHT_PASSED_REVIEW_REQUIRED');
foreach(array_keys($config) as $key){
 $variant=$config;
 $variant[$key]='unsafe';
 check(PrivateStagingPreflight::assess($variant,$facts),'NO_GO');
}
foreach(array_keys($facts) as $key){
 $variant=$facts;
 $variant[$key]=false;
 check(PrivateStagingPreflight::assess($config,$variant),'NO_GO');
}
check(PrivateStagingPreflight::assess([],[]),'NO_GO');
echo "F3-58 PASS: $checks strict private staging preflight cases, never GO.\n";
