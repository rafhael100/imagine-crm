<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;

/**
 * F246 — disposable CI PostgreSQL public-key reader with least privilege.
 *
 * No production lookup, service entitlement, IAM or ACL. Requires an exact
 * non-owner NOBYPASSRLS SELECT-only role and FORCE RLS source; never falls
 * back to the privileged connection. No private key is stored or returned.
 */
final readonly class AgentsPrivateRestrictedKeyReaderLab
{
    private const ROLE = 'crm_aia_f246_reader_ci';
    private const TABLE = 'public.aia_lab_service_keys_f245';

    public function __construct(private Connection $db)
    {
    }

    /**
     * @return ?array{tenant_ref:string,trusted_keys:array<string,array{public_key:string,enabled:bool}>}
     * Even success is ONLY a synthetic signature-verification input.
     */
    public function resolve(string $keyId, int $now): ?array
    {
        $db = $this->db;
        if (getenv('AIA_F246_MINPRIV_LAB') !== 'true'
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
            || $now < 0 || $now > 4102444800
            || strlen($keyId) > 90
            || preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D', $keyId) !== 1) {
            return null;
        }

        try {
            $status = $db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                    current_setting('app.tenant_id', true) AS inherited_tenant,
                    current_setting('row_security') AS row_security,
                    r.rolsuper, r.rolbypassrls, r.rolinherit, r.rolcreaterole,
                    r.rolcreatedb, c.relrowsecurity, c.relforcerowsecurity,
                    pg_has_role(current_user,c.relowner,'MEMBER') AS owns,
                    has_table_privilege(current_user,c.oid,'SELECT') AS can_read,
                    has_table_privilege(current_user,c.oid,'INSERT') AS can_insert,
                    has_table_privilege(current_user,c.oid,'UPDATE') AS can_update,
                    has_table_privilege(current_user,c.oid,'DELETE') AS can_delete
                FROM pg_roles r CROSS JOIN pg_class c
                WHERE r.rolname=current_user
                  AND c.oid='public.aia_lab_service_keys_f245'::regclass"
            );
            if (!$status || $status->dbname !== 'imagine_crm_ci'
                || $status->username !== self::ROLE
                || $status->row_security !== 'on'
                || ($status->inherited_tenant !== null
                    && $status->inherited_tenant !== '')
                || $status->rolsuper || $status->rolbypassrls
                || $status->rolinherit || $status->rolcreaterole
                || $status->rolcreatedb || $status->owns
                || !$status->relrowsecurity || !$status->relforcerowsecurity
                || !$status->can_read || $status->can_insert
                || $status->can_update || $status->can_delete) {
                return null;
            }

            // Exact global key ID is only a selector. Enterprise binding and
            // PUBLIC verifier bytes are read from a CRM-owned PG table.
            $row = $db->table(self::TABLE)
                ->where('key_id', $keyId)
                ->where('issuer', 'SYNTHETIC_AGENT_SERVICE')
                ->where('audience', 'SYNTHETIC_CRM_API')
                ->where('subject', 'SYNTHETIC_AGENTS_READ_ONLY')
                ->where('enabled', true)
                ->whereNull('revoked_at')
                ->where('not_before', '<=', $now)
                ->where('expires_at', '>', $now)
                ->where('revision', '>=', 1)
                ->first(['tenant_ref', 'public_key_base64', 'revision']);
            if (!$row || !is_string($row->tenant_ref)
                || strlen($row->tenant_ref) > 90
                || preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D',
                    $row->tenant_ref) !== 1
                || !is_string($row->public_key_base64)
                || strlen($row->public_key_base64) !== 44
                || (int)$row->revision < 1) {
                return null;
            }
            $key=base64_decode($row->public_key_base64, true);
            if (!is_string($key)
                || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                || !hash_equals(base64_encode($key),$row->public_key_base64)) {
                return null;
            }
            return [
                'tenant_ref'=>$row->tenant_ref,
                'trusted_keys'=>[
                    $keyId=>['public_key'=>$key,'enabled'=>true],
                ],
            ];
        } catch (\Throwable) {
            return null; // Deleted table, bad role/RLS, revoked or outage.
        }
    }
}
