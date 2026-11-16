<?php

declare(strict_types=1);
require __DIR__.'/../../app/Core/Automation/RuleDefinition.php';
require __DIR__.'/LabRuleStore.php';
use App\Core\Automation\RuleDefinition;
use Imagine\AutomationLab\LabRuleStore;

$count=0;
function check(bool $ok, string $label): void { global $count; if(!$ok) throw new RuntimeException($label); ++$count; }
function denied(callable $f,string $label): void { try {$f();} catch (InvalidArgumentException|RuntimeException $e) {check(true,$label);return;} throw new RuntimeException('NOT_DENIED:'.$label); }
function rule(string $tenant='lab-alfa',int $version=1,string $template='followup_qualified',string $id='followup'): RuleDefinition {
 return RuleDefinition::fromArray(['tenant'=>$tenant,'id'=>$id,'version'=>$version,'template'=>$template,'delay_seconds'=>0,'recipient'=>'operator-one']);
}
$now=1800000000;
$event=['id'=>'event-one','occurred_at'=>$now,'resource'=>'lead-one','stage'=>'qualified','tenant'=>'lab-alfa','trigger'=>'lead.stage.changed'];
$r=rule(); $command=$r->plan('lab-alfa',$event,$now);
check($command['action']==='task.followup.create' && $command['ai_calls']===0,'native followup no AI');
check($r->plan('lab-alfa',array_replace($event,['stage'=>'new']),$now)===null,'conditions');
denied(fn()=>$r->plan('lab-beta',$event,$now),'cross tenant');
denied(fn()=>$r->plan('lab-alfa',array_replace($event,['occurred_at'=>$now+1]),$now),'future event');
foreach(['billing.charge','site.publish','approval.grant','ai.request','http.execute'] as $sensitive) denied(fn()=>rule(template:$sensitive),'sensitive blocked');
$value=json_decode($r->json(),true); $value['url']='https://example.invalid'; denied(fn()=>RuleDefinition::fromArray($value),'closed schema');
$lead=rule(template:'lead_distribution');
check($lead->plan('lab-alfa',array_replace($event,['trigger'=>'lead.created']),$now)['action']==='lead.assignment.request','new lead');
$overdue=rule(template:'overdue_notice');
check($overdue->plan('lab-alfa',array_replace($event,['trigger'=>'task.overdue']),$now)['action']==='notification.internal.create','overdue');
if(!getenv('AUTOMATION_PG_DSN')) { echo "RULE_UNIT_PASS checks=$count postgres=NOT_EXECUTED\n"; exit; }
$db=new PDO(getenv('AUTOMATION_PG_DSN'),getenv('PGUSER'),getenv('PGPASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$store=new LabRuleStore($db);
$store->publish($r,0,true,$now);
check($store->enqueue('lab-alfa','followup',$event,$now)==='pending','enqueue');
check($store->enqueue('lab-alfa','followup',$event,$now)==='duplicate','persistent dedupe');
denied(fn()=>$store->enqueue('lab-alfa','followup',array_replace($event,['resource'=>'lead-two']),$now),'changed payload');
check($store->drainOne('lab-alfa',$now)==='completed','atomic native effect');
check($store->drainOne('lab-alfa',$now)==='empty','no duplicate effect');
$second=array_replace($event,['id'=>'event-two']); $store->enqueue('lab-alfa','followup',$second,$now);
check($store->drainOne('lab-alfa',$now,true)==='pending','retry');
check($store->drainOne('lab-alfa',$now)==='empty','retry backoff');
check($store->drainOne('lab-alfa',$now+30,true)==='failed','retry bounded');
$store->enqueue('lab-alfa','followup',array_replace($event,['id'=>'event-three']),$now);
$store->pause('lab-alfa','followup',$now);
check($store->drainOne('lab-alfa',$now)==='empty','pause cancels schedule');
denied(fn()=>$store->enqueue('lab-alfa','followup',$event,$now),'paused duplicate reauthorized');
$store->publish(rule(version:2),1,true,$now);
denied(fn()=>$store->publish(rule(version:2),1,true,$now),'optimistic version');
$store->enqueue('lab-alfa','followup',array_replace($event,['id'=>'event-four']),$now);
$store->publish(rule(version:3),2,true,$now);
check($store->drainOne('lab-alfa',$now)==='empty','edit invalidates old jobs');
$store->publish(rule('lab-beta'),0,true,$now);
$beta=array_replace($event,['tenant'=>'lab-beta']);
check($store->enqueue('lab-beta','followup',$beta,$now)==='pending','second tenant');
$db->beginTransaction(); $db->exec("SELECT set_config('app.automation_tenant','lab-beta',true)");
$db->exec("UPDATE automation_tenants SET enabled=false WHERE tenant='lab-beta'"); $db->commit();
check($store->drainOne('lab-beta',$now)==='revoked','current entitlement revocation');
denied(fn()=>$store->enqueue('lab-beta','followup',$beta,$now),'revoked replay');
// RLS with a non-owner, non-superuser, non-bypass role. A GUC alone is not IAM.
$db->beginTransaction(); $db->exec("SELECT set_config('app.automation_tenant','lab-beta',true)");
check((int)$db->query("SELECT count(*) FROM automation_effects WHERE tenant='lab-alfa'")->fetchColumn()===0,'cross-company RLS'); $db->rollBack();
check($db->query("SELECT current_setting('app.automation_tenant',true)")->fetchColumn()==='','transaction context cleaned');
// Quotas are server policy, never browser/plan claims.
$store->publish(rule(template:'overdue_notice',id:'notices'),0,true,$now);
denied(fn()=>$store->publish(rule(template:'lead_distribution',id:'distribute'),0,true,$now),'rule plan limit');
$db->beginTransaction();$db->exec("SELECT set_config('app.automation_tenant','lab-alfa',true)");$db->exec("UPDATE automation_tenants SET daily_limit=0 WHERE tenant='lab-alfa'");$db->commit();
denied(fn()=>$store->enqueue('lab-alfa','followup',array_replace($event,['id'=>'event-five']),$now),'daily quota');
echo "AUTOMATION_PG_PASS checks=$count external_calls=0 ai_calls=0 operational_authorization=NOT_IMPLEMENTED\n";
