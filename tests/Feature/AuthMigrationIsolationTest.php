<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AuthMigrationIsolationTest extends TestCase
{
    private const PATH = 'database/experimental-migrations/auth';
    private const MIGRATION = '2026_11_04_000002_create_candidate_crm_auth_registry';

    public function test_standard_discovery_never_loads_the_auth_laboratory(): void
    {
        foreach (['production', 'local', 'testing'] as $environment) {
            $this->app->instance('env', $environment);
            config(['identity.experimental' => true]);
            $migrator = $this->app['migrator'];
            $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
            $this->assertArrayNotHasKey(self::MIGRATION, $files);
            $this->assertCount(2, $migrator->getMigrationFiles([base_path(self::PATH)]));
            $this->assertArrayNotHasKey('2026_11_04_000003_create_candidate_iam_hardening', $files);
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

    public function test_standard_release_migrate_on_fresh_postgres_schema_does_not_install_auth(): void
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
        config(['database.connections.crm_release_probe' => $settings, 'identity.experimental' => false]);
        try {
            DB::setDefaultConnection('crm_release_probe');
            $this->app->instance('env', 'production');
            $this->artisan('migrate', ['--database' => 'crm_release_probe', '--force' => true])->assertExitCode(0);
            $probe = DB::connection('crm_release_probe');
            $this->assertTrue($probe->getSchemaBuilder()->hasTable('crm_memberships'));
            foreach (['crm_auth_credentials', 'crm_auth_sessions', 'crm_auth_recovery_codes', 'crm_auth_operations', 'crm_auth_mandates', 'crm_iam_audit_events'] as $table) {
                $this->assertFalse($probe->getSchemaBuilder()->hasTable($table));
            }
            $this->assertFalse($probe->table('migrations')->where('migration', self::MIGRATION)->exists());
            $this->app->instance('env', 'testing');
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
