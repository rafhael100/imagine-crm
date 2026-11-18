<?php

declare(strict_types=1);

use App\Homologation\S2S\AgentsExecutionReceipt;
use Illuminate\Support\Str;

require __DIR__.'/private-crm-toolbox.php';
umask(0077);
set_exception_handler(static function (Throwable $error): void {
    $causes=[];
    for ($index=0;$error!==null && $index<3;$index++,$error=$error->getPrevious()) {
        $cause=['type'=>get_class($error),'source'=>basename($error->getFile()),'line'=>$error->getLine()];
        if (preg_match('/\A[A-Z0-9]{5}\z/D',(string)$error->getCode())) $cause['sqlstate']=(string)$error->getCode();
        $causes[]=$cause;
    }
    fwrite(STDERR,'PRIVATE_EXECUTION_MONITOR_FIXTURE_FAILURE '.json_encode(['causes'=>$causes],JSON_THROW_ON_ERROR).PHP_EOL);
    exit(1);
});
if (getenv('CRM_PRIVATE_CI_MONITOR_PROFILE')!=='execution-monitor-v1'
    || getenv('CRM_PRIVATE_CI_PROJECT')!=='imagine-crm-journey-ci'
    || getenv('CRM_N8N_EXECUTION_MONITOR')!=='synthetic-private-v1'
    || is_file('/private/publication-opened.json')) {
    throw new RuntimeException('Disposable monitor fixture profile required.');
}
$mode=$argv[1]??'';
$vault='/private/execution-monitor-fixtures.json';
$db=privateCrmOwner();

function monitorRequire(bool $value): void
{
    if (!$value) throw new RuntimeException('Monitor fixture invariant failed.');
}

function monitorVault(string $file): array
{
    monitorRequire(is_file($file) && !is_link($file) && (fileperms($file)&0077)===0);
    return json_decode(file_get_contents($file),true,64,JSON_THROW_ON_ERROR);
}

function monitorScalar(PDO $db,string $sql,array $values=[]): mixed
{
    $statement=$db->prepare($sql);$statement->execute($values);
    return $statement->fetchColumn();
}

/** Synthetic presentation fixture only. No Agents HTTP call or n8n execution. */
function monitorObservation(PDO $db,array $source,string $state,string $queue,int $attempt,
    string $ingressKey,string $executionKey): array
{
    $now=time();
    $query=AgentsExecutionReceipt::query($source['company_id'],$source['tenant_ref'],$source['event_ref'],
        $source['payload_hash'],$ingressKey,$now,'SYNTHETIC_MONITOR_'.strtoupper(bin2hex(random_bytes(10))),
        'SYNTHETIC_MONITOR_'.strtoupper(bin2hex(random_bytes(10))));
    $wire=[
        'schema'=>'AUTO_IA_EXECUTION_STATUS_V1','issuer'=>'imagine-ai-agents','audience'=>'imagine-crm',
        'company_id'=>$source['company_id'],'tenant_ref'=>$source['tenant_ref'],'event_ref'=>$source['event_ref'],
        'payload_hash'=>$source['payload_hash'],'lead_id'=>$source['lead_id'],
        'task_payload_hash'=>$state==='NOT_STAGED'?'':hash('sha256','SYNTHETIC_PRESENTATION_TASK:'.$source['event_id']),
        'execution_state'=>$state,'queue_state'=>$queue,
        'result_code'=>in_array($state,['RESULT','ACKED'],true)?'PREPARED':'NONE',
        'observation_hash'=>'','attempt'=>$attempt,
        'request_ref'=>$query['request_ref'],'nonce_ref'=>$query['nonce_ref'],
        'issued_at'=>$now,'expires_at'=>$now+30,'key_id'=>'SYNTHETIC_EXECUTION_STATUS_KEY',
    ];
    $facts=['company_id','tenant_ref','event_ref','payload_hash','lead_id','task_payload_hash',
        'execution_state','queue_state','result_code','attempt'];
    $wire['observation_hash']=hash('sha256',"IMAGINE_AGENTS_EXECUTION_OBSERVATION_V1\n".
        implode("\n",array_map(static fn(string $field):string=>(string)$wire[$field],$facts)));
    $wire['signature']=hash_hmac('sha256',"IMAGINE_AGENTS_EXECUTION_STATUS_V1\n".
        implode("\n",array_map(static fn(mixed $value):string=>(string)$value,array_values($wire))),$executionKey);
    $raw=json_encode($wire,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
    monitorRequire(AgentsExecutionReceipt::validate($raw,$query,$executionKey,$now)===$wire);
    $db->exec('SET LOCAL ROLE crm_auto_execution_reconciler');
    $view=json_decode(monitorScalar($db,
        'SELECT crm_auto_service.project_execution(?::uuid,?::uuid,?::jsonb,?::text)',
        [$source['company_id'],$source['event_id'],json_encode($query,JSON_THROW_ON_ERROR),$raw]),true,64,JSON_THROW_ON_ERROR);
    $db->exec('RESET ROLE');
    monitorRequire($view['observation']['execution_state']===$state && $view['crm_mutated']===false);
    return $view;
}

function monitorSnapshot(PDO $db): array
{
    // IAM sessions, abuse counters and Redis legitimately change on real authentication.
    // Every private business table and every S2S table is included without filtering rows.
    $tables=$db->query("SELECT n.nspname,c.relname FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relkind='r' AND ((n.nspname='public' AND c.relname LIKE 'crm_private_%')
        OR n.nspname='crm_auto_service') ORDER BY n.nspname,c.relname")->fetchAll(PDO::FETCH_NUM);
    $snapshot=[];
    foreach ($tables as [$schema,$table]) {
        monitorRequire(preg_match('/\A[a-z_]+\z/D',$schema)===1 && preg_match('/\A[a-z_]+\z/D',$table)===1);
        $sql='SELECT count(*) AS rows,encode(sha256(convert_to(COALESCE(string_agg(to_jsonb(t)::text,chr(10) '
            .'ORDER BY to_jsonb(t)::text),\'\'),\'UTF8\')),\'hex\') AS sha256 FROM "'.$schema.'"."'.$table.'" t';
        $row=$db->query($sql)->fetch(PDO::FETCH_ASSOC);
        $snapshot[$schema.'.'.$table]=['rows'=>(int)$row['rows'],'sha256'=>$row['sha256']];
    }
    monitorRequire(isset($snapshot['crm_auto_service.execution_observations'],
        $snapshot['crm_auto_service.outbox'],$snapshot['public.crm_private_leads'],
        $snapshot['public.crm_private_automation_runs']));
    return $snapshot;
}

if ($mode==='seed') {
    monitorRequire(!file_exists($vault));
    $fixtures=monitorVault('/private/fixtures.json');
    $suffix=strtoupper(bin2hex(random_bytes(4)));
    $db->beginTransaction();
    try {
        $fixtures['clientEmpty']=privateCrmSeedAccount($db,'lab-monitor-empty-'.strtolower($suffix),
            bin2hex(random_bytes(16)),'client','EMPRESA_TESTE_MONITOR_EMPTY_'.$suffix);
        $fixtures['member']=privateCrmSeedAccount($db,'lab-monitor-member-'.strtolower($suffix),
            bin2hex(random_bytes(16)),'client','EMPRESA_TESTE_MONITOR_MEMBER_'.$suffix);
        privateCrmSql($db,'UPDATE public.crm_memberships SET organization_id=? WHERE global_identity_id=?',
            [$fixtures['clientA']['organization'],$fixtures['member']['identity']]);
        privateCrmSql($db,"UPDATE public.crm_private_accounts SET organization_id=?,team_role='member' WHERE identity_id=?",
            [$fixtures['clientA']['organization'],$fixtures['member']['identity']]);
        $fixtures['member']['organization']=$fixtures['clientA']['organization'];
        $events=[];$now=time();$ingressKey=bin2hex(random_bytes(32));$executionKey=bin2hex(random_bytes(32));
        $nominal=[['NOT_STAGED','NONE',0],['NOT_STARTED','PENDING',0],['NOT_STARTED','LEASED',0],
            ['STARTED','LEASED',1],['RESULT','LEASED',1],['ACKED','ACKED',1]];
        foreach (['clientA'=>56,'clientB'=>1] as $actor=>$count) {
            $company=$fixtures[$actor]['organization'];$identity=$fixtures[$actor]['identity'];
            $tenant='SYNTHETIC_MONITOR_'.strtoupper($actor);
            privateCrmSql($db,'INSERT INTO crm_auto_service.outbound_grants VALUES (?,?,\'imagine-ai-agents\',\'lead.ingest\',true,1,?)',[$company,$tenant,$now+3600]);
            privateCrmSql($db,'INSERT INTO crm_auto_service.execution_read_grants VALUES (?,?,\'imagine-ai-agents\',\'event.execution.read\',true,1,?)',[$company,$tenant,$now+3600]);
            $funnel=monitorScalar($db,'SELECT id FROM public.crm_private_funnels WHERE organization_id=? ORDER BY id LIMIT 1',[$company]);
            for ($index=0;$index<$count;$index++) {
                $lead=(string)Str::uuid();$event=(string)Str::uuid();$at=$now-1000+$index;
                $title='LEAD_TESTE_MONITOR_'.strtoupper($actor).'_'.$index;
                privateCrmSql($db,'INSERT INTO public.crm_private_leads(id,organization_id,funnel_id,title,created_at) VALUES (?,?,?,?,?)',[$lead,$company,$funnel,$title,$at]);
                privateCrmSql($db,"INSERT INTO public.crm_private_events(id,organization_id,actor_id,object_id,action,occurred_at) VALUES (?,?,?,?,'lead.created',?)",[$event,$company,$identity,$lead,$at]);
                $db->exec('SET LOCAL ROLE crm_auto_sender');
                $outbox=json_decode(monitorScalar($db,'SELECT crm_auto_service.stage_outbound(?::uuid,?::uuid)',[$company,$event]),true,32,JSON_THROW_ON_ERROR);
                $db->exec('RESET ROLE');
                $source=['company_id'=>$company,'tenant_ref'=>$tenant,'event_id'=>$event,
                    'event_ref'=>'SYNTHETIC_'.strtoupper(str_replace('-','',$event)),
                    'payload_hash'=>hash('sha256',$outbox['payload']),'lead_id'=>$lead,
                    'lead_title'=>$title,'occurred_at'=>$at,'index'=>$index];
                $path=[];
                if ($actor==='clientB' || $index===50) $path=array_slice($nominal,0,1);
                elseif ($index===51) $path=array_slice($nominal,0,2);
                elseif ($index===52) $path=array_slice($nominal,0,4);
                elseif ($index===53) $path=array_slice($nominal,0,5);
                elseif ($index===54) $path=[...array_slice($nominal,0,4),['UNKNOWN','LEASED',1]];
                elseif ($index===55) $path=$nominal;
                foreach ($path as [$state,$queue,$attempt]) monitorObservation($db,$source,$state,$queue,$attempt,$ingressKey,$executionKey);
                if ($index===55) {
                    $db->exec('SET LOCAL ROLE crm_auto_sender');
                    privateCrmSql($db,'SELECT crm_auto_service.ack_outbound(?::uuid,?::uuid,?::text)',
                        [$company,$event,hash('sha256','SYNTHETIC_PRESENTATION_ADMISSION:'.$event)]);
                    $db->exec('RESET ROLE');
                    $source['original_payload']=$outbox['payload'];
                }
                $source['state']=$path===[]?'UNOBSERVED':$path[count($path)-1][0];
                $source['history_count']=count($path);
                $source['history_states']=array_reverse(array_column($path,0));
                $events[$actor][]=$source;
            }
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();throw $error;
    }
    $fixtures['monitor']=['profile'=>'execution-monitor-v1','synthetic_presentation_only'=>true,
        'n8n_executions_in_this_fixture'=>0,'events'=>$events];
    monitorRequire(file_put_contents($vault,json_encode($fixtures,JSON_THROW_ON_ERROR),LOCK_EX)!==false);
    chmod($vault,0600);
    echo "PRIVATE_EXECUTION_MONITOR_SYNTHETIC_FIXTURES_CREATED; no n8n execution claimed.\n";
    exit;
}

$fixtures=monitorVault($vault);
monitorRequire(($fixtures['monitor']['profile']??null)==='execution-monitor-v1');
$main=$fixtures['monitor']['events']['clientA'][55];
$company=$fixtures['clientA']['organization'];
$peer=$fixtures['clientB'];
if ($mode==='snapshot') {
    echo json_encode(monitorSnapshot($db),JSON_THROW_ON_ERROR).PHP_EOL;
} elseif ($mode==='revoke-read-grant') {
    privateCrmSql($db,'UPDATE crm_auto_service.execution_read_grants SET active=false,revision=revision+1 WHERE company_id=? AND active',[$company]);
} elseif (in_array($mode,['suspend-account','restore-account'],true)) {
    privateCrmSql($db,'UPDATE public.crm_private_accounts SET status=? WHERE identity_id=?',
        [$mode==='suspend-account'?'suspended':'active',$peer['identity']]);
} elseif (in_array($mode,['revoke-membership','restore-membership'],true)) {
    privateCrmSql($db,'UPDATE public.crm_memberships SET status=?,version=version+1 WHERE global_identity_id=? AND organization_id=?',
        [$mode==='revoke-membership'?'revoked':'active',$peer['identity'],$peer['organization']]);
} elseif ($mode==='expire-session') {
    privateCrmSql($db,'UPDATE public.crm_auth_sessions SET expires_at=? WHERE identity_id=?',[time()-1,$peer['identity']]);
} elseif (in_array($mode,['corrupt-source','restore-source'],true)) {
    $payload=$main['original_payload'];
    if ($mode==='corrupt-source') {
        $wrong=json_decode($payload,true,16,JSON_THROW_ON_ERROR);
        $wrong['lead_id']=$fixtures['monitor']['events']['clientB'][0]['lead_id'];
        $payload=json_encode($wrong,JSON_THROW_ON_ERROR);
    }
    privateCrmSql($db,'UPDATE crm_auto_service.outbox SET payload=? WHERE company_id=? AND event_id=?',[$payload,$company,$main['event_id']]);
} elseif (in_array($mode,['corrupt-pointer','restore-pointer'],true)) {
    $sequence=$mode==='corrupt-pointer'?1:6;
    privateCrmSql($db,'UPDATE crm_auto_service.execution_projections p SET observation_hash=o.observation_hash FROM crm_auto_service.execution_observations o WHERE p.company_id=? AND p.event_id=? AND o.company_id=p.company_id AND o.event_id=p.event_id AND o.sequence=?',[$company,$main['event_id'],$sequence]);
} elseif ($mode==='readonly-context') {
    $app=new PDO('pgsql:host=postgres;port=5432;dbname=imagine_crm_private_homologation',
        'crm_homologation_app',(string)getenv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    monitorRequire($app->query('SELECT current_user')->fetchColumn()==='crm_homologation_app');
    foreach (['outbox','execution_read_grants','execution_observations','execution_projections'] as $table) {
        foreach (['SELECT','INSERT','UPDATE','DELETE','TRUNCATE'] as $right) {
            monitorRequire(monitorScalar($app,"SELECT has_table_privilege(current_user,?::text,?::text)",['crm_auto_service.'.$table,$right])===false);
        }
    }
    $app->beginTransaction();$app->exec('SET TRANSACTION READ ONLY');
    privateCrmSql($app,"SELECT set_config('app.tenant_id',?,true)",[$company]);
    $model=json_decode(monitorScalar($app,'SELECT crm_auto_service.read_execution_monitor(?::uuid)',[$main['event_id']]),true,64,JSON_THROW_ON_ERROR);
    monitorRequire($model['total_events']===56 && count($model['events'])===50
        && $model['selected']['history_count']===6
        && array_column($model['selected']['history'],'sequence')===[6,5,4,3,2,1]);
    $foreign=json_decode(monitorScalar($app,'SELECT crm_auto_service.read_execution_monitor(?::uuid)',
        [$fixtures['monitor']['events']['clientB'][0]['event_id']]),true,64,JSON_THROW_ON_ERROR);
    monitorRequire($foreign['selected']===null);
    $app->commit();
    monitorRequire(in_array($app->query("SELECT current_setting('app.tenant_id',true)")->fetchColumn(),[null,'',false],true));
    $denied=false;
    try { $app->query('SELECT crm_auto_service.read_execution_monitor(NULL)'); }
    catch (PDOException $error) { $denied=$error->getCode()==='P0001'; }
    monitorRequire($denied && !$app->inTransaction());
    $app->beginTransaction();$app->exec('SET TRANSACTION READ ONLY');
    privateCrmSql($app,"SELECT set_config('app.tenant_id',?,true)",[$peer['organization']]);
    $other=json_decode($app->query('SELECT crm_auto_service.read_execution_monitor(NULL)')->fetchColumn(),true,64,JSON_THROW_ON_ERROR);
    monitorRequire($other['total_events']===1 && $other['events'][0]['event_id']===$fixtures['monitor']['events']['clientB'][0]['event_id']);
    $app->rollBack();
    monitorRequire(in_array($app->query("SELECT current_setting('app.tenant_id',true)")->fetchColumn(),[null,'',false],true));
    echo "PRIVATE_EXECUTION_MONITOR_READONLY_CONTEXT_PASS\n";
} else {
    throw new RuntimeException('Unknown disposable monitor fixture operation.');
}
