<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;
use ImagineAI\MockBridge\MockCrmReadRequest;

/**
 * F233: read CRM-owned, disposable PostgreSQL *candidate* scope facts.
 *
 * This NEVER grants business access. Only a proof of lab policy conformance
 * after service signature, trusted key and atomic nonce were verified.
 * No production schema, no PII, no real membership, no CRM lead reads.
 */
final readonly class AiaPgServiceScopeLab
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * This is not an ACL decision. No caller may convert true to ALLOW.
     * The controller/response boundary remains unconditional DENIAL.
     *
     * @param array<string,mixed> $signedClaims Already verified by the
     *   original Agents Ed25519 implementation in the calling bridge.
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
            || $this->connection->getConfig('username') !== 'postgres'
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
            $who = $this->connection->selectOne(
                'SELECT current_database() AS dbname, current_user AS username'
            );
            if (!$who || $who->dbname !== 'imagine_crm_ci'
                || $who->username !== 'postgres') {
                return false;
            }

            // The caller's assertions are only selectors; all permissions
            // and owner facts must exist in separate CRM-owned tables.
            $delegation = $this->connection
                ->table('public.aia_lab_service_delegations')
                ->where('tenant_ref', $expectedTenant)
                ->where('key_id', $keyId)
                ->where('principal_ref', $request['principal_ref'])
                ->where('purpose_ref', $request['purpose_ref'])
                ->where('operation', 'crm.lead.read_minimal')
                ->where('enabled', true)
                ->whereNull('revoked_at')
                ->where('not_before', '<=', $now)
                ->where('expires_at', '>', $now)
                ->first(['revision','enabled','revoked_at']);
            if (!$delegation || (int) $delegation->revision < 1) {
                return false;
            }

            $owner = $this->connection
                ->table('public.aia_lab_resource_owners')
                ->where('tenant_ref', $expectedTenant)
                ->where('resource_ref', $request['resource_ref'])
                ->where('principal_ref', $request['principal_ref'])
                ->where('status', 'active')
                ->exists();
            if (!$owner) {
                return false;
            }

            $fields = $request['fields'];
            if (!is_array($fields) || count($fields) < 1 || count($fields) > 2
                || count(array_unique($fields)) !== count($fields)
                || array_diff($fields, ['ref','stage']) !== []) {
                return false;
            }

            $approved = $this->connection
                ->table('public.aia_lab_service_field_grants')
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
        } catch (\Throwable) {
            // Missing owner/delegation/field table, RLS or DB outage -> deny.
            return false;
        }
    }

    private static function syntheticRef(string $ref, int $min): bool
    {
        return strlen($ref) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{'.$min.',80}\z/D', $ref) === 1;
    }
}
