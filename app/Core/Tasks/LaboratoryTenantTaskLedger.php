<?php

declare(strict_types=1);

namespace App\Core\Tasks;

use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * CRM-TASKS-PG01: disposable PG16 follow-up tasks with immutable lead/tenant
 * and append-only audit. CI only. PrincipalReference is NOT authentication.
 *
 * Not an operational service, HTTP controller, customer task or assignment
 * mechanism. DenyByDefaultAuthorizer remains unchanged.
 */
final readonly class LaboratoryTenantTaskLedger
{
    public function __construct(private Connection $db) {}

    /**
     * @return array{status_code:string,can_contact:false,can_notify:false,can_activate:false}
     */
    public function createSynthetic(
        PrincipalReference $principal,
        string $organization,
        string $taskId,
        string $leadId,
        string $syntheticSubject,
        string $dueOn,
    ): array {
        $this->validate($organization, $taskId);
        if (!Str::isUuid($leadId)
            || preg_match('/\ATASK_[A-Z0-9_]{3,40}\z/D', $syntheticSubject) !== 1
            || !self::validDate($dueOn)) {
            throw new InvalidArgumentException('Tarefa sintetica invalida.');
        }

        return $this->within($principal, $organization, function (MembershipSnapshot $member)
            use ($taskId, $leadId, $syntheticSubject, $dueOn): array {
            try {
                // No enumeration of a missing, archived or foreign-tenant lead.
                $exists = $this->db->table('public.crm_lab_leads')
                    ->where('organization_id', $member->tenantId)
                    ->where('id', strtolower($leadId))
                    ->where('archived', false)->exists();
                if (!$exists) {
                    return self::result('synthetic_task_denied');
                }

                $this->db->table('public.crm_lab_followup_tasks')->insert([
                    'id' => strtolower($taskId),
                    'organization_id' => $member->tenantId,
                    'lead_id' => strtolower($leadId),
                    'synthetic_subject' => $syntheticSubject,
                    'due_on' => $dueOn,
                ]);
                $this->appendEvent($taskId, $member->tenantId, 1, 'created');
                return self::result('synthetic_task_created');
            } catch (Throwable) {
                throw new RuntimeException('Tarefa sintetica indisponivel.');
            }
        });
    }

    /**
     * @return array{status_code:string,can_contact:false,can_notify:false,can_activate:false}
     */
    public function resolveSynthetic(
        PrincipalReference $principal,
        string $organization,
        string $taskId,
        string $next,
        int $expectedVersion,
    ): array {
        $this->validate($organization, $taskId);
        if (!in_array($next, ['done', 'cancelled'], true) || $expectedVersion < 1) {
            throw new InvalidArgumentException('Transicao sintetica de tarefa invalida.');
        }

        return $this->within($principal, $organization, function (MembershipSnapshot $member)
            use ($taskId, $next, $expectedVersion): array {
            try {
                $row = $this->db->table('public.crm_lab_followup_tasks')
                    ->where('id', strtolower($taskId))
                    ->where('organization_id', $member->tenantId)
                    ->lockForUpdate()->first(['status', 'version']);
                if (!$row || $row->status !== 'todo' || (int) $row->version !== $expectedVersion) {
                    return self::result('synthetic_task_not_changed');
                }

                $count = $this->db->table('public.crm_lab_followup_tasks')
                    ->where('id', strtolower($taskId))
                    ->where('organization_id', $member->tenantId)
                    ->where('status', 'todo')
                    ->where('version', $expectedVersion)
                    ->update(['status' => $next, 'version' => $expectedVersion + 1]);

                if ($count === 1) {
                    $this->appendEvent($taskId, $member->tenantId,
                        $expectedVersion + 1, $next === 'done' ? 'completed' : 'cancelled');
                }
                return self::result($count === 1
                    ? 'synthetic_task_resolved' : 'synthetic_task_not_changed');
            } catch (Throwable) {
                throw new RuntimeException('Transicao de tarefa sintetica indisponivel.');
            }
        });
    }

    /**
     * Bounded synthetic worklist, not an operational export or API.
     * @return list<array{task_id:string,lead_id:string,subject:string,due_on:string,version:int}>
     */
    public function pendingSynthetic(PrincipalReference $principal, string $organization): array
    {
        $this->validate($organization, null);
        return $this->within($principal, $organization, function (MembershipSnapshot $member): array {
            try {
                return $this->db->table('public.crm_lab_followup_tasks')
                    ->where('organization_id', $member->tenantId)
                    ->where('status', 'todo')
                    ->orderBy('due_on')->orderBy('id')->limit(100)
                    ->get(['id', 'lead_id', 'synthetic_subject', 'due_on', 'version'])
                    ->map(static fn ($row) => [
                        'task_id' => (string) $row->id,
                        'lead_id' => (string) $row->lead_id,
                        'subject' => (string) $row->synthetic_subject,
                        'due_on' => substr((string) $row->due_on, 0, 10),
                        'version' => (int) $row->version,
                    ])->all();
            } catch (Throwable) {
                throw new RuntimeException('Consulta de tarefas sinteticas indisponivel.');
            }
        });
    }

    private function within(PrincipalReference $principal, string $org, callable $effect): mixed
    {
        $this->guard();
        return (new PostgresActiveMembershipStore($this->db))
            ->within($principal, strtolower($org), $effect);
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('CI') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_TASKS_LAB') !== 'synthetic-only'
            || $this->db->getDriverName() !== 'pgsql'
            || $this->db->transactionLevel() !== 0
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new LogicException('Tarefas exigem PostgreSQL descartavel do CI.');
        }
        try {
            $role = $this->db->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new LogicException('Role de tarefa indisponivel.');
        }
        if (($role->role_name ?? null) !== 'crm_app_ci') {
            throw new LogicException('Role de tarefa nao autorizada.');
        }
    }

    private static function validate(string $org, ?string $taskId): void
    {
        if (!Str::isUuid($org)
            || ($taskId !== null && !Str::isUuid($taskId))) {
            throw new InvalidArgumentException('Referencia sintetica de tarefa invalida.');
        }
    }

    private static function validDate(string $date): bool
    {
        $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $value !== false && $value->format('Y-m-d') === $date;
    }

    private function appendEvent(string $id, string $org, int $version, string $kind): void
    {
        $this->db->table('public.crm_lab_task_events')->insert([
            'event_id' => (string) Str::uuid(),
            'task_id' => strtolower($id),
            'organization_id' => $org,
            'version' => $version,
            'event_type' => $kind,
        ]);
    }

    private static function result(string $code): array
    {
        return [
            'status_code' => $code,
            'can_contact' => false,
            'can_notify' => false,
            'can_activate' => false,
        ];
    }
}
