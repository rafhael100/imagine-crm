<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Scoped authorization for synthetic staging tables, not a production ALLOW policy. */
final readonly class Workspace
{
    public const STAGES = ['new','qualified','proposal','won','lost'];
    private const NEXT = ['new'=>['qualified','lost'], 'qualified'=>['proposal','lost'],
        'proposal'=>['won','lost'], 'won'=>[], 'lost'=>[]];

    public function __construct(private GuardPrincipalSource $principals,
        private Connection $db, private Connection $iam) {}

    private function run(string $action, callable $effect, bool $write = false): mixed
    {
        Environment::assertConnection($this->db, 'app');
        return $this->principals->withinCurrent(function (PrincipalReference $principal) use ($action,$effect,$write) {
            $account = $this->iam->table('public.crm_private_accounts')
                ->where('identity_id',$principal->principalId)->first();
            abort_unless($account && $account->status === 'active' && $account->role === 'client',403);
            if ($account->team_role!=='owner') {
                abort_unless(in_array($action,['view','leads.create','leads.update','tasks.create','tasks.complete','automation.execute'],true),403);
            }
            // Tenant is resolved exclusively from this verified identity's binding.
            return (new PostgresActiveMembershipStore($this->db))->within($principal,$account->organization_id,
                function () use ($account,$principal,$action,$effect,$write) {
                    $trial = $this->db->table('public.crm_private_trials')
                        ->where('organization_id',$account->organization_id)->first();
                    abort_unless($trial,403);
                    if ($write) abort_if((int)$trial->expires_at <= time(),403,'Trial fictício encerrado.');
                    if ($write || $action==='automation.export' || str_starts_with($action,'orders.')) {
                        $this->db->selectOne('SELECT pg_advisory_xact_lock(hashtextextended(?::text,0))',
                            ['crm_private_quota|'.$account->organization_id]);
                    }
                    return $effect($account,$trial,$principal);
                });
        });
    }

    private function table(string $name, string $organization)
    {
        return $this->db->table('public.crm_private_'.$name)->where('organization_id',$organization);
    }

    public function overview(): array
    {
        return $this->run('view',function ($account,$trial) {
            $org = $account->organization_id;
            $leads = $this->table('leads',$org)->orderBy('created_at')->orderBy('id')->get()->all();
            $tasks = $this->table('tasks',$org)->orderBy('due_on')->get()->all();
            $events = $this->table('events',$org)->orderByDesc('occurred_at')->limit(50)->get()->all();
            $orders = $this->table('orders',$org)->orderByDesc('created_at')->get()->all();
            $preferences = $this->table('preferences',$org)->first();
            $funnels=$this->table('funnels',$org)->orderBy('created_at')->get()->all();
            $invitations=$this->table('invitations',$org)->orderByDesc('created_at')->limit(20)->get(['id','state','expires_at'])->all();
            $automation = AutomationSettings::read($preferences->automation_rules ?? '[]');
            $rules = $automation->rules;
            $automationRuns=$this->table('automation_runs',$org)->orderByDesc('created_at')->limit(50)->get()->all();
            $team = $this->iam->table('public.crm_private_accounts')->where('organization_id',$org)
                ->get(['identity_id','status','role','team_role'])->all();
            return compact('account','trial','leads','tasks','events','orders','preferences','rules','team','funnels','invitations','automation','automationRuns');
        });
    }

    private static function label(mixed $value, string $prefix): string
    {
        if (!is_string($value) || preg_match('/\A'.$prefix.'[A-Z0-9_]{1,40}\z/D',$value)!==1) {
            throw ValidationException::withMessages(['title'=>'Use apenas o identificador fictício indicado no campo.']);
        }
        return $value;
    }

    private function event($account,$principal,string $object,string $action): void
    {
        $this->db->table('public.crm_private_events')->insert(['id'=>(string)Str::uuid(),
            'organization_id'=>$account->organization_id,'actor_id'=>$principal->principalId,
            'object_id'=>$object,'action'=>$action,'occurred_at'=>time()]);
    }

    public function createLeads(array $rows,bool $historicalImport=false): void
    {
        if (count($rows)<1 || count($rows)>20) {
            throw ValidationException::withMessages(['csv'=>'O arquivo deve ter de 1 a 20 linhas fictícias.']);
        }
        $validated=[];
        foreach ($rows as $row) {
            $title=self::label($row['title']??null,'LEAD_TESTE_');
            $value=filter_var($row['value_cents']??0,FILTER_VALIDATE_INT);
            if ($value===false || $value<0 || $value>100000000) {
                throw ValidationException::withMessages(['value_cents'=>'Valor inválido.']);
            }
            $funnel=$row['funnel']??null;
            abort_unless($funnel===null || (is_string($funnel) && Str::isUuid($funnel)),422);
            $stage=$historicalImport?($row['stage']??'new'):'new';
            abort_unless(in_array($stage,['new','qualified','proposal','won','lost'],true),422);
            $validated[]=[$title,$value,$funnel,$stage];
        }
        $this->run('leads.create',function ($account,$trial,$principal) use ($validated,$historicalImport) {
            abort_if($this->table('leads',$account->organization_id)->count()+count($validated)>20,409,'Limite de 20 leads do trial.');
            foreach ($validated as [$title,$value,$funnel,$stage]) {
                $funnel=$funnel??$this->table('funnels',$account->organization_id)->orderBy('created_at')->value('id');
                abort_unless($funnel && $this->table('funnels',$account->organization_id)->where('id',$funnel)->exists(),404);
                $id=(string)Str::uuid();
                $this->db->table('public.crm_private_leads')->insert(['id'=>$id,'organization_id'=>$account->organization_id,
                    'title'=>$title,'value_cents'=>$value,'stage'=>$stage,'funnel_id'=>$funnel,'created_at'=>time()]);
                $this->event($account,$principal,$id,$historicalImport?'lead.imported':'lead.created');
            }
        },true);
    }

    public function updateLead(string $id,int $version,array $input): void
    {
        abort_unless(Str::isUuid($id) && $version>0,422);
        $this->run('leads.update',function ($account,$trial,$principal) use ($id,$version,$input) {
            $query=$this->table('leads',$account->organization_id)->where('id',$id);
            $row=(clone $query)->lockForUpdate()->first();
            abort_unless($row,404);
            abort_unless((int)$row->version===$version,409,'Registro atualizado em outra aba. Recarregue.');
            $values=['version'=>$version+1];
            if (isset($input['title'])) $values['title']=self::label($input['title'],'LEAD_TESTE_');
            if (isset($input['stage'])) {
                abort_unless(in_array($input['stage'],self::NEXT[$row->stage]??[],true),422,'Transição de etapa inválida.');
                $values['stage']=$input['stage'];
            }
            $query->update($values);
            $this->event($account,$principal,$id,isset($values['stage'])?'lead.moved':'lead.updated');
            if (isset($values['stage']) && in_array($values['stage'],['qualified','proposal'],true)) {
                $settings=AutomationSettings::read($this->table('preferences',$account->organization_id)->value('automation_rules')??'[]');
                $engine=new NativeFollowupEngine($this->db,$this->iam,$account->organization_id,$principal->principalId);
                $engine->enqueue($id,$values['stage'],$version+1,$settings);
                $engine->process($settings);
            }
        },true);
    }

    public function createTask(string $lead,mixed $title,string $due): void
    {
        $title=self::label($title,'TASK_TESTE_');
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$due);
        abort_unless(Str::isUuid($lead) && $date && $date->format('Y-m-d')===$due,422);
        $this->run('tasks.create',function ($account,$trial,$principal) use ($lead,$title,$due) {
            abort_unless($this->table('leads',$account->organization_id)->where('id',$lead)->exists(),404);
            abort_if($this->table('tasks',$account->organization_id)->count()>=200,409,'Limite de segurança das tarefas fictícias.');
            $id=(string)Str::uuid();
            $this->db->table('public.crm_private_tasks')->insert(['id'=>$id,'organization_id'=>$account->organization_id,
                'lead_id'=>$lead,'title'=>$title,'due_on'=>$due]);
            $this->event($account,$principal,$id,'task.created');
        },true);
    }

    public function resolveTask(string $id,int $version): void
    {
        abort_unless(Str::isUuid($id) && $version>0,422);
        $this->run('tasks.complete',function ($account,$trial,$principal) use ($id,$version) {
            $query=$this->table('tasks',$account->organization_id)->where('id',$id);
            $row=(clone $query)->lockForUpdate()->first();
            abort_unless($row,404);
            abort_unless($row->state==='todo' && (int)$row->version===$version,409);
            $query->update(['state'=>'done','version'=>$version+1]);
            $this->event($account,$principal,$id,'task.completed');
        },true);
    }

    public function preferences(string $language,array $rules,int $expectedVersion,int $delaySeconds=0): void
    {
        abort_unless(in_array($language,['pt','en','es'],true)
            && count($rules)<=2 && count(array_unique($rules))===count($rules)
            && array_diff($rules,['followup_qualified','followup_proposal'])===[],422);
        $this->run('preferences.update',function ($account,$trial,$principal) use ($language,$rules,$expectedVersion,$delaySeconds) {
            $query=$this->table('preferences',$account->organization_id);
            $row=(clone $query)->lockForUpdate()->first();
            abort_unless($row,409);
            $current=AutomationSettings::read($row->automation_rules ?? '[]');
            abort_unless($current->version===$expectedVersion && $current->version<20,409,'As automações mudaram ou atingiram o limite de revisões da homologação.');
            $next=$current->revise($expectedVersion,array_values($rules),$principal->principalId,time(),$delaySeconds);
            $query->update(['language'=>$language,'automation_rules'=>$next->json()]);
            (new NativeFollowupEngine($this->db,$this->iam,$account->organization_id,$principal->principalId))->invalidate();
            $this->event($account,$principal,$account->organization_id,'automation.settings.v'.$next->version);
            $this->event($account,$principal,$account->organization_id,'preferences.updated');
        },true);
    }

    public function automationState(): array
    {
        return $this->run('automation.export',function($account,$trial,$principal){
            $settings=AutomationSettings::read($this->table('preferences',$account->organization_id)->value('automation_rules')??'[]');
            $counts=['pending'=>0,'completed'=>0,'cancelled'=>0,'failed'=>0];
            foreach($this->table('automation_runs',$account->organization_id)->selectRaw('state,count(*) AS total')->groupBy('state')->get() as $row)$counts[$row->state]=(int)$row->total;
            $events=$this->table('events',$account->organization_id)->where('action','like','automation.%')->orderByDesc('occurred_at')->orderByDesc('id')->limit(20)->get(['action','occurred_at']);
            return ['schema'=>'CRM_AUTOMATION_STATE_V1','source_kind'=>'synthetic_private_database','company_id'=>$account->organization_id,
                'observed_at'=>time(),'settings_version'=>$settings->version,'counts'=>$counts,
                'events'=>array_map(fn($event)=>['action'=>$event->action,'at'=>(int)$event->occurred_at],$events->all()),
                'ai_provider_calls'=>0,'operational_authorization'=>false];
        });
    }

    public function processAutomations(): void
    {
        $this->run('automation.execute',function($account,$trial,$principal){
            $settings=AutomationSettings::read($this->table('preferences',$account->organization_id)->value('automation_rules')??'[]');
            (new NativeFollowupEngine($this->db,$this->iam,$account->organization_id,$principal->principalId))->process($settings);
        },true);
    }
    public function cancelAutomation(string $id): void
    {
        abort_unless(Str::isUuid($id),422);
        $this->run('automation.cancel',function($account,$trial,$principal) use($id){
            (new NativeFollowupEngine($this->db,$this->iam,$account->organization_id,$principal->principalId))->cancel($id);
        },true);
    }

    public function order(string $plan,string $method,string $key): string
    {
        abort_unless(in_array($plan,['basic','essential','professional','enterprise'],true)
            && in_array($method,['pix','card','boleto'],true) && Str::isUuid($key),422);
        return $this->run('orders.create',function ($account,$trial,$principal) use ($plan,$method,$key) {
            $existing=$this->table('orders',$account->organization_id)->where('idempotency_key',$key)->first();
            if ($existing) { abort_unless($existing->plan===$plan && $existing->payment_method===$method,409); return $existing->id; }
            abort_if($this->table('orders',$account->organization_id)->count()>=50,409);
            $id=(string)Str::uuid();
            $this->db->table('public.crm_private_orders')->insert(['id'=>$id,'organization_id'=>$account->organization_id,
                'plan'=>$plan,'payment_method'=>$method,'idempotency_key'=>$key,'created_at'=>time()]);
            $this->event($account,$principal,$id,'order.simulated');
            return $id;
        });
    }

    public function simulatePayment(string $id,string $state,int $version): void
    {
        abort_unless(Str::isUuid($id) && in_array($state,['approved','refused','cancelled'],true) && $version>0,422);
        $this->run('orders.simulate',function ($account,$trial,$principal) use ($id,$state,$version) {
            $query=$this->table('orders',$account->organization_id)->where('id',$id);
            $row=(clone $query)->lockForUpdate()->first();
            abort_unless($row,404);
            if ($row->state===$state) return;
            abort_unless($row->state==='pending' && (int)$row->version===$version,409);
            $query->update(['state'=>$state,'version'=>$version+1]);
            $this->event($account,$principal,$id,'payment.simulated_'.$state);
            // Deliberately no entitlement/provisioning: a simulated payment is not a real right.
        });
    }

    public function createFunnel(mixed $title): void
    {
        $title=self::label($title,'FUNIL_TESTE_');
        $this->run('funnels.create',function ($account,$trial,$principal) use ($title) {
            abort_if($this->table('funnels',$account->organization_id)->count()>=2,409,'Limite de dois funis do trial.');
            $id=(string)Str::uuid();
            $this->db->table('public.crm_private_funnels')->insert(['id'=>$id,'organization_id'=>$account->organization_id,
                'title'=>$title,'created_at'=>time()]);
            $this->event($account,$principal,$id,'funnel.created');
        },true);
    }

    public function invite(): string
    {
        return $this->run('team.invite',function ($account,$trial,$principal) {
            $members=$this->iam->table('public.crm_private_accounts')->where('organization_id',$account->organization_id)->count();
            $pending=$this->table('invitations',$account->organization_id)->where('state','pending')->where('expires_at','>',time())->count();
            abort_if($members+$pending>=2,409,'Limite de dois usuários, incluindo convites pendentes.');
            $id=(string)Str::uuid();$token=InvitationToken::issue($account->organization_id,$id);
            $this->db->table('public.crm_private_invitations')->insert(['id'=>$id,'organization_id'=>$account->organization_id,
                'token_hash'=>hash('sha256',$token),'created_at'=>time(),'expires_at'=>time()+86400]);
            $this->event($account,$principal,$id,'team.invited');
            return $token;
        },true);
    }
}
