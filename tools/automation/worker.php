<?php
// CI race: two independent PHP processes, same PostgreSQL tenant and event.
declare(strict_types=1);
require __DIR__.'/../../app/Core/Automation/RuleDefinition.php';
require __DIR__.'/LabRuleStore.php';
$db=new PDO(getenv('AUTOMATION_PG_DSN'),getenv('PGUSER'),getenv('PGPASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$store=new Imagine\AutomationLab\LabRuleStore($db,$argv[1]??'operator-one');
$now=1800000000;
$event=['id'=>'race-one','occurred_at'=>$now,'resource'=>'lead-one','stage'=>'qualified','tenant'=>'lab-race','trigger'=>'lead.stage.changed'];
echo $store->enqueue('lab-race','followup',$event,$now),"\n";
echo $store->drainOne('lab-race',$now),"\n";
