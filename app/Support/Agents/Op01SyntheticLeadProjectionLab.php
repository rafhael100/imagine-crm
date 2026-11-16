<?php

declare(strict_types=1);

namespace App\Support\Agents;

use Illuminate\Database\Connection;

/**
 * OP-01 P2: a disposable PostgreSQL 16 projection experiment.
 *
 * Never an HTTP adapter, a business authorizer, or a tenant authenticator.
 * A "trusted" tenant argument is synthetic test data only; real identity and
 * membership would need independent CORE/IAM/SEG approval before any use.
 *
 * No instance can return a result outside this repo's disposable GitHub CI.
 */
final readonly class Op01SyntheticLeadProjectionLab
{
    private const TABLE = 'public.aia_lab_op01_leads_p2';
    private const ROLE = 'crm_aia_leadreader_p2_ci';
    private const FIELDS = ['ref', 'stage'];
    private const STAGES = ['new', 'qualified', 'proposal', 'won', 'lost'];

    public function __construct(private Connection $db)
    {
    }

    /**
     * Strictly scoped projection of one synthetic row under a SELECT-only
     * unprivileged role with FORCE RLS. Its returned data NEVER signifies
     * an operational authorization, entitlement or consent.
     *
     * @param list<string> $requestedFields
     * @return ?array<string,string>
     */
    public function project(string $trustedTenant, string $resourceRef, array $requestedFields): ?array
    {
        $db = $this->db;
        if (getenv('AIA_OP01_P2_PG_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || $db->getDriverName() !== 'pgsql'
            || $db->getDatabaseName() !== 'imagine_crm_ci'
            || $db->getConfig('host') !== '127.0.0.1'
            || $db->getConfig('username') !== self::ROLE
            || $db->transactionLevel() !== 0
            || !self::syntheticRef($trustedTenant)
            || !self::syntheticRef($resourceRef)
            || !array_is_list($requestedFields)
            || count($requestedFields) < 1 || count($requestedFields) > 2
            || count(array_filter($requestedFields,static fn (mixed $field): bool => !is_string($field))) > 0
            || count(array_unique($requestedFields)) !== count($requestedFields)
            || count(array_diff($requestedFields, self::FIELDS)) > 0
        ) {
            return null;
        }

        try {
            return $db->transaction(function () use ($db, $trustedTenant, $resourceRef, $requestedFields): ?array {
                $privileges = $db->selectOne(
                    "SELECT current_database() AS dbname, current_user AS username,
                        current_setting('row_security') AS row_security,
                        row_security_active('public.aia_lab_op01_leads_p2'::regclass) AS rls_active,
                        r.rolsuper, r.rolbypassrls, r.rolinherit, r.rolcreatedb, r.rolcreaterole,
                        c.relrowsecurity, c.relforcerowsecurity,
                        pg_has_role(current_user,c.relowner,'MEMBER') AS owns_table,
                        has_column_privilege(current_user,c.oid,'tenant_ref','SELECT') AS can_scope,
                        has_column_privilege(current_user,c.oid,'ref','SELECT') AS can_read_ref,
                        has_column_privilege(current_user,c.oid,'stage','SELECT') AS can_read_stage,
                        has_column_privilege(current_user,c.oid,'contact_email','SELECT') AS can_read_email,
                        has_column_privilege(current_user,c.oid,'secret_note','SELECT') AS can_read_note,
                        has_table_privilege(current_user,c.oid,'INSERT') AS can_insert,
                        has_table_privilege(current_user,c.oid,'UPDATE') AS can_update,
                        has_table_privilege(current_user,c.oid,'DELETE') AS can_delete
                    FROM pg_roles r CROSS JOIN pg_class c
                    WHERE r.rolname=current_user AND c.oid='public.aia_lab_op01_leads_p2'::regclass"
                );
                if (!$privileges
                    || $privileges->dbname !== 'imagine_crm_ci'
                    || $privileges->username !== self::ROLE
                    || $privileges->row_security !== 'on'
                    || !$privileges->rls_active
                    || !$privileges->relrowsecurity
                    || !$privileges->relforcerowsecurity
                    || $privileges->rolsuper || $privileges->rolbypassrls
                    || $privileges->rolinherit || $privileges->rolcreatedb
                    || $privileges->rolcreaterole || $privileges->owns_table
                    || !$privileges->can_scope || !$privileges->can_read_ref
                    || !$privileges->can_read_stage
                    || $privileges->can_read_email || $privileges->can_read_note
                    || $privileges->can_insert || $privileges->can_update
                    || $privileges->can_delete) {
                    return null;
                }

                // Inherited tenant context from a reused connection is a
                // contamination signal; never silently overwrite it.
                $previous = $db->selectOne(
                    "SELECT current_setting('app.tenant_id', true) AS tenant"
                );
                if (!$previous || ($previous->tenant !== null && $previous->tenant !== '')) {
                    return null;
                }

                // RLS adds a second filter; session tenant markers alone are
                // NOT authentication. Here the input is a CI-only fixture.
                $db->selectOne(
                    "SELECT set_config('app.tenant_id', ?, true) AS assigned",
                    [$trustedTenant]
                );
                $row = $db->table(self::TABLE)
                    ->where('tenant_ref', $trustedTenant)
                    ->where('ref', $resourceRef)
                    ->first(['ref', 'stage']);

                if (!$row || !is_string($row->ref)
                    || $row->ref !== $resourceRef
                    || !in_array($row->stage, self::STAGES, true)) {
                    return null;
                }
                $result = [];
                // Canonical output ordering prevents request-order variance.
                foreach (self::FIELDS as $field) {
                    if (in_array($field, $requestedFields, true)) {
                        $result[$field] = $row->{$field};
                    }
                }
                return $result;
            }, 1);
        } catch (\Throwable) {
            // Database faults, privilege drift or invalid rows all deny.
            return null;
        }
    }

    private static function syntheticRef(string $ref): bool
    {
        return strlen($ref) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D', $ref) === 1;
    }
}
