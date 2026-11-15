<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/**
 * F242: a key_id selects a CRM-owned, globally unique service->tenant binding.
 * No caller-provided tenant, SQL tenant marker, or JWT claim is authoritative.
 *
 * Restricted, nonowner SELECT-only PG16 role; forced RLS. The lookup itself
 * authenticates NOTHING. Only the SHA-pinned Agents Ed25519 verifier, using
 * the tenant's corresponding CRM-owned public key, can validate the peer.
 * All tables, roles and identities are disposable synthetic GitHub CI only.
 */
final readonly class AiaPgServiceTenantBindingLab
{
    private const TABLE = 'public.aia_lab_service_tenant_bindings';
    private const ROLE = 'crm_aia_peer_router_ci';

    public function __construct(private Connection $db)
    {
    }

    /** @return ?array{tenant_ref:string,organization_id:string} */
    public function resolveByKeyId(string $keyId): ?array
    {
        $db = $this->db;
        if (getenv('AIA_F242_PEER_ROUTING_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_USERNAME') !== 'postgres'
            || $db->getDriverName() !== 'pgsql'
            || $db->getDatabaseName() !== 'imagine_crm_ci'
            || $db->getConfig('host') !== '127.0.0.1'
            || $db->getConfig('username') !== self::ROLE
            || $db->transactionLevel() !== 0
            || !self::synthetic($keyId)) {
            return null;
        }
        try {
            $role = $db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                    current_setting('app.tenant_id',true) AS inherited_tenant,
                    current_setting('row_security') AS rls,
                    r.rolsuper, r.rolbypassrls, r.rolinherit,
                    r.rolcreaterole, r.rolcreatedb,
                    c.relrowsecurity, c.relforcerowsecurity,
                    pg_has_role(current_user,c.relowner,'MEMBER') AS owns,
                    has_table_privilege(current_user,c.oid,'SELECT') AS can_read,
                    has_table_privilege(current_user,c.oid,'INSERT') AS can_insert,
                    has_table_privilege(current_user,c.oid,'UPDATE') AS can_update,
                    has_table_privilege(current_user,c.oid,'DELETE') AS can_delete
                FROM pg_roles r CROSS JOIN pg_class c
                WHERE r.rolname=current_user
                    AND c.oid='public.aia_lab_service_tenant_bindings'::regclass"
            );
            if (!$role
                || $role->dbname !== 'imagine_crm_ci'
                || $role->username !== self::ROLE || $role->rls !== 'on'
                || $role->rolsuper || $role->rolbypassrls
                || $role->rolinherit || $role->rolcreaterole
                || $role->rolcreatedb || $role->owns
                || !$role->relrowsecurity || !$role->relforcerowsecurity
                || !$role->can_read || $role->can_insert
                || $role->can_update || $role->can_delete
                || ($role->inherited_tenant !== null
                    && $role->inherited_tenant !== '')) {
                return null;
            }

            // Tenant is intentionally NOT set: it has yet to be derived.
            // Unique(key_id) is a CRM-side invariant, not a client promise.
            $row = $db->table(self::TABLE)
                ->where('key_id', $keyId)
                ->where('issuer', 'SYNTHETIC_AGENT_SERVICE')
                ->where('audience', 'SYNTHETIC_CRM_API')
                ->where('subject', 'SYNTHETIC_AGENTS_READ_ONLY')
                ->where('enabled', true)
                ->whereNull('revoked_at')
                ->where('revision', '>=', 1)
                ->first(['tenant_ref', 'organization_id']);
            if (!$row || !self::synthetic($row->tenant_ref)
                || !Str::isUuid($row->organization_id)) {
                return null;
            }
            return [
                'tenant_ref' => $row->tenant_ref,
                'organization_id' => strtolower($row->organization_id),
            ];
        } catch (\Throwable) {
            return null; // key revoked, RLS removed, backend failure => DENY
        }
    }

    private static function synthetic(mixed $value): bool
    {
        return is_string($value)
            && strlen($value) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D', $value) === 1;
    }
}
