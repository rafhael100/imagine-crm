<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function isolated(): void
    {
        if (!config('identity.experimental') || !app()->environment('local', 'testing')) {
            throw new RuntimeException('Candidate auth schema is isolated-development only.');
        }
        if (DB::getDriverName() !== 'pgsql' && !app()->environment('testing')) {
            throw new RuntimeException('PostgreSQL required outside the test adapter.');
        }
    }

    public function up(): void
    {
        $this->isolated();
        Schema::create('crm_auth_operations', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('action', 32);
            $t->uuid('actor_id')->nullable();
            $t->uuid('subject_id')->nullable()->index();
            $t->uuid('mandate_id')->nullable();
            $t->string('state', 16);
            $t->unsignedBigInteger('created_at');
        });
        Schema::create('crm_auth_mandates', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->uuid('actor_id');
            $t->uuid('subject_id');
            $t->unsignedBigInteger('actor_version');
            $t->string('action', 32);
            $t->unsignedBigInteger('expires_at');
            $t->unsignedBigInteger('revoked_at')->nullable();
        });
        Schema::create('crm_iam_audit_events', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->uuid('correlation_id')->index();
            $t->string('action', 32);
            $t->string('outcome', 16);
            $t->uuid('actor_id')->nullable();
            $t->uuid('subject_id')->nullable();
            $t->string('origin_key', 64)->nullable();
            $t->unsignedBigInteger('occurred_at');
        });
        Schema::table('crm_auth_sessions', function (Blueprint $t): void {
            $t->uuid('operation_id')->nullable()->index();
            $t->uuid('security_hold')->nullable()->index();
        });
    }

    public function down(): void
    {
        $this->isolated();
        Schema::table('crm_auth_sessions', function (Blueprint $t): void {
            $t->dropIndex(['operation_id']); $t->dropIndex(['security_hold']);
            $t->dropColumn(['operation_id', 'security_hold']);
        });
        Schema::dropIfExists('crm_iam_audit_events');
        Schema::dropIfExists('crm_auth_mandates');
        Schema::dropIfExists('crm_auth_operations');
    }
};
