<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-182 — static deny-only regression for experimental IAM / membership SQL.
 *
 * A passing behavioral integration test is insufficient if a NEW code path
 * accidentally introduces an unqualified table name not exercised by tests.
 * Only PHP source under the two actual runtime namespaces is scanned.
 */
final class CriticalRegistryQualifiedTableSourceGuardTest extends TestCase
{
    private const REQUIRED = [
        'app/Core/Identity/DatabaseSessionAuthority.php',
        'app/Core/Identity/ExperimentalAuthenticator.php',
        'app/Core/Identity/IamAudit.php',
        'app/Core/Identity/IamOperationRunner.php',
        'app/Core/Identity/IamRegistryTable.php',
        'app/Core/Identity/LaboratoryProvisioner.php',
        'app/Core/Identity/GlobalIdentity.php',
        'app/Core/Tenancy/PostgresActiveMembershipStore.php',
    ];

    /** @return list<string> */
    private static function sourceFiles(): array
    {
        $root = dirname(__DIR__, 2);
        foreach (self::REQUIRED as $file) {
            self::assertFileExists($root.'/'.$file);
        }

        $paths = [
            ...glob($root.'/app/Core/Identity/*.php'),
            $root.'/app/Core/Tenancy/PostgresActiveMembershipStore.php',
        ];
        self::assertGreaterThanOrEqual(count(self::REQUIRED), count($paths));

        sort($paths);

        return $paths;
    }

    /** @return list<string> */
    private static function violations(string $source): array
    {
        $patterns = [
            // Laravel query builders and DB facade calls accepting a literal
            // physical table or relation identifier.
            '/(?:->|::)(?:table|from|join|leftJoin|rightJoin|crossJoin|joinSub)\s*\(\s*([\\x27\\x22])crm_[a-z0-9_]+\1/i',
            // A regclass lookup can be silently redirected to pg_temp.
            '/([\\x27\\x22])crm_[a-z0-9_]+\1\s*::\s*regclass/i',
            // Raw SQL for high-impact table operations should explicitly
            // qualify public schema; don't attempt full SQL parsing here.
            '/\b(?:FROM|JOIN|UPDATE|INTO|REFERENCES|TRUNCATE)\s+(?:ONLY\s+)?(?:[\\x27\\x22])?crm_(?:auth|global|iam|memberships)[a-z0-9_]*/i',
        ];
        $violations = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $matches) > 0) {
                array_push($violations, ...$matches[0]);
            }
        }

        return $violations;
    }

    public function test_experimental_iam_and_membership_sources_have_no_unqualified_sql_table_identifiers(): void
    {
        foreach (self::sourceFiles() as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            self::assertSame(
                [],
                self::violations($source),
                'Ambiguous registry references in '.basename($path),
            );
        }
    }

    public function test_guard_detects_new_unsafe_builder_and_regclass_patterns(): void
    {
        foreach ([
            "return \$db->table('crm_auth_credentials')->first();",
            "return DB::table('crm_auth_sessions')->count();",
            "return \$query->from('crm_global_identities');",
            "SELECT * FROM crm_auth_mandates WHERE id = ?",
            "SELECT c.oid FROM pg_class c WHERE c.oid = 'crm_memberships'::regclass",
        ] as $bad) {
            self::assertNotSame([], self::violations($bad), $bad);
        }

        foreach ([
            "return \$db->table('public.crm_auth_credentials')->first();",
            "return \$db->table(IamRegistryTable::name(\$db,'crm_auth_credentials'))->first();",
            "SELECT * FROM public.crm_auth_sessions WHERE id = ?",
            "c.oid = 'public.crm_memberships'::regclass",
        ] as $safe) {
            self::assertSame([], self::violations($safe), $safe);
        }
    }

    public function test_registry_allowlist_and_global_model_stay_schema_aware(): void
    {
        $root = dirname(__DIR__, 2);
        $helper = file_get_contents($root.'/app/Core/Identity/IamRegistryTable.php');
        $model = file_get_contents($root.'/app/Core/Identity/GlobalIdentity.php');
        $store = file_get_contents($root.'/app/Core/Tenancy/PostgresActiveMembershipStore.php');
        self::assertIsString($helper);
        self::assertIsString($model);
        self::assertIsString($store);

        foreach ([
            'if (!isset(self::TABLES[$table]))',
            "return 'public.'.\$table;",
            "getDriverName() === 'sqlite' && app()->environment('testing')",
        ] as $guard) {
            self::assertStringContainsString($guard, $helper);
        }
        self::assertStringContainsString('public function getTable(): string', $model);
        self::assertStringContainsString("IamRegistryTable::name(\$this->getConnection(), 'crm_global_identities')", $model);
        self::assertStringContainsString("'public.crm_memberships'::regclass", $store);
        self::assertStringContainsString("->table('public.crm_memberships')", $store);
    }
}
