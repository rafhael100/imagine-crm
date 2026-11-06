<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AuthMigrationIsolationTest extends TestCase
{
    private const PATH = 'database/experimental-migrations/auth';
    private const TENANT_PATH = 'database/experimental-migrations/tenant';
    private const TENANT_MIGRATION = '2026_11_04_000001_create_crm_identity_organizations_memberships';
    private const MIGRATION = '2026_11_04_000002_create_candidate_crm_auth_registry';

    public function test_standard_discovery_never_loads_experimental_laboratories(): void
    {
        foreach (['production', 'local', 'testing'] as $environment) {
            $this->app->instance('env', $environment);
            config(['identity.experimental' => true, 'identity.tenant_schema_experimental' => true]);
            $migrator = $this->app['migrator'];
            $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
            $this->assertArrayNotHasKey(self::MIGRATION, $files);
            $this->assertArrayNotHasKey(self::TENANT_MIGRATION, $files);
            $this->assertCount(2, $migrator->getMigrationFiles([base_path(self::PATH)]));
            $this->assertCount(1, $migrator->getMigrationFiles([base_path(self::TENANT_PATH)]));
            $this->assertArrayNotHasKey('2026_11_04_000003_create_candidate_iam_hardening', $files);
        }
    }

    public function test_candidate_tenant_migration_rejects_unapproved_environment_or_profile(): void
    {
        $migration = require base_path(self::TENANT_PATH.'/'.self::TENANT_MIGRATION.'.php');
        foreach ([['production', true], ['testing', false]] as [$environment, $enabled]) {
            $this->app->instance('env', $environment);
            config(['identity.tenant_schema_experimental' => $enabled]);
            foreach (['up', 'down'] as $direction) {
                try {
                    $migration->$direction();
                    $this->fail('Candidate tenant DDL must be blocked before any change.');
                } catch (\RuntimeException $e) {
                    $this->assertSame('Candidate tenant schema is isolated-development only.', $e->getMessage());
                }
            }
        }
        $this->app->instance('env', 'testing');
        config(['identity.tenant_schema_experimental' => true]);
        if (DB::getDriverName() === 'sqlite') {
            foreach (['up', 'down'] as $direction) {
                try {
                    $migration->$direction();
                    $this->fail('Candidate tenant DDL requires PostgreSQL.');
                } catch (\RuntimeException $e) {
                    $this->assertSame('CRM tenant/RLS schema requires PostgreSQL.', $e->getMessage());
                }
            }
        }
    }


    public function test_tenant_migration_rejects_wrong_database_or_nonloopback_host(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires disposable PostgreSQL CI database.');
        }
        $migration = require base_path(self::TENANT_PATH.'/'.self::TENANT_MIGRATION.'.php');
        $this->app->instance('env', 'testing');
        config(['identity.tenant_schema_experimental' => true,
            'identity.tenant_schema_lab_database' => 'imagine_crm_lab_wrong']);
        foreach (['up', 'down'] as $direction) {
            try {
                $migration->$direction();
                $this->fail('Mismatched laboratory database was accepted.');
            } catch (\\RuntimeException $e) {
                $this->assertSame(
                    'Candidate tenant schema requires an explicitly approved loopback laboratory database.',
                    $e->getMessage()
                );
            }
        }
        $original = DB::getDefaultConnection();
        $settings = config('database.connections.'.$original);
        $settings['url'] = null;
        $settings['host'] = 'external-db.invalid';
        config(['database.connections.crm_nonloopback_probe' => $settings,
            'identity.tenant_schema_lab_database' => 'imagine_crm_ci']);
        try {
            DB::setDefaultConnection('crm_nonloopback_probe');
            foreach (['up', 'down'] as $direction) {
                try {
                    $migration->$direction();
                    $this->fail('Non-loopback database endpoint was accepted.');
                } catch (\\RuntimeException $e) {
                    $this->assertSame(
                        'Candidate tenant schema requires an explicitly approved loopback laboratory database.',
                        $e->getMessage()
                    );
                }
            }
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('crm_nonloopback_probe');
        }
    }

    public function test_recorded_legacy_tenant_migration_is_not_rediscovered_or_double_applied(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires disposable PostgreSQL CI database.');
        }
        // Simulates the old migration ledger in a throwaway namespace, never a persisted environment.
        $setup = DB::connection();
        $schema = 'crm_legacy_probe_'.bin2hex(random_bytes(6));
        $setup->statement('CREATE SCHEMA '.$schema);
        $original = DB::getDefaultConnection();
        $settings = config('database.connections.'.$original);
        $settings['search_path'] = $schema;
        $settings['url'] = null;
        config(['database.connections.crm_legacy_probe' => $settings,
            'identity.tenant_schema_lab_database' => 'imagine_crm_ci',
            'identity.tenant_schema_experimental' => true]);
        try {
            DB::setDefaultConnection('crm_legacy_probe');
            $this->app->instance('env', 'testing');
            $this->artisan('migrate', [
                '--database' => 'crm_legacy_probe', '--path' => self::TENANT_PATH, '--force' => true
            ])->assertExitCode(0);
            $db = DB::connection('crm_legacy_probe');
            $recorded = $db->table('migrations')->where('migration', self::TENANT_MIGRATION);
            $this->assertSame(1, $recorded->count());
            $this->assertTrue($db->getSchemaBuilder()->hasTable('crm_memberships'));

            // Default release path must not mutate or re-run the recorded tenant migration.
            config(['identity.tenant_schema_experimental' => false]);
            $this->artisan('migrate', [
                '--database' => 'crm_legacy_probe', '--force' => true
            ])->assertExitCode(0);
            $this->assertSame(1, $recorded->count());
            $this->assertTrue($db->getSchemaBuilder()->hasTable('crm_memberships'));

            // Explicit opt-in sees the same basename in the ledger and must not duplicate DDL.
            config(['identity.tenant_schema_experimental' => true]);
            $this->artisan('migrate', [
                '--database' => 'crm_legacy_probe', '--path' => self::TENANT_PATH, '--force' => true
            ])->assertExitCode(0);
            $this->assertSame(1, $recorded->count());
            $this->assertTrue($db->getSchemaBuilder()->hasTable('crm_memberships'));

            // Rollback is permitted only here: isolated namespace, explicit path, opt-in.
            $this->artisan('migrate:rollback', [
                '--database' => 'crm_legacy_probe', '--path' => self::TENANT_PATH, '--force' => true
            ])->assertExitCode(0);
            $this->assertFalse($db->getSchemaBuilder()->hasTable('crm_memberships'));
        } finally {
            $this->app->instance('env', 'testing');
            DB::setDefaultConnection($original);
            DB::purge('crm_legacy_probe');
            $setup->statement('DROP SCHEMA '.$schema.' CASCADE');
        }
    }

    public function test_explicit_lab_migration_and_rollback_on_sqlite(): void
    {
        if (DB::getDriverName() !== 'sqlite') { $this->markTestSkipped('SQLite adapter proof only.'); }
        config(['identity.experimental' => true]);
        Schema::create('crm_global_identities', fn ($t) => $t->uuid('id')->primary());
        $this->artisan('migrate', ['--path' => self::PATH, '--force' => true])->assertExitCode(0);
        foreach (['crm_auth_credentials', 'crm_auth_sessions', 'crm_auth_recovery_codes', 'crm_auth_operations', 'crm_auth_mandates', 'crm_iam_audit_events'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->artisan('migrate:rollback', ['--path' => self::PATH, '--force' => true])->assertExitCode(0);
        $this->assertFalse(Schema::hasTable('crm_auth_credentials'));
    }

    public function test_explicit_lab_path_still_refuses_production_and_disabled_profile(): void
    {
        foreach ([['production', true], ['testing', false]] as [$environment, $enabled]) {
            $this->app->instance('env', $environment);
            config(['identity.experimental' => $enabled]);
            foreach (glob(base_path(self::PATH).'/*.php') as $file) {
            $migration = require $file;
            foreach (['up', 'down'] as $direction) {
                try { $migration->$direction(); $this->fail('Laboratory must refuse this profile.'); }
                catch (\RuntimeException $e) {
                    $this->assertSame('Candidate auth schema is isolated-development only.', $e->getMessage());
                }
            }
            }
        }
    }

    public function test_standard_release_migrate_omits_both_laboratories_until_explicit_opt_in(): void
    {
        if (DB::getDriverName() !== 'pgsql') { $this->markTestSkipped('PostgreSQL migration proof only.'); }
        // Dedicated disposable namespace in the CI database; never an external deployment.
        $setup = DB::connection();
        $schema = 'crm_release_probe_'.bin2hex(random_bytes(6));
        $setup->statement('CREATE SCHEMA '.$schema);
        $original = DB::getDefaultConnection();
        $settings = config('database.connections.'.$original);
        $settings['search_path'] = $schema;
        $settings['url'] = null;
        config(['database.connections.crm_release_probe' => $settings, 'identity.experimental' => false,
            'identity.tenant_schema_experimental' => false,
            'identity.tenant_schema_lab_database' => 'imagine_crm_ci']);
        try {
            DB::setDefaultConnection('crm_release_probe');
            $this->app->instance('env', 'production');
            $this->artisan('migrate', ['--database' => 'crm_release_probe', '--force' => true])->assertExitCode(0);
            $probe = DB::connection('crm_release_probe');
            foreach (['crm_global_identities', 'crm_organizations', 'crm_memberships'] as $table) {
                $this->assertFalse($probe->getSchemaBuilder()->hasTable($table));
            }
            $this->assertFalse($probe->table('migrations')->where('migration', self::TENANT_MIGRATION)->exists());
            foreach (['crm_auth_credentials', 'crm_auth_sessions', 'crm_auth_recovery_codes', 'crm_auth_operations', 'crm_auth_mandates', 'crm_iam_audit_events'] as $table) {
                $this->assertFalse($probe->getSchemaBuilder()->hasTable($table));
            }
            $this->assertFalse($probe->table('migrations')->where('migration', self::MIGRATION)->exists());
            $this->app->instance('env', 'testing');
            config(['identity.tenant_schema_experimental' => true]);
            $this->artisan('migrate', ['--database' => 'crm_release_probe', '--path' => self::TENANT_PATH, '--force' => true])->assertExitCode(0);
            foreach (['crm_global_identities', 'crm_organizations', 'crm_memberships'] as $table) {
                $this->assertTrue($probe->getSchemaBuilder()->hasTable($table));
            }
            config(['identity.experimental' => true]);
            $this->artisan('migrate', ['--database' => 'crm_release_probe', '--path' => self::PATH, '--force' => true])->assertExitCode(0);
            foreach (['crm_auth_credentials', 'crm_auth_sessions', 'crm_auth_recovery_codes', 'crm_auth_operations', 'crm_auth_mandates', 'crm_iam_audit_events'] as $table) {
                $this->assertTrue($probe->getSchemaBuilder()->hasTable($table));
            }
        } finally {
            $this->app->instance('env', 'testing');
            DB::setDefaultConnection($original);
            DB::purge('crm_release_probe');
            // Identifier generated above from a fixed prefix and hex, in this CI database only.
            $setup->statement('DROP SCHEMA '.$schema.' CASCADE');
        }
    }
}
