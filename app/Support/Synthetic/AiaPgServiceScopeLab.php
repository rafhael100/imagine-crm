<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;
use ImagineAI\MockBridge\MockCrmReadRequest;

/**
 * F234: test-only, read-only PostgreSQL RLS-backed scope witness.
 *
 * Queries are executed ONLY under dedicated non-owner, NOBYPASSRLS,
 * SELECT-only CI role with transaction-local tenant marker. No real
 * business authorization, human delegation, lead data or production use.
 */
final readonly class AiaPgServiceScopeLab
{
    private const ROLE = 'crm_aia_scope_ci';

    public function __construct(private Connection $connection)
    {
    }

    /**
     * "true" is ONLY a synthetic policy diagnostic. Never turn it into
     * CRM ALLOW; the public read response remains uniform DENIAL.
     *
     * @param array<string,mixed> $signedClaims Already Ed25519-verified by
     *  the original pinned Agents implementation in the calling bridge.
     */
    public function matchesCandidate(
        string $canonicalRequest,
        array $signedClaims,
        string $expectedTenant,
        int $now,
    ): bool {
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || $this->connection->getDriverName() !== 'pgsql'
            || $this->connection->getDatabaseName() !== 'imagine_crm_ci'
            || $this->connection->getConfig('host') !== '127.0.0.1'
            || $this->connection->getConfig('username') !== self::ROLE
            || $this->connection->transactionLevel() !== 0
            || $now < 0 || $now > 4102444800) {
            return false;
        }

        try {
            $request = MockCrmReadRequest::decodeCanonicalJson($canonicalRequest);
            $keyId = $signedClaims['key_id'] ?? null;
            if (!is_string($keyId)
                || !self::syntheticRef($keyId, 3)
                || !self::syntheticRef($expectedTenant, 1)
                || $request['tenant_ref'] !== $expectedTenant
                || ($signedClaims['tenant_ref'] ?? null) !== $expectedTenant
                || ($signedClaims['issuer'] ?? null) !== 'SYNTHETIC_AGENT_SERVICE'
                || ($signedClaims['subject'] ?? null) !== 'SYNTHETIC_AGENTS_READ_ONLY'
                || ($signedClaims['audience'] ?? null) !== 'SYNTHETIC_CRM_API'
                || ($signedClaims['operation'] ?? null) !== 'crm.lead.read_minimal'
                || $request['operation'] !== 'crm.lead.read_minimal'
                || !self::syntheticRef($request['principal_ref'], 1)
                || !self::syntheticRef($request['resource_ref'], 1)
                || !self::syntheticRef($request['purpose_ref'], 1)) {
                return false;
            }
            $db = $this->connection;
            $who = $db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                    current_setting('app.tenant_id', true) AS inherited_tenant,
                    current_setting('row_security') AS row_security,
                    r.rolsuper, r.rolbypassrls
                 FROM pg_roles r WHERE r.rolname = current_user"
            );
            if (!$who || $who->dbname !== 'imagine_crm_ci'
                || $who->username !== self::ROLE
                || $who->rolsuper || $who->rolbypassrls
                || $who->row_security !== 'on'
                || ($who->inherited_tenant !== null
                    && $who->inherited_tenant !== '')) {
                return false;
            }

            // Require all three exact tables to enforce FORCE RLS and
            // deny any role that might bypass by owning them.
            $flags = $db->selectOne(
                "SELECT count(*) AS count,
                    bool_and(c.relrowsecurity AND c.relforcerowsecurity
                        AND NOT pg_has_role(current_user,c.relowner,'MEMBER')) AS safe
                 FROM pg_class c WHERE c.oid IN (
                    'public.aia_lab_service_delegations'::regclass,
                    'public.aia_lab_resource_owners'::regclass,
                    'public.aia_lab_service_field_grants'::regclass
                 )"
            );
            if (!$flags || (int) $flags->count !== 3
                || ($flags->safe !== true && $flags->safe !== 't')) {
                return false;
            }

            return $db->transaction(function () use (
                $db, $request, $signedClaims, $expectedTenant, $now, $keyId
            ): bool {
                // The only company marker is derived from the verified
                // expected tenant, not an HTTP header or arbitrary claim.
                $db->selectOne(
                    "SELECT set_config('app.tenant_id', ?, true) AS tenant",
                    [$expectedTenant],
                );

                $delegation = $db->table('public.aia_lab_service_delegations')
                    ->where('tenant_ref', $expectedTenant)
                    ->where('key_id', $keyId)
                    ->where('principal_ref', $request['principal_ref'])
                    ->where('purpose_ref', $request['purpose_ref'])
                    ->where('operation', 'crm.lead.read_minimal')
                    ->where('enabled', true)
                    ->whereNull('revoked_at')
                    ->where('not_before', '<=', $now)
                    ->where('expires_at', '>', $now)
                    ->first(['revision']);
                if (!$delegation || (int) $delegation->revision < 1) {
                    return false;
                }

                $owner = $db->table('public.aia_lab_resource_owners')
                    ->where('tenant_ref', $expectedTenant)
                    ->where('resource_ref', $request['resource_ref'])
                    ->where('principal_ref', $request['principal_ref'])
                    ->where('status', 'active')
                    ->exists();
                if (!$owner) {
                    return false;
                }

                $fields = $request['fields'];
                if (!is_array($fields) || count($fields) < 1
                    || count($fields) > 2
                    || count(array_unique($fields)) !== count($fields)
                    || array_diff($fields, ['ref', 'stage']) !== []) {
                    return false;
                }
                $approved = $db->table('public.aia_lab_service_field_grants')
                    ->where('tenant_ref', $expectedTenant)
                    ->where('key_id', $keyId)
                    ->where('principal_ref', $request['principal_ref'])
                    ->where('resource_ref', $request['resource_ref'])
                    ->whereIn('field_code', $fields)
                    ->where('enabled', true)
                    ->pluck('field_code')
                    ->all();
                sort($approved, SORT_STRING);
                sort($fields, SORT_STRING);
                return $approved === $fields;
            }, 1); // no automatic retry of a protected operation
        } catch (\Throwable) {
            // Absent relation, stale tenant, transaction, RLS or backend
            // outage always deny; never fall back to privileged connection.
            return false;
        }
    }

    private static function syntheticRef(string $ref, int $min): bool
    {
        return strlen($ref) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{'.$min.',80}\z/D', $ref) === 1;
    }
}
