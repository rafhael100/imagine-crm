<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!app()->environment('testing', 'local')) {
            throw new RuntimeException('Candidate auth schema is isolated-development only.');
        }
        if (DB::getDriverName() !== 'pgsql' && !app()->environment('testing')) {
            throw new RuntimeException('PostgreSQL required outside the test adapter.');
        }
        // Protected GLOBAL IAM data, not tenant business data; access by table ACL.
        // Runtime IAM role has no membership/business grants or ownership.
        Schema::create('crm_auth_credentials', function (Blueprint $t): void {
            $t->uuid('identity_id')->primary();
            $t->foreign('identity_id')->references('id')->on('crm_global_identities')->restrictOnDelete();
            $t->string('login_key', 64)->unique();
            $t->string('password_hash', 255);
            $t->string('status', 16);
            $t->unsignedBigInteger('verified_at')->nullable();
            $t->unsignedBigInteger('version')->default(1);
            $t->text('factor_ciphertext')->nullable();
            $t->unsignedBigInteger('factor_confirmed_at')->nullable();
            $t->unsignedBigInteger('last_totp_step')->nullable();
        });
        Schema::create('crm_auth_sessions', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('token_hash', 64)->unique();
            $t->uuid('identity_id');
            $t->foreign('identity_id')->references('identity_id')->on('crm_auth_credentials')->restrictOnDelete();
            $t->string('state', 16);
            $t->unsignedBigInteger('credential_version');
            $t->string('binding', 64);
            $t->unsignedBigInteger('created_at');
            $t->unsignedBigInteger('last_seen_at');
            $t->unsignedBigInteger('expires_at');
            $t->unsignedBigInteger('absolute_expires_at');
            $t->unsignedBigInteger('mfa_verified_at')->nullable();
            $t->index(['identity_id', 'state']);
        });
        Schema::create('crm_auth_recovery_codes', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->uuid('identity_id');
            $t->foreign('identity_id')->references('identity_id')->on('crm_auth_credentials')->restrictOnDelete();
            $t->string('code_hash', 255);
            $t->unsignedBigInteger('credential_version');
            $t->unsignedBigInteger('consumed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_auth_recovery_codes');
        Schema::dropIfExists('crm_auth_sessions');
        Schema::dropIfExists('crm_auth_credentials');
    }
};
