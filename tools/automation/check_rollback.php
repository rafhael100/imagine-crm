<?php
/** Real PostgreSQL privilege failure injection, only in the disposable CI schema. */
declare(strict_types=1);
require __DIR__.'/../../app/Core/Automation/RuleDefinition.php';require __DIR__.'/LabRuleStore.php';
$db=new PDO(getenv('AUTOMATION_PG_DSN'),getenv('PGUSER'),getenv('PGPASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$store=new Imagine\AutomationLab\LabRuleStore($db);$now=1800000000;
$event=['id'=>'event-rollback','occurred_at'=>$now,'resource'=>'lead-one','stage'=>'qualified','tenant'=>'lab-alfa','trigger'=>'lead.stage.changed'];
if(($argv[1]??'')==='restored') {
 if($store->enqueue('lab-alfa','followup',$event,$now)!=='pending' || $store->drainOne('lab-alfa',$now)!=='completed')throw new RuntimeException('RECOVERY_FAILED');
 echo "AUDIT_RECOVERY_PASS one_durable_effect=1\n";exit;
}
try {$store->enqueue('lab-alfa','followup',$event,$now);throw new RuntimeException('MISSING_AUDIT_WAS_ACCEPTED');}
catch(PDOException $e) {if($e->getCode()!=='42501')throw new RuntimeException('UNEXPECTED_FAILURE');}
if($db->inTransaction())throw new RuntimeException('TRANSACTION_LEAK');
$db->beginTransaction();$db->exec("SELECT set_config('app.automation_tenant','lab-alfa',true)");
$q=$db->query("SELECT count(*) FROM automation_executions WHERE tenant='lab-alfa' AND event_id='event-rollback'");
if((int)$q->fetchColumn()!==0)throw new RuntimeException('UNAUDITED_EXECUTION_SURVIVED');$db->rollBack();
echo "AUDIT_FAILURE_ROLLBACK_PASS pending_execution=0\n";
