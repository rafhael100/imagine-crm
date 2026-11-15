<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use App\Core\Identity\{GuardPrincipalSource, PrincipalReference};
use App\Core\Tenancy\{MembershipSnapshot, PostgresActiveMembershipStore};
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use ImagineAI\MockBridge\MockCrmReadRequest;

/**
 * F237: inert, server-owned delegation witness for disposable CRM/Agents CI.
 *
 * Issuance requires ACTUAL CRM test session+MFA and physical PG16 membership.
 * A later signed service probe rechecks grant, server mapping and current
 * membership version under locks. Neither method grants any CRM read.
 *
 * Requires a privileged ephemeral fixture connection ONLY to manage test
 * tables; it is not a runtime/provisioning pattern. No production routes,
 * tables, key material, IAM tokens, PII or customer effects.
 */
final readonly class AiaPgHumanDelegationWitnessLab
{
    public function __construct(private Connection $fixture)
    {
    }

    /**
     * This does NOT authorize an Agent, issue a token or transmit a session.
     * The human initiator is always read from GuardPrincipalSource under a
     * verified locked IAM session; caller cannot provide their UUID.
     */
    public function issueFromCurrentHuman(
        GuardPrincipalSource $human,
        PostgresActiveMembershipStore $memberships,
        string $organizationId,
        string $canonicalRequest,
        string $keyId,
        int $now,
    ): bool {
        if (!self::labSafe($this->fixture)
            || !Str::isUuid($organizationId)
            || !self::synthetic($keyId)
            || $now < 0 || $now > 4102444500) {
            return false;
        }

        try {
            $request = MockCrmReadRequest::decodeCanonicalJson($canonicalRequest);
            if ($request['operation'] !== 'crm.lead.read_minimal') {
                return false;
            }

            return $this->fixture->transaction(function () use (
                $human, $memberships, $organizationId, $request,
                $canonicalRequest, $keyId, $now
            ): bool {
                $binding = $this->binding(
                    $request['tenant_ref'], $organizationId,
                    $request['principal_ref'], $keyId, true,
                );
                if ($binding === null
                    || !self::fieldsMatch($binding->allowed_fields_json, $request['fields'])) {
                    return false;
                }

                return $human->withinCurrent(
                    function (PrincipalReference $verified) use (
                        $memberships, $organizationId, $request,
                        $canonicalRequest, $keyId, $now
                    ): bool {
                        return $memberships->within(
                            $verified, $organizationId,
                            function (MembershipSnapshot $membership) use (
                                $organizationId, $request, $canonicalRequest,
                                $keyId, $now
                            ): bool {
                                $this->fixture
                                    ->table('public.aia_lab_human_service_grants')
                                    ->insert([
                                        'grant_id' => (string) Str::uuid(),
                                        'tenant_ref' => $request['tenant_ref'],
                                        'organization_id' => strtolower($organizationId),
                                        'key_id' => $keyId,
                                        'principal_ref' => $request['principal_ref'],
                                        'human_identity_id' => $membership->principalId,
                                        'membership_version' => $membership->version,
                                        'request_sha256' => hash('sha256', $canonicalRequest),
                                        'purpose_ref' => $request['purpose_ref'],
                                        'resource_ref' => $request['resource_ref'],
                                        'issued_at' => $now,
                                        'expires_at' => $now + 30,
                                        'revoked_at' => null,
                                        'version' => 1,
                                    ]);
                                return true;
                            }
                        );
                    },
                    true, // recent MFA: no proof of user from Agent message
                );
            }, 1);
        } catch (\Throwable) {
            // Wrong principal, expired session, revoked membership, duplicate,
            // failed DB or unsafe fixture environment: never mint witness.
            return false;
        }
    }

    /**
     * Service-side laboratory probe: original Ed25519, CRM keyring and
     * PG nonce checked FIRST. Then verify server-issued human grant and
     * re-read physical membership under lock. "true" is only diagnostic.
     */
    public function matchesSignedServiceWitness(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signature,
        string $expectedTenant,
        int $now,
        AiaPgServiceKeyringLab $keyring,
        AiaPgNonceCandidateLedger $nonceLedger,
        PostgresActiveMembershipStore $memberships,
    ): bool {
        if (!self::labSafe($this->fixture)
            || !self::synthetic($expectedTenant)
            || $now < 0 || $now > 4102444800) {
            return false;
        }

        try {
            if (!AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $canonicalRequest, $canonicalClaims, $signature,
                $expectedTenant, $now, $keyring, $nonceLedger
            )) {
                return false;
            }

            $request = MockCrmReadRequest::decodeCanonicalJson($canonicalRequest);
            $claims = json_decode($canonicalClaims, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($claims) || !self::synthetic($claims['key_id'] ?? null)
                || $request['tenant_ref'] !== $expectedTenant) {
                return false;
            }

            // Deliberately lock binding and grant on CRM-owned connection:
            // a concurrent UPDATE revocation cannot commit between the
            // grant check and the physical membership recheck.
            return $this->fixture->transaction(function () use (
                $request, $canonicalRequest, $claims, $expectedTenant,
                $now, $memberships
            ): bool {
                $grant = $this->fixture
                    ->table('public.aia_lab_human_service_grants')
                    ->where('tenant_ref', $expectedTenant)
                    ->where('key_id', $claims['key_id'])
                    ->where('principal_ref', $request['principal_ref'])
                    ->where('request_sha256', hash('sha256', $canonicalRequest))
                    ->where('purpose_ref', $request['purpose_ref'])
                    ->where('resource_ref', $request['resource_ref'])
                    ->whereNull('revoked_at')
                    ->where('issued_at', '<=', $now)
                    ->where('expires_at', '>', $now)
                    ->lockForUpdate()
                    ->first([
                        'organization_id','human_identity_id','membership_version',
                        'version',
                    ]);
                if (!$grant || (int) $grant->version < 1
                    || (int) $grant->membership_version < 1
                    || !Str::isUuid($grant->organization_id)
                    || !Str::isUuid($grant->human_identity_id)) {
                    return false;
                }

                $binding = $this->binding(
                    $expectedTenant, $grant->organization_id,
                    $request['principal_ref'], $claims['key_id'], true,
                );
                if ($binding === null
                    || !self::fieldsMatch($binding->allowed_fields_json, $request['fields'])) {
                    return false;
                }

                // Real PG16 membership re-read on non-superuser FORCE-RLS
                // connection; stale or revoked membership blocks the probe.
                // Human cookies/session are NEVER sent to the Agents.
                return $memberships->within(
                    new PrincipalReference(strtolower($grant->human_identity_id)),
                    strtolower($grant->organization_id),
                    static fn (MembershipSnapshot $snapshot): bool =>
                        $snapshot->version === (int) $grant->membership_version,
                );
            }, 1);
        } catch (\Throwable) {
            return false; // zero business effects on any error
        }
    }

    /** @return ?object */
    private function binding(
        string $tenantRef,
        string $organizationId,
        string $principalRef,
        string $keyId,
        bool $lock,
    ): ?object {
        $query = $this->fixture
            ->table('public.aia_lab_agent_human_bindings')
            ->where('tenant_ref', $tenantRef)
            ->where('organization_id', strtolower($organizationId))
            ->where('principal_ref', $principalRef)
            ->where('key_id', $keyId)
            ->where('enabled', true)
            ->whereNull('revoked_at')
            ->where('revision', '>=', 1);
        return ($lock ? $query->lockForUpdate() : $query)->first([
            'allowed_fields_json'
        ]);
    }

    /** @param array<int,string> $requested */
    private static function fieldsMatch(string $json, array $requested): bool
    {
        try {
            $allowed = json_decode($json, true, 3, JSON_THROW_ON_ERROR);
            if (!is_array($allowed) || !array_is_list($allowed)
                || count($allowed) < 1 || count($allowed) > 2
                || count(array_unique($allowed)) !== count($allowed)
                || array_diff($allowed, ['ref', 'stage']) !== []
                || count($requested) < 1 || count($requested) > 2) {
                return false;
            }
            return array_diff($requested, $allowed) === [];
        } catch (\Throwable) {
            return false;
        }
    }

    private static function synthetic(mixed $value): bool
    {
        return is_string($value)
            && strlen($value) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D', $value) === 1;
    }

    private static function labSafe(Connection $db): bool
    {
        if (getenv('AIA_F237_DELEGATION_LAB') !== 'true'
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
            || $db->getConfig('username') !== 'postgres'
            || $db->transactionLevel() !== 0) {
            return false;
        }
        try {
            $who = $db->selectOne(
                'SELECT current_database() AS dbname, current_user AS username'
            );
            return $who && $who->dbname === 'imagine_crm_ci'
                && $who->username === 'postgres';
        } catch (\Throwable) {
            return false;
        }
    }
}
