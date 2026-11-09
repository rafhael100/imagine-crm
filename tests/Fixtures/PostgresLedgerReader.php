<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Tenancy\ContaminatedTenantConnection;
use Illuminate\Database\Connection;

/** SELECT-only fixture role + consistent read-only transaction. No IAM lookup/grant. */
final readonly class PostgresLedgerReader
{
    public function __construct(private Connection $db, private string $fixtureTenant) {}

    public function sample(): array
    {
        if (!config('identity.experimental') || !app()->environment('local', 'testing')) { throw new LedgerReadFailure(LedgerGap::LaboratoryDisabled); }
        if (!in_array($this->fixtureTenant, [AuthorizationCases::TENANT, AuthorizationCases::OTHER], true)
            || $this->db->getDriverName() !== 'pgsql' || $this->db->transactionLevel() !== 0) { throw new LedgerReadFailure(LedgerGap::UnsafeReader); }
        return $this->db->transaction(function (): array {
            // Set isolation/access mode before the first snapshot; no mixed four-table views.
            $this->db->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');
            $this->db->statement("SET LOCAL statement_timeout = '500ms'"); // Test bound, not a production SLA.
            $state = $this->db->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant,
                current_setting('row_security') AS row_security, r.rolsuper, r.rolbypassrls
                FROM pg_roles r WHERE r.rolname = current_user");
            if ($state && $state->tenant !== null && $state->tenant !== '') { throw new ContaminatedTenantConnection(); }
            if (!$state || $state->rolsuper || $state->rolbypassrls || $state->row_security !== 'on') { throw new LedgerReadFailure(LedgerGap::UnsafeReader); }
            foreach (PostgresSyntheticLedger::TABLES as $table) {
                $tableState = $this->db->selectOne("SELECT relrowsecurity, relforcerowsecurity,
                    pg_has_role(current_user, relowner, 'MEMBER') AS owns_table,
                    has_table_privilege(current_user, oid, 'SELECT') AS can_read,
                    has_table_privilege(current_user, oid, 'INSERT,UPDATE,DELETE,TRUNCATE,REFERENCES,TRIGGER') AS can_mutate,
                    has_any_column_privilege(current_user, oid, 'INSERT,UPDATE,REFERENCES') AS can_mutate_columns
                    FROM pg_class WHERE oid = to_regclass(?)", [$table]);
                if (!$tableState) { throw new LedgerReadFailure(LedgerGap::SchemaUnavailable); }
                if ($tableState->owns_table || !$tableState->relrowsecurity || !$tableState->relforcerowsecurity
                    || !$tableState->can_read || $tableState->can_mutate || $tableState->can_mutate_columns) { throw new LedgerReadFailure(LedgerGap::UnsafeReader); }
            }
            $this->db->selectOne("SELECT set_config('app.tenant_id', ?, true)", [$this->fixtureTenant]);
            $sample = [];
            foreach (array_keys(LedgerIntegrityProbe::COLUMNS) as $index => $name) {
                $rows = $this->db->table(PostgresSyntheticLedger::TABLES[$index])
                    ->where('tenant_id', $this->fixtureTenant)->orderBy('id')->limit(201)->get(LedgerIntegrityProbe::COLUMNS[$name]);
                if ($rows->count() > 200) { throw new LedgerReadFailure(LedgerGap::SampleLimit); }
                $sample[$name] = $rows->map(static fn ($row): array => (array) $row)->all();
            }
            return $sample;
        }, 1); // No repair, retry or auto-replay on any error.
    }
}
