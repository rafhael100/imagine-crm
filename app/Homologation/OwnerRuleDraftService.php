<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Core\Identity\DatabaseSessionAuthority;
use App\Homologation\S2S\AgentsOwnerDraftClient;
use App\Homologation\S2S\OwnerDraftContract;
use App\Homologation\S2S\StrictJson;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

/** Only invoked inside Workspace's held owner + IAM + membership unit. */
final readonly class OwnerRuleDraftService
{
    public function __construct(private Connection $db,private Connection $iam,private string $company,private string $actor,
        private ?int $committedDeadline=null)
    {
        if (!Environment::ownerDraftsEnabled() || $db->transactionLevel()!==1 || $iam->transactionLevel()!==1) {
            throw new \LogicException('Verified owner draft unit required');
        }
        $db->statement("SET LOCAL lock_timeout = '2s'");
        $db->statement("SET LOCAL statement_timeout = '5s'");
        $iam->statement("SET LOCAL lock_timeout = '2s'");
        $safe=$db->selectOne("SELECT count(*) AS n FROM pg_class c WHERE c.oid IN
            ('public.crm_private_owner_draft_intents'::regclass,'public.crm_private_owner_draft_grants'::regclass)
            AND c.relrowsecurity AND c.relforcerowsecurity AND NOT pg_has_role(current_user,c.relowner,'MEMBER')");
        if (!$safe || (int)$safe->n!==2) throw new \LogicException('Unsafe owner draft storage');
    }

    private function intents()
    {
        return $this->db->table('public.crm_private_owner_draft_intents')->where('organization_id',$this->company);
    }

    private function audit(string $draft,string $action): void
    {
        $this->db->table('public.crm_private_events')->insert(['id'=>(string)Str::uuid(),'organization_id'=>$this->company,
            'actor_id'=>$this->actor,'object_id'=>$draft,'action'=>$action,'occurred_at'=>time()]);
    }

    /** No nested withinCurrent: re-read the registry under locks already owned by this unit. */
    private function humanDeadline(): int
    {
        $authority=app(DatabaseSessionAuthority::class);
        if ($authority->db!==$this->iam) throw new \LogicException('Owner draft IAM connection mismatch');
        $credential=$this->iam->table('public.crm_auth_credentials')->where('identity_id',$this->actor)->first();
        $session=$authority->sessionRow('crm.auth.token');
        $now=time();
        abort_unless($credential && $authority->validCredential($credential) && $session
            && $session->identity_id===$this->actor && $session->state==='active' && $session->security_hold===null
            && $authority->usable($session) && (int)$session->credential_version===(int)$credential->version
            && hash_equals($session->binding,hash('sha256',session()->getId())) && (int)$session->mfa_verified_at>0
            && (int)$session->created_at<=$now && (int)$session->expires_at>$now && (int)$session->absolute_expires_at>$now,403);
        return min((int)$session->expires_at,(int)$session->absolute_expires_at,$this->committedDeadline??4102444800);
    }

    /** Commit this IAM refresh before a later unit may attest a remote operation. */
    public function prepareAuthorization(): int
    {
        return $this->humanDeadline();
    }

    private function grant(string $operation): stdClass
    {
        $grant=$this->db->table('public.crm_private_owner_draft_grants')->where('organization_id',$this->company)
            ->where('operation',$operation)->sharedLock()->first();
        abort_unless($grant && $grant->active && (int)$grant->revision>0 && (int)$grant->expires_at>time(),403);
        return $grant;
    }

    private function window(stdClass $grant,bool $write=false,?int $causal=null): int
    {
        $deadline=min($this->humanDeadline(),(int)$grant->expires_at,$causal??4102444800);
        if ($write) {
            $trial=$this->db->table('public.crm_private_trials')->where('organization_id',$this->company)->first();
            abort_unless($trial,403);$deadline=min($deadline,(int)$trial->expires_at);
        }
        abort_if($deadline<=time(),403);
        return $deadline;
    }

    private function recheck(int $deadline): void
    {
        abort_if(min($deadline,$this->humanDeadline())<=time(),403);
    }

    /** Source session is used only here; never persisted again or placed on the wire. */
    private function source(string $id,string $draft): array
    {
        $preferences=$this->db->table('public.crm_private_preferences')->where('organization_id',$this->company)->lockForUpdate()->first();
        $run=$this->db->table('public.crm_private_automation_runs')->where('organization_id',$this->company)->where('id',$id)->lockForUpdate()->first();
        abort_unless($run,404);
        abort_unless($run->actor_id===$this->actor && $run->state==='pending' && $run->template==='followup_qualified',409);
        $lead=$this->db->table('public.crm_private_leads')->where('organization_id',$this->company)->where('id',$run->lead_id)->lockForUpdate()->first();
        abort_unless($lead,409);
        $authority=app(DatabaseSessionAuthority::class);
        $causal=$this->iam->table('public.crm_auth_sessions')->where('id',$run->authority_session_id)->where('identity_id',$this->actor)->lockForUpdate()->first();
        $credential=$this->iam->table('public.crm_auth_credentials')->where('identity_id',$this->actor)->first();
        $now=time();
        abort_unless($causal && $credential && $causal->state==='active' && $causal->security_hold===null
            && $authority->usable($causal) && (int)$causal->credential_version===(int)$credential->version
            && (int)$causal->mfa_verified_at>0 && (int)$causal->created_at<=$now
            && (int)$causal->expires_at>$now && (int)$causal->absolute_expires_at>$now,409);
        try {
            $business=OwnerRuleDraftSource::business($this->company,$this->actor,$draft,$run,$lead,
                AutomationSettings::read($preferences->automation_rules??'[]'));
        } catch (\InvalidArgumentException|\JsonException) { abort(409); }
        abort_if($business['source_created_at']>$now,409);
        return [$business,min((int)$causal->expires_at,(int)$causal->absolute_expires_at)];
    }

    /** This unit MUST commit before any remote call. Existing intents are never rewritten. */
    public function stage(string $source): array
    {
        $grant=$this->grant(OwnerDraftContract::CREATE);
        $existing=$this->intents()->where('source_run_id',$source)->lockForUpdate()->first();
        if ($existing) {
            $this->stored($existing);$deadline=$this->window($grant,true);$this->recheck($deadline);
            return ['draft_ref'=>$existing->draft_ref,'created'=>false,'deadline'=>$deadline];
        }
        $draft=(string)Str::uuid();
        [$business,$causal]=$this->source($source,$draft);
        $deadline=$this->window($grant,true,$causal);
        abort_if($this->intents()->count()>=200,409);
        $this->db->table('public.crm_private_owner_draft_intents')->insert([
            'draft_ref'=>$draft,'organization_id'=>$this->company,'source_run_id'=>$source,'creator_id'=>$this->actor,
            'business'=>OwnerDraftContract::canonical($business),'business_hash'=>OwnerDraftContract::digest($business),
            'created_at'=>time(),
        ]);
        $this->audit($draft,'agents.draft.intent_created');
        $this->recheck($deadline);
        return ['draft_ref'=>$draft,'created'=>true,'deadline'=>$deadline];
    }

    private function stored(stdClass $intent): array
    {
        $business=OwnerDraftContract::business(StrictJson::decodeObject($intent->business,4096,8));
        if ($business['company_id']!==$this->company || $business['draft_ref']!==$intent->draft_ref
            || $business['source_run_id']!==$intent->source_run_id || $business['creator_id']!==$intent->creator_id
            || !hash_equals($intent->business_hash,OwnerDraftContract::digest($business))) {
            throw new \LogicException('Owner draft intent integrity failed');
        }
        return $business;
    }

    private function intent(string $draft): stdClass
    {
        $intent=$this->intents()->where('draft_ref',$draft)->lockForUpdate()->first();
        abort_unless($intent,404);
        return $intent;
    }

    public function references(): array
    {
        $deadline=$this->humanDeadline();
        $grant=$this->db->table('public.crm_private_owner_draft_grants')->where('organization_id',$this->company)
            ->where('operation',OwnerDraftContract::READ)->sharedLock()->first();
        // An unavailable optional module must not disclose its local references.
        if (!$grant || !$grant->active || (int)$grant->revision<1 || (int)$grant->expires_at<=time()) return [];
        $deadline=min($deadline,(int)$grant->expires_at);
        $references=$this->intents()->orderBy('draft_ref')->limit(200)->pluck('draft_ref','source_run_id')->all();
        $this->recheck($deadline);
        return $references;
    }

    private function confirmed(stdClass $intent,array $business): array
    {
        if ($intent->state!=='confirmed') {
            $this->intents()->where('draft_ref',$intent->draft_ref)->update(['state'=>'confirmed','confirmed_at'=>time()]);
            $this->audit($intent->draft_ref,'agents.draft.confirmed');
        }
        return ['draft_ref'=>$intent->draft_ref,'state'=>'confirmed','business'=>$business,'available'=>true];
    }

    /** A valid ABSENT is meaningful; any transport/auth failure keeps uncertainty. */
    private function lookup(stdClass $intent,array $business): array
    {
        if ($this->committedDeadline===null) throw new \LogicException('Committed authorization window required');
        $grant=$this->grant(OwnerDraftContract::READ);$deadline=$this->window($grant);
        try { $reply=(new AgentsOwnerDraftClient())->exchange($business,$this->actor,OwnerDraftContract::READ,$grant->key_id,$deadline); }
        catch (RuntimeException) {
            $this->recheck($deadline);
            return ['draft_ref'=>$intent->draft_ref,'state'=>'pending','business'=>null,'available'=>false];
        }
        $this->recheck($deadline);
        if ($reply['status']==='DRAFT') {
            $result=$this->confirmed($intent,$reply['business']);
            $this->audit($intent->draft_ref,'agents.draft.read');
            $this->recheck($deadline);
            return $result;
        }
        // A previously confirmed receipt cannot be silently recreated after data loss.
        abort_if($intent->state==='confirmed',409);
        $this->audit($intent->draft_ref,'agents.draft.pending_read');
        $this->recheck($deadline);
        return ['draft_ref'=>$intent->draft_ref,'state'=>'pending','business'=>null,'available'=>true];
    }

    public function read(string $draft): array
    {
        $intent=$this->intent($draft);
        return $this->lookup($intent,$this->stored($intent));
    }

    public function deliver(string $draft,bool $reconcile): array
    {
        if ($this->committedDeadline===null) throw new \LogicException('Committed authorization window required');
        $intent=$this->intent($draft);$business=$this->stored($intent);
        if ($reconcile) {
            $result=$this->lookup($intent,$business);
            if ($result['state']==='confirmed' || !$result['available']) return $result;
        }
        $grant=$this->grant(OwnerDraftContract::CREATE);
        [$current,$causal]=$this->source($intent->source_run_id,$draft);
        abort_unless(OwnerDraftContract::canonical($current)===OwnerDraftContract::canonical($business),409);
        $deadline=$this->window($grant,true,$causal);
        try { $reply=(new AgentsOwnerDraftClient())->exchange($business,$this->actor,OwnerDraftContract::CREATE,$grant->key_id,$deadline); }
        catch (RuntimeException) {
            // Intention already committed. The remote may also have committed.
            $this->recheck($deadline);
            $this->audit($draft,'agents.draft.pending');
            $this->recheck($deadline);
            return ['draft_ref'=>$draft,'state'=>'pending','business'=>null,'available'=>false];
        }
        $this->recheck($deadline);
        $result=$this->confirmed($intent,$reply['business']);
        $this->recheck($deadline);
        return $result;
    }
}
