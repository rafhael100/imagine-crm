<?php

declare(strict_types=1);

namespace Imagine\AutomationLab;

use App\Core\Automation\RuleDefinition;
use PDO;
use RuntimeException;
use Throwable;

/** Disposable PostgreSQL rehearsal. Cannot deliver notifications, assign leads or call any service. */
final class LabRuleStore
{
    public function __construct(private PDO $db) {}

    private function transaction(string $tenant, callable $work): mixed
    {
        if ($this->db->inTransaction()) throw new RuntimeException('NESTED_TRANSACTION_DENIED');
        if (!preg_match('/\Alab-[a-z0-9-]{1,48}\z/D', $tenant)) throw new RuntimeException('LAB_SCOPE_REQUIRED');
        $this->db->beginTransaction();
        try {
            $this->db->prepare("SELECT set_config('app.automation_tenant', ?, true)")->execute([$tenant]);
            // Serializes publication, quotas, cancellation and effects for this tenant only.
            $q = $this->db->prepare('SELECT * FROM automation_tenants WHERE tenant=? FOR UPDATE');
            $q->execute([$tenant]); $policy = $q->fetch(PDO::FETCH_ASSOC);
            if (!$policy) throw new RuntimeException('TENANT_NOT_FOUND');
            $result = $work($policy);
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            $this->db->rollBack(); throw $e;
        }
    }

    private function audit(string $tenant, string $event, string $reference, int $now): void
    {
        $this->db->prepare('INSERT INTO automation_audit(tenant,event,reference,occurred_at) VALUES(?,?,?,?)')
            ->execute([$tenant,$event,$reference,$now]);
    }

    public function publish(RuleDefinition $rule, int $expectedVersion, bool $active, int $now): void
    {
        $this->transaction($rule->tenant, function (array $policy) use ($rule,$expectedVersion,$active,$now): void {
            if (!in_array($policy['enabled'], [true, 1, '1', 't'], true)) throw new RuntimeException('REVOKED');
            $q=$this->db->prepare('SELECT version FROM automation_rules WHERE tenant=? AND rule_id=?');
            $q->execute([$rule->tenant,$rule->id]); $previous=$q->fetchColumn();
            if (($previous === false ? 0 : (int)$previous) !== $expectedVersion || $rule->version !== $expectedVersion+1) throw new RuntimeException('VERSION_CONFLICT');
            $q=$this->db->prepare('SELECT count(*) FROM automation_rules WHERE tenant=? AND active AND rule_id<>?');
            $q->execute([$rule->tenant,$rule->id]);
            if ($active && (int)$q->fetchColumn() >= (int)$policy['rule_limit']) throw new RuntimeException('RULE_LIMIT');
            $this->db->prepare('INSERT INTO automation_versions VALUES(?,?,?,?::jsonb)')->execute([$rule->tenant,$rule->id,$rule->version,$rule->json()]);
            $this->db->prepare('INSERT INTO automation_rules VALUES(?,?,?,?,?::jsonb) ON CONFLICT(tenant,rule_id) DO UPDATE SET version=excluded.version,active=excluded.active,definition=excluded.definition')
                ->execute([$rule->tenant,$rule->id,$rule->version,$active?'true':'false',$rule->json()]);
            $this->db->prepare("UPDATE automation_executions SET state='cancelled' WHERE tenant=? AND rule_id=? AND state='pending'")->execute([$rule->tenant,$rule->id]);
            $this->audit($rule->tenant,'rule.published',$rule->id,$now);
        });
    }

    public function pause(string $tenant, string $rule, int $now): void
    {
        $this->transaction($tenant,function () use($tenant,$rule,$now): void {
            $this->db->prepare('UPDATE automation_rules SET active=false WHERE tenant=? AND rule_id=?')->execute([$tenant,$rule]);
            $this->db->prepare("UPDATE automation_executions SET state='cancelled' WHERE tenant=? AND rule_id=? AND state='pending'")->execute([$tenant,$rule]);
            $this->audit($tenant,'rule.paused',$rule,$now);
        });
    }

    public function enqueue(string $tenant, string $ruleId, array $event, int $now): string
    {
        return $this->transaction($tenant,function(array $policy) use($tenant,$ruleId,$event,$now): string {
            if (!in_array($policy['enabled'], [true, 1, '1', 't'], true)) throw new RuntimeException('REVOKED');
            $q=$this->db->prepare('SELECT * FROM automation_rules WHERE tenant=? AND rule_id=?'); $q->execute([$tenant,$ruleId]); $row=$q->fetch(PDO::FETCH_ASSOC);
            if (!$row || !in_array($row['active'], [true, 1, '1', 't'], true)) throw new RuntimeException('RULE_INACTIVE');
            $rule=RuleDefinition::fromArray(json_decode($row['definition'],true,512,JSON_THROW_ON_ERROR));
            $command=$rule->plan($tenant,$event,$now); if ($command===null) return 'condition_not_matched';
            // Hash the full event with fixed field order; same ID with changed facts is a conflict.
            $canonical=[]; foreach(['id','occurred_at','resource','stage','tenant','trigger'] as $field) $canonical[$field]=$event[$field];
            $hash=hash('sha256',json_encode($canonical,JSON_THROW_ON_ERROR));
            $q=$this->db->prepare('SELECT payload_hash FROM automation_executions WHERE tenant=? AND execution_key=?'); $q->execute([$tenant,$command['idempotency_key']]); $previous=$q->fetchColumn();
            if($previous!==false) { if(!hash_equals($previous,$hash)) throw new RuntimeException('IDEMPOTENCY_CONFLICT'); return 'duplicate'; }
            $q=$this->db->prepare('SELECT count(*) FROM automation_executions WHERE tenant=? AND admitted_at>=? AND admitted_at<?');
            $day=intdiv($now,86400)*86400; $q->execute([$tenant,$day,$day+86400]);
            if ((int)$q->fetchColumn()>=(int)$policy['daily_limit']) throw new RuntimeException('DAILY_LIMIT');
            $this->db->prepare("INSERT INTO automation_executions(tenant,execution_key,event_id,rule_id,version,payload_hash,command,due_at,admitted_at,state) VALUES(?,?,?,?,?,?,?::jsonb,?,?,'pending')")
                ->execute([$tenant,$command['idempotency_key'],$event['id'],$ruleId,$rule->version,$hash,json_encode($command,JSON_THROW_ON_ERROR),$command['due_at'],$now]);
            $this->audit($tenant,'execution.admitted',$command['idempotency_key'],$now);
            return 'pending';
        });
    }

    /** Native durable effect fixture only, in the same transaction. No network callback or arbitrary code. */
    public function drainOne(string $tenant, int $now, bool $simulateTransientFailure=false): string
    {
        return $this->transaction($tenant,function(array $policy) use($tenant,$now,$simulateTransientFailure): string {
            if (!in_array($policy['enabled'], [true, 1, '1', 't'], true)) {
                $this->db->prepare("UPDATE automation_executions SET state='cancelled' WHERE tenant=? AND state='pending'")->execute([$tenant]);
                $this->audit($tenant,'execution.revoked','tenant',$now); return 'revoked';
            }
            $q=$this->db->prepare("SELECT e.*,r.active,r.version AS current_version FROM automation_executions e JOIN automation_rules r ON r.tenant=e.tenant AND r.rule_id=e.rule_id WHERE e.tenant=? AND e.state='pending' AND e.due_at<=? ORDER BY e.due_at,e.execution_key LIMIT 1");
            $q->execute([$tenant,$now]); $row=$q->fetch(PDO::FETCH_ASSOC); if(!$row) return 'empty';
            $state='completed';
            if(!in_array($row['active'], [true, 1, '1', 't'], true) || (int)$row['version']!==(int)$row['current_version']) $state='cancelled';
            elseif($simulateTransientFailure) $state=(int)$row['attempts']+1 >= (int)$policy['max_attempts'] ? 'failed':'pending';
            else $this->db->prepare('INSERT INTO automation_effects VALUES(?,?,?::jsonb) ON CONFLICT DO NOTHING')->execute([$tenant,$row['execution_key'],$row['command']]);
            $retry=$now+min(3600,30*(2**(int)$row['attempts']));
            $this->db->prepare('UPDATE automation_executions SET state=?,attempts=attempts+1,due_at=? WHERE tenant=? AND execution_key=?')->execute([$state,$retry,$tenant,$row['execution_key']]);
            $this->audit($tenant,'execution.'.$state,$row['execution_key'],$now);
            return $state;
        });
    }
}
