<?php

declare(strict_types=1);
namespace App\Homologation;
use App\Core\Automation\RuleDefinition;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/** Private synthetic application adapter. All entry points are inside Workspace's verified IAM + membership transaction. */
final readonly class NativeFollowupEngine
{
 public function __construct(private Connection $db,private Connection $iam,private string $tenant,private string $actor) {
  if(!Environment::enabled() || $db->transactionLevel()!==1 || $iam->transactionLevel()!==1)throw new \LogicException('Verified private unit required');
  $db->statement("SET LOCAL lock_timeout = '2s'");
  $db->statement("SET LOCAL statement_timeout = '5s'");
  $iam->statement("SET LOCAL lock_timeout = '2s'");
 }
 private function runs(){return $this->db->table('public.crm_private_automation_runs')->where('organization_id',$this->tenant);}
 private function audit(string $id,string $action):void {
  $this->db->table('public.crm_private_events')->insert(['id'=>(string)Str::uuid(),'organization_id'=>$this->tenant,'actor_id'=>$this->actor,'object_id'=>$id,'action'=>$action,'occurred_at'=>time()]);
 }
 public function enqueue(string $lead,string $stage,int $leadVersion,AutomationSettings $settings):void {
  $template='followup_'.$stage;if(!in_array($template,$settings->rules,true))return;
  $now=time();
  $rule=RuleDefinition::fromArray(['tenant'=>'t-'.$this->tenant,'id'=>$template,'version'=>max(1,$settings->version),
   'template'=>$template,'delay_seconds'=>$settings->delaySeconds,'recipient'=>'p-'.$this->actor]);
  $event=['tenant'=>'t-'.$this->tenant,'id'=>'e-'.$lead.'-'.$leadVersion,'resource'=>'r-'.$lead,'stage'=>$stage,'trigger'=>'lead.stage.changed','occurred_at'=>$now];
  $command=$rule->plan('t-'.$this->tenant,$event,$now);if(!$command)return;
  $session=$this->iam->table('public.crm_auth_sessions')->where('identity_id',$this->actor)
    ->where('token_hash',hash('sha256',(string)session()->get('crm.auth.token')))->first();
  abort_unless($session && $session->state==='active',403);
  $existing=$this->runs()->where('execution_key',$command['idempotency_key'])->first();
  if($existing){abort_unless($existing->lead_id===$lead && $existing->template===$template,409);return;}
  abort_if($this->runs()->count()>=200,409,'Limite de segurança das execuções fictícias.');
  $id=(string)Str::uuid();
  $this->db->table('public.crm_private_automation_runs')->insert(['id'=>$id,'organization_id'=>$this->tenant,'actor_id'=>$this->actor,
   'authority_session_id'=>$session->id,'template'=>$template,'settings_version'=>$settings->version,'lead_id'=>$lead,'lead_version'=>$leadVersion,
   'execution_key'=>$command['idempotency_key'],'command'=>json_encode($command,JSON_THROW_ON_ERROR),'due_at'=>$command['due_at'],'created_at'=>$now]);
  $this->audit($id,'automation.queued');
 }
 public function cancel(string $id):void {
  $query=$this->runs()->where('id',$id);$row=(clone $query)->lockForUpdate()->first();abort_unless($row,404);
  if(in_array($row->state,['cancelled','completed','failed'],true))return;
  $query->update(['state'=>'cancelled']);$this->audit($id,'automation.cancelled');
 }
 public function invalidate():void {
  foreach($this->runs()->where('state','pending')->limit(200)->get() as $row)$this->cancel($row->id);
 }
 public function invalidateLead(string $lead):void {
  // A committed lead version change makes its earlier commands ineligible.
  // Reflect that immediately, in the same transaction, even before due_at.
  foreach($this->runs()->where('lead_id',$lead)->where('state','pending')->orderBy('id')->limit(200)->lockForUpdate()->get(['id']) as $row) {
   $this->cancel($row->id);
  }
 }
 public function process(AutomationSettings $settings):void {
  $now=time();
  $rows=$this->runs()->where('actor_id',$this->actor)->where('state','pending')->where('due_at','<=',$now)->orderBy('due_at')->orderBy('id')->limit(20)->lockForUpdate()->get();
  foreach($rows as $row){
   $source=$this->iam->table('public.crm_auth_sessions')->where('id',$row->authority_session_id)->where('identity_id',$this->actor)->lockForUpdate()->first();
   $lead=$this->db->table('public.crm_private_leads')->where('organization_id',$this->tenant)->where('id',$row->lead_id)->first();
   $command=json_decode($row->command,true,32,JSON_THROW_ON_ERROR);
   $active=$source && $source->state==='active' && (int)$source->expires_at>$now && (int)$source->absolute_expires_at>$now
    && $row->actor_id===$this->actor && (int)$row->settings_version===$settings->version && in_array($row->template,$settings->rules,true)
    && $lead && $lead->stage===substr($row->template,9) && (int)$lead->version===(int)$row->lead_version
    && ($command['tenant']??null)==='t-'.$this->tenant && ($command['recipient']??null)==='p-'.$this->actor
    && ($command['action']??null)==='task.followup.create' && ($command['ai_calls']??null)===0;
   if($active){
    $expectedRule=RuleDefinition::fromArray(['tenant'=>'t-'.$this->tenant,'id'=>$row->template,'version'=>max(1,$settings->version),
     'template'=>$row->template,'delay_seconds'=>$settings->delaySeconds,'recipient'=>'p-'.$this->actor]);
    $expected=$expectedRule->plan('t-'.$this->tenant,['tenant'=>'t-'.$this->tenant,'id'=>'e-'.$row->lead_id.'-'.$row->lead_version,
     'resource'=>'r-'.$row->lead_id,'stage'=>$lead->stage,'trigger'=>'lead.stage.changed','occurred_at'=>(int)$row->created_at],(int)$row->created_at);
    ksort($expected);ksort($command);
    $active=hash_equals(hash('sha256',json_encode($expected,JSON_THROW_ON_ERROR)),hash('sha256',json_encode($command,JSON_THROW_ON_ERROR)))
       && hash_equals($row->execution_key,$command['idempotency_key']);
   }
   if(!$active){$this->cancel($row->id);continue;}
   $tasks=$this->db->table('public.crm_private_tasks')->where('organization_id',$this->tenant);
   if((clone $tasks)->count()>=200){
    $attempts=(int)$row->attempts+1;$state=$attempts>=3?'failed':'pending';
    $this->runs()->where('id',$row->id)->update(['attempts'=>$attempts,'state'=>$state,'due_at'=>$now+30*(2**($attempts-1))]);
    $this->audit($row->id,$state==='failed'?'automation.failed':'automation.retry');continue;
   }
   // Task + run + audit share the native transaction. Deterministic task ID also fences duplicate delivery.
   if(!(clone $tasks)->where('id',$row->id)->exists())$this->db->table('public.crm_private_tasks')->insert(['id'=>$row->id,'organization_id'=>$this->tenant,
    'lead_id'=>$row->lead_id,'title'=>'TASK_TESTE_AUTO_'.strtoupper(substr($row->template,9)),'due_on'=>gmdate('Y-m-d',$now+86400)]);
   $this->runs()->where('id',$row->id)->update(['state'=>'completed','attempts'=>(int)$row->attempts+1]);
   $this->audit($row->id,'automation.task_created');
  }
 }
}
