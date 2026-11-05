<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['crm_lab_async_ledger', 'crm_lab_async_attempts', 'crm_lab_async_inbox', 'crm_lab_async_outbox'];

    private function guard(): void
    {
        if (!config('identity.experimental') || !app()->environment('local', 'testing')) {
            throw new RuntimeException('Synthetic async schema is laboratory only.');
        }
        if (DB::getDriverName() !== 'pgsql') { throw new RuntimeException('PostgreSQL required for synthetic ledger.'); }
    }

    public function up(): void
    {
        $this->guard();
        Schema::create(self::TABLES[0], function (Blueprint $t): void {
            $t->uuid('id')->primary(); $t->uuid('tenant_id'); $t->uuid('operation_id'); $t->uuid('idempotency_key');
            $t->integer('revision'); $t->string('scope_kind', 16); $t->uuid('scope_id');
            $t->string('policy_version', 32); $t->string('digest', 64); $t->string('state', 32);
            $t->bigInteger('created_at'); $t->bigInteger('expires_at');
            $t->unique(['tenant_id', 'id'], 'lab_async_tenant_id');
            $t->unique(['tenant_id', 'operation_id', 'revision', 'scope_kind', 'scope_id'], 'lab_async_operation');
            $t->unique(['tenant_id', 'idempotency_key', 'revision', 'scope_kind', 'scope_id'], 'lab_async_key');
        });
        DB::statement("ALTER TABLE crm_lab_async_ledger ADD CONSTRAINT lab_ledger_closed CHECK (
            state = 'registered_for_test' AND policy_version = 'lab-async-deny-v1' AND revision IN (1,2)
            AND scope_kind IN ('tenant','record','team','unit') AND digest ~ '^[0-9a-f]{64}$'
            AND created_at > 0 AND expires_at > created_at)");
        foreach (array_slice(self::TABLES, 1) as $table) {
            Schema::create($table, function (Blueprint $t) use ($table): void {
                $t->uuid('id')->primary(); $t->uuid('tenant_id'); $t->uuid('ledger_id'); $t->string('state', 32);
                $t->foreign(['tenant_id', 'ledger_id'])->references(['tenant_id', 'id'])->on(self::TABLES[0])->restrictOnDelete();
                if ($table === self::TABLES[1]) {
                    $t->string('outcome', 16); $t->uuid('correlation_id'); $t->uuid('causation_id'); $t->bigInteger('created_at');
                } else { $t->unique(['tenant_id', 'ledger_id']); }
            });
            $check = $table === self::TABLES[1]
                ? "state = 'reconciliation_required' AND outcome IN ('first_seen','duplicate','conflict')"
                : "state = 'registered_for_test'";
            DB::statement("ALTER TABLE {$table} ADD CHECK ({$check})");
        }
        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY lab_tenant_isolation ON {$table} USING
                (tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)");
        }
        // No role, credential, business grant, dispatcher or automatic migration path.
    }

    public function down(): void
    {
        $this->guard();
        foreach (array_reverse(self::TABLES) as $table) { Schema::dropIfExists($table); }
    }
};
