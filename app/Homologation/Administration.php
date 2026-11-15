<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Core\Identity\GuardPrincipalSource;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/** Private system metadata only. No implicit cross-tenant commercial data access. */
final readonly class Administration
{
    public function __construct(private GuardPrincipalSource $principals,
        private Connection $db, private Connection $iam) {}

    private function run(callable $effect): mixed
    {
        Environment::assertConnection($this->db,'operator');
        return $this->principals->withinCurrent(function ($principal) use ($effect) {
            $actor=$this->iam->table('public.crm_private_accounts')->where('identity_id',$principal->principalId)->first();
            abort_unless($actor && $actor->status==='active' && $actor->role==='admin',403);
            return $this->db->transaction(fn ()=>$effect($principal));
        },true);
    }

    public function overview(): array
    {
        return $this->run(function ($principal) {
            $accounts=$this->db->table('public.crm_private_accounts')->where('role','client')->orderByDesc('created_at')->limit(200)->get()->all();
            $trials=[]; $orders=[];
            foreach ($accounts as $account) {
                $org=$account->organization_id;
                if (array_key_exists($org,$trials)) continue;
                $this->db->selectOne("SELECT set_config('app.tenant_id',?,true)",[$org]);
                $trials[$org]=$this->db->table('public.crm_private_trials')->where('organization_id',$org)->first();
                foreach($this->db->table('public.crm_private_orders')->where('organization_id',$org)->orderByDesc('created_at')->limit(50)->get() as $row) $orders[]=$row;
            }
            $events=$this->db->table('public.crm_private_operator_events')->where('actor_id',$principal->principalId)
                ->orderByDesc('occurred_at')->limit(100)->get()->all();
            return compact('accounts','trials','orders','events');
        });
    }

    public function status(string $identity,string $status): void
    {
        abort_unless(Str::isUuid($identity) && in_array($status,['active','suspended'],true),422);
        $this->run(function ($principal) use ($identity,$status) {
            $q=$this->db->table('public.crm_private_accounts')->where('identity_id',$identity)->where('role','client');
            abort_unless((clone $q)->exists(),404);
            // Same order as IAM: credential -> session/business effect. Revocation
            // waits for an already verified operation, never cuts through it.
            $this->db->table('public.crm_auth_credentials')->where('identity_id',$identity)
                ->lockForUpdate()->first(['identity_id','lock_marker']);
            $target=(clone $q)->lockForUpdate()->first();
            abort_unless($target,404);
            if ($target->status===$status) return;
            $q->update(['status'=>$status]);
            // Resume never reactivates an old session. Fresh password/MFA is required.
            $this->db->table('public.crm_auth_sessions')->where('identity_id',$identity)->update(['state'=>'revoked']);
            $this->db->table('public.crm_private_operator_events')->insert(['id'=>(string)Str::uuid(),
                'actor_id'=>$principal->principalId,'subject_id'=>$identity,
                'action'=>$status==='active'?'resume':'suspend','occurred_at'=>time()]);
        });
    }
}
