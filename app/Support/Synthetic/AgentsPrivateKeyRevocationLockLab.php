<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Closure;
use Illuminate\Database\Connection;

/**
 * F248 — transaction-scoped row lock for a synthetic, CRM-owned public key.
 * The CI-only PostgreSQL role may SELECT and UPDATE(lock_marker) but its
 * FORCE RLS policy WITH CHECK(false) prohibits actual row mutation.
 *
 * This is NOT peer authentication, production IAM or a grant to read leads.
 */
final readonly class AgentsPrivateKeyRevocationLockLab
{
    private const ROLE = 'crm_aia_keylock_f248_ci';
    private const TABLE = 'public.aia_lab_service_keys_f248';

    public function __construct(private Connection $db)
    {
    }

    /**
     * Callback MUST finish inside the key's transaction and row lock.
     * Only original synthetic verification + disposable nonce claim belongs
     * in the callback; a true result is evidence, never authorization.
     *
     * @param Closure(array{tenant_ref:string,trusted_keys:array<string,array{public_key:string,enabled:bool}>}):bool $evaluate
     */
    public function withLockedKey(string $keyId, int $now, Closure $evaluate): bool
    {
        $db=$this->db;
        if (getenv('AIA_F248_ROW_LOCK_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || $db->getDriverName() !== 'pgsql'
            || $db->getDatabaseName() !== 'imagine_crm_ci'
            || $db->getConfig('host') !== '127.0.0.1'
            || $db->getConfig('username') !== self::ROLE
            || $db->transactionLevel() !== 0
            || $now < 0 || $now > 4102444800
            || strlen($keyId) > 90
            || preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D',$keyId) !== 1) {
            return false;
        }

        try {
            $rights=$db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                    current_setting('row_security') AS rls,
                    row_security_active('public.aia_lab_service_keys_f248'::regclass)
                        AS active,
                    p.rolsuper, p.rolbypassrls, p.rolinherit,
                    p.rolcreatedb, p.rolcreaterole,
                    c.relrowsecurity, c.relforcerowsecurity,
                    pg_has_role(current_user,c.relowner,'MEMBER') AS owns,
                    has_table_privilege(current_user,c.oid,'SELECT') AS can_read,
                    has_table_privilege(current_user,c.oid,'INSERT') AS can_insert,
                    has_table_privilege(current_user,c.oid,'DELETE') AS can_delete,
                    has_column_privilege(current_user,c.oid,'lock_marker','UPDATE')
                        AS can_lock,
                    has_column_privilege(current_user,c.oid,'tenant_ref','UPDATE')
                        AS can_change_tenant,
                    has_column_privilege(current_user,c.oid,'public_key_base64','UPDATE')
                        AS can_replace_key,
                    has_column_privilege(current_user,c.oid,'revoked_at','UPDATE')
                        AS can_revoke
                FROM pg_roles p CROSS JOIN pg_class c
                WHERE p.rolname=current_user AND c.oid='public.aia_lab_service_keys_f248'::regclass"
            );
            if (!$rights || $rights->dbname !== 'imagine_crm_ci'
                || $rights->username !== self::ROLE
                || $rights->rls !== 'on'
                || !$rights->active || !$rights->relrowsecurity
                || !$rights->relforcerowsecurity
                || $rights->rolsuper || $rights->rolbypassrls
                || $rights->rolinherit || $rights->rolcreatedb
                || $rights->rolcreaterole || $rights->owns
                || !$rights->can_read || !$rights->can_lock
                || $rights->can_insert || $rights->can_delete
                || $rights->can_change_tenant || $rights->can_replace_key
                || $rights->can_revoke) {
                return false;
            }

            return $db->transaction(function () use (
                $db,$keyId,$now,$evaluate
            ): bool {
                $row=$db->table(self::TABLE)
                    ->where('key_id',$keyId)
                    ->where('issuer','SYNTHETIC_AGENT_SERVICE')
                    ->where('audience','SYNTHETIC_CRM_API')
                    ->where('subject','SYNTHETIC_AGENTS_READ_ONLY')
                    ->where('enabled',true)
                    ->whereNull('revoked_at')
                    ->where('not_before','<=',$now)
                    ->where('expires_at','>',$now)
                    ->where('revision','>=',1)
                    ->lockForUpdate()
                    ->first(['tenant_ref','public_key_base64']);

                if (!$row || !is_string($row->tenant_ref)
                    || strlen($row->tenant_ref) > 90
                    || preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D',$row->tenant_ref) !== 1
                    || !is_string($row->public_key_base64)
                    || strlen($row->public_key_base64) !== 44) {
                    return false;
                }
                $public=base64_decode($row->public_key_base64,true);
                if (!is_string($public)
                    || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                    || !hash_equals(base64_encode($public),$row->public_key_base64)) {
                    return false;
                }
                // The caller's Ed25519 + PG nonce proof happens before
                // PostgreSQL releases the row lock on this exact key.
                return $evaluate([
                    'tenant_ref'=>$row->tenant_ref,
                    'trusted_keys'=>[
                        $keyId=>['public_key'=>$public,'enabled'=>true],
                    ],
                ]) === true;
            },1);
        } catch (\Throwable) {
            return false; // Lock timeout, revoked key, RLS drift: deny.
        }
    }
}
