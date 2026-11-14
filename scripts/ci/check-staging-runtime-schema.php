<?php

declare(strict_types=1);

// CRM-STG-SCHEMA-01: CI-only probe of the REAL Laravel DB connection.
// No HTTP, signup, access grant, production migration, or customer data.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

function denyCiRuntime(string $message): never
{
    fwrite(STDERR, 'CRM SCHEMA CI NO-GO: '.$message.PHP_EOL);
    exit(41);
}

foreach ([
    'CI' => 'true',
    'GITHUB_ACTIONS' => 'true',
    'GITHUB_REPOSITORY' => 'rafhael100/imagine-crm',
    'CRM_SCHEMA_LAB' => 'synthetic-only',
    'APP_ENV' => 'staging',
    'DB_HOST' => 'postgres',
    'DB_DATABASE' => 'imagine_crm_staging',
    'DB_USERNAME' => 'crm_staging_app',
] as $name => $expected) {
    if (getenv($name) !== $expected) {
        denyCiRuntime('unexpected lab environment: '.$name);
    }
}

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$db = DB::connection();
$role = $db->selectOne(<<<'SQL'
SELECT current_user AS role_name,
  CASE WHEN r.rolsuper OR r.rolbypassrls
    OR pg_has_role(current_user, c.relowner, 'MEMBER')
    THEN 'unsafe' ELSE 'safe' END AS role_safety,
  CASE WHEN has_schema_privilege(current_user, 'public', 'CREATE')
    OR has_table_privilege(current_user, 'public.crm_memberships', 'INSERT')
    OR has_table_privilege(current_user, 'public.crm_memberships', 'UPDATE')
    OR has_table_privilege(current_user, 'public.crm_memberships', 'DELETE')
    THEN 'unsafe' ELSE 'read_only' END AS grant_safety,
  c.relrowsecurity AS rls_enabled,
  c.relforcerowsecurity AS rls_forced
FROM pg_roles AS r CROSS JOIN pg_class AS c
WHERE r.rolname = current_user
  AND c.oid = 'public.crm_memberships'::regclass
SQL);

if (!$role || $role->role_name !== 'crm_staging_app'
    || $role->role_safety !== 'safe'
    || $role->grant_safety !== 'read_only'
    || !$role->rls_enabled || !$role->rls_forced) {
    denyCiRuntime('database role or membership FORCE RLS is unsafe');
}

$scopes = [
    'a1111111-1111-4111-8111-111111111111' => 1,
    'b2222222-2222-4222-8222-222222222222' => 1,
    '' => 0,
];
foreach ($scopes as $tenant => $expectedCount) {
    $visible = $db->transaction(static function () use ($db, $tenant): int {
        $db->selectOne("SELECT set_config('app.tenant_id', ?, true)", [$tenant]);

        return (int) $db->table('crm_memberships')->count();
    });

    if ($visible !== $expectedCount || $db->transactionLevel() !== 0) {
        denyCiRuntime('tenant membership RLS isolation failed');
    }
}

$withoutScope = (int) $db->table('crm_memberships')->count();
if ($withoutScope !== 0) {
    denyCiRuntime('unscoped connection leaked tenant membership');
}

echo "CRM SCHEMA CI PASS: Laravel PHP connected as non-owner, non-superuser app role;".
    " membership FORCE RLS and tenant A/B/unscoped isolation confirmed.".PHP_EOL;
