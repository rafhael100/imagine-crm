<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;

/**
 * F240: CRM-owned Ed25519 verification keys retrieved using a dedicated
 * non-owner, SELECT-only PostgreSQL16 role with FORCE RLS.
 *
 * A key_id from signed claims is only a selector. Tenant must be established
 * by the caller's independent service-routing/trust decision. This test
 * experiment neither provides that operational decision nor emits ALLOW.
 */
final readonly class AiaPgRestrictedKeyringLab implements AiaTrustedPublicKeySource
{
    private const ROLE = 'crm_aia_key_verify_ci';
    private const TABLE = 'public.aia_lab_ed25519_service_keys';

    public function __construct(private Connection $connection)
    {
    }

    /** @return array<string,array{public_key:string,enabled:bool}> */
    public function trustedPublicKey(string $keyId, string $expectedTenant): array
    {
        $db = $this->connection;
        if (getenv('AIA_F240_KEYRING_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || $db->getDriverName() !== 'pgsql'
            || $db->getDatabaseName() !== 'imagine_crm_ci'
            || $db->getConfig('host') !== '127.0.0.1'
            || $db->getConfig('username') !== self::ROLE
            || $db->transactionLevel() !== 0
            || !self::synthetic($keyId, 3)
            || !self::synthetic($expectedTenant, 1)) {
            return [];
        }

        try {
            $role = $db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                    current_setting('app.tenant_id',true) AS inherited_tenant,
                    current_setting('row_security') AS rls,
                    r.rolsuper, r.rolbypassrls, r.rolinherit,
                    r.rolcreatedb, r.rolcreaterole,
                    c.relrowsecurity, c.relforcerowsecurity,
                    pg_has_role(current_user,c.relowner,'MEMBER') AS owns,
                    has_table_privilege(current_user,c.oid,'SELECT') AS can_read,
                    has_table_privilege(current_user,c.oid,'INSERT') AS can_insert,
                    has_table_privilege(current_user,c.oid,'UPDATE') AS can_update,
                    has_table_privilege(current_user,c.oid,'DELETE') AS can_delete
                 FROM pg_roles r CROSS JOIN pg_class c
                 WHERE r.rolname=current_user AND
                    c.oid='public.aia_lab_ed25519_service_keys'::regclass"
            );
            if (!$role
                || $role->dbname !== 'imagine_crm_ci'
                || $role->username !== self::ROLE
                || $role->rls !== 'on'
                || $role->rolsuper || $role->rolbypassrls
                || $role->rolinherit || $role->rolcreatedb
                || $role->rolcreaterole || $role->owns
                || !$role->relrowsecurity || !$role->relforcerowsecurity
                || !$role->can_read || $role->can_insert
                || $role->can_update || $role->can_delete
                || ($role->inherited_tenant !== null
                    && $role->inherited_tenant !== '')) {
                return [];
            }

            return $db->transaction(function () use (
                $db, $keyId, $expectedTenant
            ): array {
                $db->selectOne(
                    "SELECT set_config('app.tenant_id', ?, true)",
                    [$expectedTenant],
                );

                $row = $db->table(self::TABLE)
                    ->where('tenant_ref', $expectedTenant)
                    ->where('key_id', $keyId)
                    ->where('issuer', 'SYNTHETIC_AGENT_SERVICE')
                    ->where('audience', 'SYNTHETIC_CRM_API')
                    ->where('enabled', true)
                    ->whereNull('revoked_at')
                    ->where('revision', '>=', 1)
                    ->first(['public_key_base64', 'enabled', 'revision']);

                if (!$row || !is_string($row->public_key_base64)
                    || strlen($row->public_key_base64) !== 44) {
                    return [];
                }
                $key = base64_decode($row->public_key_base64, true);
                if (!is_string($key)
                    || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                    || !hash_equals(base64_encode($key), $row->public_key_base64)) {
                    return [];
                }
                return [$keyId => ['public_key' => $key, 'enabled' => true]];
            }, 1);
        } catch (\Throwable) {
            // Missing key, revoked key, broken policy/role/backend, inherited
            // tenant or read failure: no fallback to a privileged connection.
            return [];
        }
    }

    private static function synthetic(string $value, int $min): bool
    {
        return strlen($value) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{'.$min.',80}\z/D', $value) === 1;
    }
}
