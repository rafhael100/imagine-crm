<?php
declare(strict_types=1);

/**
 * Run on an authorized PRIVATE staging host only, with environment credentials.
 * Queries PostgreSQL catalogs and session state; makes NO schema/data changes.
 * Never run as evidence of production readiness.
 */
require_once dirname(__DIR__,2).'/app/Core/Operations/PrivateStagingPreflight.php';
use App\Core\Operations\PrivateStagingPreflight;
if(PHP_SAPI!=='cli')exit(2);
$names=[
    'APP_ENV','APP_DEBUG','IMAGINE_HOMOLOGATION_PREPARE',
    'IMAGINE_PUBLIC_INTAKE_ENABLED','IMAGINE_DELIVERY_WORKER_ENABLED',
    'IMAGINE_SYNTHETIC_DATA_ONLY','DB_CONNECTION'
];
$configuration=[];
foreach($names as $name){
    $value=getenv($name);
    $configuration[$name]=$value===false?'':$value;
}
$facts=[];
$host=getenv('DB_HOST');
$name=getenv('DB_DATABASE');
$user=getenv('DB_USERNAME');
$password=getenv('DB_PASSWORD');
function privateIp(mixed $value):bool {
    if(!is_string($value)||filter_var($value,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)===false)
        return false;
    $parts=array_map('intval',explode('.',$value));
    return $parts[0]===10
      ||($parts[0]===172&&$parts[1]>=16&&$parts[1]<=31)
      ||($parts[0]===192&&$parts[1]===168)
      ||($parts[0]===127);
}
$facts['private_database_address']=privateIp($host);
$loopback=$host==='127.0.0.1';
// Fail before even contacting a DB when run outside deliberately closed staging.
if(($configuration['APP_ENV']??'')==='homologation'
  &&($configuration['IMAGINE_HOMOLOGATION_PREPARE']??'')==='1'
  &&($configuration['DB_CONNECTION']??'')==='pgsql'
  &&$facts['private_database_address']
  &&is_string($name)&&preg_match('/\A[a-zA-Z0-9_]{1,63}\z/D',$name)===1
  &&is_string($user)&&preg_match('/\A[a-zA-Z0-9_]{1,63}\z/D',$user)===1
  &&is_string($password)&&strlen($password)>0) {
    try {
        $dsn='pgsql:host='.$host.';port=5432;dbname='.$name.';connect_timeout=3';
        if(!$loopback)$dsn.=';sslmode=require';
        $db=new PDO($dsn,$user,$password,[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        ]);
        $summary=$db->query(
            "SELECT current_database() dbname,
              current_setting('server_version_num')::integer version,
              current_setting('app.tenant_id',true) tenant_context,
              r.rolsuper privileged, r.rolbypassrls bypass
             FROM pg_roles r WHERE r.rolname=current_user"
        )->fetch();
        $rls=$db->query(
            "SELECT relrowsecurity enabled,relforcerowsecurity forced
             FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
             WHERE n.nspname='public' AND c.relname='crm_memberships'"
        )->fetch();
        $ssl=$db->query(
            'SELECT ssl FROM pg_stat_ssl WHERE pid=pg_backend_pid()'
        )->fetchColumn();
        $facts['database_connected']=is_array($summary);
        $facts['database_name_matched']=is_array($summary)&&$summary['dbname']===$name;
        $facts['postgres16_or_later']=is_array($summary)&&(int)$summary['version']>=160000;
        $facts['non_privileged_role']=is_array($summary)
            &&!in_array($summary['privileged'],[true,'t','1',1],true)
            &&!in_array($summary['bypass'],[true,'t','1',1],true)
            &&$user!=='postgres';
        $facts['membership_rls_enabled']=is_array($rls)&&in_array($rls['enabled'],[true,'t','1',1],true);
        $facts['membership_rls_forced']=is_array($rls)&&in_array($rls['forced'],[true,'t','1',1],true);
        $facts['tenant_context_empty']=is_array($summary)
            &&in_array($summary['tenant_context'],[null,''],true);
        $facts['tls_or_loopback']=$loopback||in_array($ssl,[true,'t','1',1],true);
    }catch(Throwable){
        // No DSN, address, username, error text or credentials exposed.
        $facts['database_connected']=false;
    }
}
$result=PrivateStagingPreflight::assess($configuration,$facts);
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
exit($result['status']==='READ_ONLY_PREFLIGHT_PASSED_REVIEW_REQUIRED'?0:1);
