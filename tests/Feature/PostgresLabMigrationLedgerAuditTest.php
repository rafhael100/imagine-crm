<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEGDBA-04: exact read-only inventory of the disposable PostgreSQL migration
 * ledger after the intentionally separate base/auth/async migration commands.
 *
 * A clean CI install with no unknown or duplicated ledger entries is useful
 * evidence; it does not prove compatibility with a legacy or real database,
 * backup recovery, interrupted migrations, or safe destructive operations.
 */
final class PostgresLabMigrationLedgerAuditTest extends TestCase
{
    private const BASE = '2026_11_04_000001_create_crm_identity_organizations_memberships';

    private const LAB_AUTH = [
        '2026_11_04_000002_create_candidate_crm_auth_registry',
        '2026_11_04_000003_create_candidate_iam_hardening',
    ];

    private const LAB_ASYNC = '2026_11_05_000004_create_synthetic_async_ledger';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql' || !getenv('ASYNC_AUDIT_TEST_USERNAME')) {
            $this->markTestSkipped('Requires the disposable PostgreSQL CI migration ledger.');
        }
    }

    public function test_clean_ci_ledger_has_exactly_four_known_migrations_in_separate_batches(): void
    {
        // No writes, migrations, rollback, or other DDL are performed here.
        $rows = DB::table('migrations')->select(['migration', 'batch'])
            ->orderBy('migration')->get();

        $expected = [
            self::BASE,
            ...self::LAB_AUTH,
            self::LAB_ASYNC,
        ];
        sort($expected);

        self::assertSame($expected, $rows->pluck('migration')->all());
        self::assertSame(count($expected), count(array_unique($rows->pluck('migration')->all())));

        $batches = [];
        foreach ($rows as $row) {
            self::assertIsInt($row->batch);
            self::assertGreaterThan(0, $row->batch);
            $batches[$row->migration] = $row->batch;
        }

        self::assertSame(1, $batches[self::BASE]);
        self::assertSame(2, $batches[self::LAB_AUTH[0]]);
        self::assertSame(2, $batches[self::LAB_AUTH[1]]);
        self::assertSame(3, $batches[self::LAB_ASYNC]);
    }

    public function test_default_migration_discovery_contains_only_the_tenant_scaffold(): void
    {
        // Exact filenames cannot silently expand the default production
        // discovery to include candidate IAM, async, or other lab schemas.
        $migrator = $this->app['migrator'];
        $baseFiles = $migrator->getMigrationFiles([
            database_path('migrations'),
        ]);
        self::assertSame([self::BASE], array_keys($baseFiles));

        $authFiles = $migrator->getMigrationFiles([
            base_path('database/experimental-migrations/auth'),
        ]);
        self::assertSame(self::LAB_AUTH, array_keys($authFiles));

        $asyncFiles = $migrator->getMigrationFiles([
            base_path('database/experimental-migrations/async'),
        ]);
        self::assertSame([self::LAB_ASYNC], array_keys($asyncFiles));

        foreach ([$baseFiles, $authFiles, $asyncFiles] as $group) {
            foreach ($group as $file) {
                self::assertFileExists($file);
                self::assertStringEndsWith('.php', $file);
            }
        }
    }

    public function test_clean_ci_migration_catalog_is_only_a_rehearsal_not_a_release_gate(): void
    {
        $schema = DB::selectOne(
            "SELECT current_database() AS database_name,
                    current_user AS running_role,
                    current_setting('app.tenant_id', true) AS tenant_claim"
        );
        self::assertNotNull($schema);
        self::assertSame('imagine_crm_ci', $schema->database_name);
        self::assertSame('postgres', $schema->running_role);
        self::assertContains($schema->tenant_claim, [null, '']);

        // A superuser-installed fixture must NEVER be interpreted as proof
        // that a normal application role can safely migrate a real database.
        $role = DB::selectOne(
            'SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = current_user'
        );
        self::assertNotNull($role);
        self::assertTrue($role->rolsuper);
        self::assertTrue($role->rolbypassrls);
    }
}
