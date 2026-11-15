<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;

/**
 * F241: atomic INSERT-only nonce claim using dedicated, nonowner PostgreSQL
 * role with FORCE RLS. Never SELECT or UPDATE the nonce ledger.
 *
 * The caller is responsible for original Agents signature verification and
 * supplying a CRM-side trusted tenant. Successful claim is NOT an ALLOW.
 */
final readonly class AiaPgRestrictedNonceLedgerLab implements AiaNonceClaimSource
{
    private const ROLE = 'crm_aia_nonce_ci';
    private const TABLE = 'public.aia_lab_nonce_atomic_probe';

    public function __construct(private Connection $connection)
    {
    }

    public function claim(string $nonce, string $tenantRef, int $claimedAt): bool
    {
        $db = $this->connection;
        if (getenv('AIA_F241_NONCE_ROLE_LAB') !== 'true'
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
            || !self::synthetic($tenantRef, 1)
            || !self::synthetic($nonce, 4)
            || $claimedAt < 0 || $claimedAt > 4102444800) {
            return false;
        }

        try {
            $role = $db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                    current_setting('app.tenant_id',true) AS inherited_tenant,
                    current_setting('row_security') AS row_security,
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
                    c.oid='public.aia_lab_nonce_atomic_probe'::regclass"
            );
            if (!$role
                || $role->dbname !== 'imagine_crm_ci'
                || $role->username !== self::ROLE
                || $role->row_security !== 'on'
                || $role->rolsuper || $role->rolbypassrls
                || $role->rolinherit || $role->rolcreatedb
                || $role->rolcreaterole || $role->owns
                || !$role->relrowsecurity || !$role->relforcerowsecurity
                || $role->can_read || !$role->can_insert
                || $role->can_update || $role->can_delete
                || ($role->inherited_tenant !== null
                    && $role->inherited_tenant !== '')) {
                return false;
            }

            return $db->transaction(function () use (
                $db, $nonce, $tenantRef, $claimedAt
            ): bool {
                $db->selectOne(
                    "SELECT set_config('app.tenant_id', ?, true)",
                    [$tenantRef]
                );
                // Plain INSERT, deliberately NOT ON CONFLICT: some PG16
                // privilege/RLS configurations require SELECT for ON
                // CONFLICT. The primary key rejects duplicate nonces.
                // Any unique violation aborts this unit and returns false.
                return $db->table(self::TABLE)->insert([
                    'issuer' => 'SYNTHETIC_AGENT_SERVICE',
                    'audience' => 'SYNTHETIC_CRM_API',
                    'nonce' => $nonce,
                    'tenant_ref' => $tenantRef,
                    'claimed_at' => $claimedAt,
                ]) === true;
            }, 1);
        } catch (\Throwable) {
            // Duplicate, revoked role, missing RLS/policy, backend outage,
            // lock error or malformed input all fail closed.
            return false;
        }
    }

    private static function synthetic(string $value, int $min): bool
    {
        return strlen($value) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{'.$min.',80}\z/D', $value) === 1;
    }
}
