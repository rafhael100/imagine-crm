<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncAuthorityBridge;
use App\Core\Async\AsyncDiagnostic;
use App\Core\Tenancy\ContaminatedTenantConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/** Explicit CI fixture ONLY. No container binding, routes, publisher or effect callback. */
final readonly class PostgresSyntheticLedger
{
    public const TABLES = ['crm_lab_async_ledger', 'crm_lab_async_attempts', 'crm_lab_async_inbox', 'crm_lab_async_outbox'];

    // Tenant comes from trusted fixture setup, not an HTTP/job resolver or the request.
    public function __construct(private Connection $db, private AsyncAuthorityBridge $bridge, private string $fixtureTenant) {}

    public function record(array $input, int $revision = 1, string $content = 'lab.alpha'): LedgerReceipt
    {
        $attempt = (string) Str::uuid();
        // Always re-inspect, including duplicates. A stored row never caches authority.
        $denial = $this->bridge->inspect($input);
        if (!config('identity.experimental') || !app()->environment('testing', 'local')) {
            return new LedgerReceipt('inconclusive', $attempt, $denial, AsyncDiagnostic::disabled());
        }
        try {
            $description = LedgerDescription::parse($input, $revision, $content);
            $request = $description->request;
            if (!in_array($this->fixtureTenant, [AuthorizationCases::TENANT, AuthorizationCases::OTHER], true)
                || $request->tenantId !== $this->fixtureTenant
                || $request->issuedAt > now()->getTimestamp() || $request->expiresAt <= now()->getTimestamp()) {
                throw new \InvalidArgumentException('Invalid synthetic context.');
            }
        } catch (\InvalidArgumentException) {
            return new LedgerReceipt('inconclusive', $attempt, $denial, AsyncDiagnostic::contract());
        }
        try {
            if ($this->db->getDriverName() !== 'pgsql' || $this->db->transactionLevel() !== 0) {
                throw new LogicException('Dedicated PostgreSQL laboratory connection required.');
            }
            $outcome = $this->db->transaction(function () use ($description, $request, $attempt, $denial): string {
                $this->assertSafeConnection();
                $this->db->selectOne("SELECT set_config('app.tenant_id', ?, true)", [$this->fixtureTenant]);
                $key = ['tenant_id' => $this->fixtureTenant, 'revision' => $description->revision,
                    'scope_kind' => $request->intent->scope, 'scope_id' => $request->intent->scopeId];
                $id = (string) Str::uuid();
                $inserted = $this->db->table(self::TABLES[0])->insertOrIgnore($key + [
                    'id' => $id, 'operation_id' => $request->operationId, 'idempotency_key' => $request->idempotencyKey,
                    'policy_version' => 'lab-async-deny-v1', 'digest' => $description->digest,
                    'state' => 'registered_for_test', 'created_at' => now()->getTimestamp(), 'expires_at' => $request->expiresAt,
                ]);
                // Both logical operation and delivery key have independent uniqueness constraints.
                $rows = $this->db->table(self::TABLES[0])->where($key)->where(function ($query) use ($request): void {
                    $query->where('operation_id', $request->operationId)->orWhere('idempotency_key', $request->idempotencyKey);
                })->orderBy('id')->get(); // Immutable rows: role has no UPDATE/DELETE; uniqueness holds insert locks.
                if ($rows->isEmpty()) { throw new LogicException('Unconfirmed local write.'); }
                $row = $rows->first();
                $same = $rows->count() === 1 && $row->operation_id === $request->operationId
                    && $row->idempotency_key === $request->idempotencyKey && hash_equals($row->digest, $description->digest);
                $outcome = !$same ? 'conflict' : ($inserted === 1 ? 'first_seen' : 'duplicate');
                $this->db->table(self::TABLES[1])->insert([
                    'id' => $attempt, 'tenant_id' => $this->fixtureTenant, 'ledger_id' => $row->id,
                    'outcome' => $outcome, 'state' => 'reconciliation_required',
                    'correlation_id' => $denial->observation->correlationId,
                    'causation_id' => $denial->observation->id, 'created_at' => now()->getTimestamp(),
                ]);
                if ($outcome === 'first_seen') {
                    foreach ([self::TABLES[2], self::TABLES[3]] as $table) {
                        $this->db->table($table)->insert(['id' => (string) Str::uuid(),
                            'tenant_id' => $this->fixtureTenant, 'ledger_id' => $row->id, 'state' => 'registered_for_test']);
                    }
                }
                return $outcome;
            }, 1); // No retry/replay, not even for deadlock or uncertain commit.
            return new LedgerReceipt($outcome, $attempt, $denial, AsyncDiagnostic::reconciliation());
        } catch (Throwable $failure) {
            return new LedgerReceipt('inconclusive', $attempt, $denial, AsyncDiagnostic::fromFailure($failure));
        }
    }

    private function assertSafeConnection(): void
    {
        $state = $this->db->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant,
            current_setting('row_security') AS row_security, r.rolsuper, r.rolbypassrls
            FROM pg_roles r WHERE r.rolname = current_user");
        if ($state && $state->tenant !== null && $state->tenant !== '') { throw new ContaminatedTenantConnection(); }
        if (!$state || $state->rolsuper || $state->rolbypassrls || $state->row_security !== 'on') {
            throw new LogicException('Unsafe laboratory role.');
        }
        foreach (self::TABLES as $table) {
            $tableState = $this->db->selectOne("SELECT relrowsecurity, relforcerowsecurity,
                pg_has_role(current_user, relowner, 'MEMBER') AS owns_table FROM pg_class WHERE oid = ?::regclass", [$table]);
            if (!$tableState || $tableState->owns_table || !$tableState->relrowsecurity || !$tableState->relforcerowsecurity) {
                throw new LogicException('Unsafe laboratory table.');
            }
        }
    }
}
