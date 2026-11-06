<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function guard(): void
    {
        if (!config('identity.tenant_schema_experimental') || !app()->environment('local', 'testing')) {
            throw new RuntimeException('Candidate tenant schema is isolated-development only.');
        }
        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('CRM tenant/RLS schema requires PostgreSQL.');
        }
    }

    public function up(): void
    {
        $this->guard();

        // Candidate F1 only, isolated development. No production schema approved.
        Schema::create('crm_global_identities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });

        Schema::create('crm_organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });

        Schema::create('crm_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('global_identity_id');
            $table->string('status', 16);
            $table->unsignedBigInteger('version')->default(1);
            $table->timestampsTz();

            $table->foreign('organization_id')
                ->references('id')->on('crm_organizations')->restrictOnDelete();
            $table->foreign('global_identity_id')
                ->references('id')->on('crm_global_identities')->restrictOnDelete();
            $table->unique(
                ['organization_id', 'global_identity_id'],
                'crm_memberships_org_identity_unique'
            );
        });

        DB::statement('ALTER TABLE crm_memberships ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE crm_memberships FORCE ROW LEVEL SECURITY');

        $scope = "organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid";

        DB::statement(
            'CREATE POLICY crm_memberships_tenant_scope ON crm_memberships FOR ALL TO PUBLIC ' .
            "USING ({$scope}) WITH CHECK ({$scope})"
        );
    }

    public function down(): void
    {
        $this->guard();
        Schema::dropIfExists('crm_memberships');
        Schema::dropIfExists('crm_organizations');
        Schema::dropIfExists('crm_global_identities');
    }
};
