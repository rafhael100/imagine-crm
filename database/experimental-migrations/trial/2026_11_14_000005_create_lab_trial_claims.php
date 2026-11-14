<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F3-TRIAL-PG01: disposable CI-only global uniqueness constraint.
 *
 * The row is not a trial or enrollment. It is an immutable, pseudonymous
 * observation of a SYNTHETIC CNPJ; it grants no account or business rights.
 * This migration is NOT part of the default migrations/ directory.
 */
return new class extends Migration
{
    private const TABLE = 'crm_lab_trial_claims';

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');

        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_TRIAL_LEDGER_LAB') !== 'synthetic-only'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci'
            || DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('Trial uniqueness schema is laboratory only; disposable CI PostgreSQL required.');
        }
    }

    public function up(): void
    {
        $this->guard();

        Schema::create('crm_lab_trial_key_epochs', static function (Blueprint $t): void {
            $t->unsignedSmallInteger('version')->primary();
            $t->string('secret_tag', 64)->unique();
        });
        DB::statement("ALTER TABLE crm_lab_trial_key_epochs ADD CONSTRAINT lab_trial_key_epoch_closed CHECK (
            version BETWEEN 1 AND 4 AND secret_tag ~ '^[0-9a-f]{64}
            // A keyed HMAC fingerprint, NEVER the raw CNPJ. Global across tenants:
            // one synthetic company cannot receive new markers in other tenants.
            $t->string('cnpj_hmac', 64)->primary();
            $t->unsignedSmallInteger('key_version');
            $t->bigInteger('started_at');
            $t->bigInteger('expires_at');
            $t->string('state', 32);
            $t->boolean('can_activate')->default(false);
            $t->boolean('can_register')->default(false);
            $t->boolean('can_charge')->default(false);
        });

        DB::statement("ALTER TABLE crm_lab_trial_claims ADD CONSTRAINT lab_trial_unusable CHECK (
            cnpj_hmac ~ '^[0-9a-f]{64}$'
            AND key_version BETWEEN 1 AND 4
            AND started_at > 0
            AND expires_at = started_at + 1209600
            AND state = 'synthetic_observation_only'
            AND can_activate = false AND can_register = false AND can_charge = false
        )");

        // The trial ledger is global, not tenant-scoped. Restricted CI role
        // gets INSERT only; no SELECT/UPDATE/DELETE/TRUNCATE grants.
        // No OAuth, customer record, CNPJ lookup, access grants or send route.
    }

    public function down(): void
    {
        $this->guard();
        Schema::dropIfExists(self::TABLE);
        Schema::dropIfExists('crm_lab_trial_key_epochs');
    }
};

        )");

        Schema::create(self::TABLE, static function (Blueprint $t): void {
            // A keyed HMAC fingerprint, NEVER the raw CNPJ. Global across tenants:
            // one synthetic company cannot receive new markers in other tenants.
            $t->string('cnpj_hmac', 64)->primary();
            $t->unsignedSmallInteger('key_version');
            $t->bigInteger('started_at');
            $t->bigInteger('expires_at');
            $t->string('state', 32);
            $t->boolean('can_activate')->default(false);
            $t->boolean('can_register')->default(false);
            $t->boolean('can_charge')->default(false);
        });

        DB::statement("ALTER TABLE crm_lab_trial_claims ADD CONSTRAINT lab_trial_unusable CHECK (
            cnpj_hmac ~ '^[0-9a-f]{64}$'
            AND key_version = 1
            AND started_at > 0
            AND expires_at = started_at + 1209600
            AND state = 'synthetic_observation_only'
            AND can_activate = false AND can_register = false AND can_charge = false
        )");

        // The trial ledger is global, not tenant-scoped. Restricted CI role
        // gets INSERT only; no SELECT/UPDATE/DELETE/TRUNCATE grants.
        // No OAuth, customer record, CNPJ lookup, access grants or send route.
    }

    public function down(): void
    {
        $this->guard();
        Schema::dropIfExists(self::TABLE);
    }
};
