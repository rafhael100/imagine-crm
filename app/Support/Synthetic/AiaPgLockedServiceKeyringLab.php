<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Closure;
use Illuminate\Database\Connection;

/**
 * F244: physical key revocation/rotation serialization.
 * SELECT FOR UPDATE under nonowner scoped PG role holds the exact
 * tenant/key row during original Agents Ed25519 verification AND nonce
 * insertion. UPDATE(lock_marker) is a lock-enabling column only; RLS
 * WITH CHECK(false) prevents actual key changes by the verifier.
 */
final readonly class AiaPgLockedServiceKeyringLab
{
    public function __construct(private Connection $db)
    {
    }

    /**
     * @param Closure(array<string,array{public_key:string,enabled:bool}>):bool $verify
     */
    public function withinLockedPublicKey(
        string $keyId,
        string $trustedTenant,
        Closure $verify,
    ): bool {
        $db = $this->db;
        if (getenv('AIA_F244_KEY_ROTATION_LOCK_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || $db->getDriverName() !== 'pgsql'
            || $db->getDatabaseName() !== 'imagine_crm_ci'
            || $db->getConfig('host') !== '127.0.0.1'
            || $db->getConfig('username') !== 'crm_aia_key_lock_ci'
            || $db->transactionLevel() !== 0
            || !self::synthetic($keyId) || !self::synthetic($trustedTenant)) {
            return false;
        }

        try {
            $r = $db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                    current_setting('app.tenant_id',true) AS inherited_tenant,
                    current_setting('row_security') AS rls,
                    p.rolsuper, p.rolbypassrls, p.rolinherit, p.rolcreatedb,
                    p.rolcreaterole, c.relrowsecurity, c.relforcerowsecurity,
                    pg_has_role(current_user,c.relowner,'MEMBER') AS owns,
                    has_table_privilege(current_user,c.oid,'SELECT') AS can_read,
                    has_table_privilege(current_user,c.oid,'INSERT') AS can_insert,
                    has_table_privilege(current_user,c.oid,'DELETE') AS can_delete,
                    has_column_privilege(current_user,c.oid,'lock_marker','UPDATE')
                        AS can_lock,
                    has_column_privilege(current_user,c.oid,'public_key_base64','UPDATE')
                        AS can_rotate,
                    has_column_privilege(current_user,c.oid,'revoked_at','UPDATE')
                        AS can_revoke
                 FROM pg_roles p CROSS JOIN pg_class c
                 WHERE p.rolname=current_user
                   AND c.oid='public.aia_lab_ed25519_service_keys'::regclass"
            );
            if (!$r || $r->dbname !== 'imagine_crm_ci'
                || $r->username !== 'crm_aia_key_lock_ci'
                || $r->rls !== 'on'
                || $r->rolsuper || $r->rolbypassrls || $r->rolinherit
                || $r->rolcreatedb || $r->rolcreaterole || $r->owns
                || !$r->relrowsecurity || !$r->relforcerowsecurity
                || !$r->can_read || !$r->can_lock
                || $r->can_insert || $r->can_delete
                || $r->can_rotate || $r->can_revoke
                || ($r->inherited_tenant !== null
                    && $r->inherited_tenant !== '')) {
                return false;
            }

            return $db->transaction(function () use (
                $db,$keyId,$trustedTenant,$verify
            ): bool {
                $db->selectOne(
                    "SELECT set_config('app.tenant_id', ?, true)",
                    [$trustedTenant]
                );
                $row = $db->table('public.aia_lab_ed25519_service_keys')
                    ->where('tenant_ref',$trustedTenant)
                    ->where('key_id',$keyId)
                    ->where('issuer','SYNTHETIC_AGENT_SERVICE')
                    ->where('audience','SYNTHETIC_CRM_API')
                    ->where('enabled',true)
                    ->whereNull('revoked_at')
                    ->where('revision','>=',1)
                    ->lockForUpdate()
                    ->first(['public_key_base64']);
                if (!$row || !is_string($row->public_key_base64)
                    || strlen($row->public_key_base64) !== 44) {
                    return false;
                }
                $key = base64_decode($row->public_key_base64,true);
                if (!is_string($key)
                    || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                    || !hash_equals(base64_encode($key),$row->public_key_base64)) {
                    return false;
                }
                // The verifier executes inside this transaction; remote
                // revocation/rotation of the same row must wait.
                return $verify([$keyId=>[
                    'public_key'=>$key,'enabled'=>true,
                ]]) === true;
            },1);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function synthetic(string $value): bool
    {
        return strlen($value) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D',$value) === 1;
    }
}
