<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/**
 * F243: linearize use of CRM-owned service->tenant binding versus revocation.
 * The row remains FOR UPDATE locked during the signed service proof and nonce
 * claim callback. Failure, timeout or revocation => null (NO ALLOW).
 *
 * Separate, ephemeral PostgreSQL role. UPDATE(lock_marker) is granted only
 * to permit SELECT FOR UPDATE; RLS WITH CHECK(false) prohibits mutations.
 */
final readonly class AiaPgLockedServiceTenantBindingLab
{
    public function __construct(private Connection $db)
    {
    }

    /**
     * @param Closure(array{tenant_ref:string,organization_id:string}):bool $verify
     * @return ?array{tenant_ref:string,organization_id:string}
     */
    public function withinLockedBinding(string $keyId, Closure $verify): ?array
    {
        $db = $this->db;
        if (getenv('AIA_F243_BINDING_LOCK_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_USERNAME') !== 'postgres'
            || $db->getDriverName() !== 'pgsql'
            || $db->getDatabaseName() !== 'imagine_crm_ci'
            || $db->getConfig('host') !== '127.0.0.1'
            || $db->getConfig('username') !== 'crm_aia_peer_lock_ci'
            || $db->transactionLevel() !== 0
            || strlen($keyId) > 90
            || preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D', $keyId) !== 1) {
            return null;
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
                    has_column_privilege(current_user,c.oid,'tenant_ref','UPDATE')
                        AS can_change_tenant,
                    has_column_privilege(current_user,c.oid,'revoked_at','UPDATE')
                        AS can_revoke
                 FROM pg_roles p CROSS JOIN pg_class c
                 WHERE p.rolname=current_user
                    AND c.oid='public.aia_lab_service_tenant_bindings'::regclass"
            );
            if (!$r || $r->dbname !== 'imagine_crm_ci'
                || $r->username !== 'crm_aia_peer_lock_ci'
                || $r->rls !== 'on'
                || $r->rolsuper || $r->rolbypassrls || $r->rolinherit
                || $r->rolcreatedb || $r->rolcreaterole || $r->owns
                || !$r->relrowsecurity || !$r->relforcerowsecurity
                || !$r->can_read || !$r->can_lock
                || $r->can_insert || $r->can_delete
                || $r->can_change_tenant || $r->can_revoke
                || ($r->inherited_tenant !== null
                    && $r->inherited_tenant !== '')) {
                return null;
            }

            return $db->transaction(function () use ($db,$keyId,$verify): ?array {
                // No SQL tenant GUC here: mapping itself is globally unique
                // by key_id and is locked until signed proof finishes.
                $row = $db->table('public.aia_lab_service_tenant_bindings')
                    ->where('key_id',$keyId)
                    ->where('issuer','SYNTHETIC_AGENT_SERVICE')
                    ->where('audience','SYNTHETIC_CRM_API')
                    ->where('subject','SYNTHETIC_AGENTS_READ_ONLY')
                    ->where('enabled',true)
                    ->whereNull('revoked_at')
                    ->where('revision','>=',1)
                    ->lockForUpdate()
                    ->first(['tenant_ref','organization_id']);
                if (!$row || !is_string($row->tenant_ref)
                    || preg_match(
                        '/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D',
                        $row->tenant_ref
                    ) !== 1
                    || !Str::isUuid($row->organization_id)) {
                    return null;
                }
                $bound = [
                    'tenant_ref'=>$row->tenant_ref,
                    'organization_id'=>strtolower($row->organization_id),
                ];
                // Original Agents verifier and nonce claim must run INSIDE
                // this callback, not after transaction commit.
                return $verify($bound) === true ? $bound : null;
            },1);
        } catch (\Throwable) {
            return null;
        }
    }
}
