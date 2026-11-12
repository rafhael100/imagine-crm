<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Identity\PrincipalReference;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use LogicException;

/** Candidate F1 adapter for the synthetic membership schema, not business ACL. */
final readonly class PostgresActiveMembershipStore implements ActiveMembershipStore
{
    public function __construct(private Connection $connection) {}

    public function within(PrincipalReference $principal, string $tenantId, Closure $operation): mixed
    {
        if (!Str::isUuid($tenantId) || !Str::isUuid($principal->principalId)) {
            throw new MembershipDenied();
        }

        $db = $this->connection;
        if ($db->getDriverName() !== 'pgsql' || $db->transactionLevel() !== 0) {
            throw new LogicException('A dedicated PostgreSQL transaction is required.');
        }

        return $db->transaction(function () use ($db, $principal, $tenantId, $operation): mixed {
            // Never trust an inherited session marker or a privileged runtime role.
            // Qualify BOTH metadata and data lookup: an untrusted search_path
            // or temporary-table shadow must not replace the tenant table.
            $state = $db->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant,
                r.rolsuper, r.rolbypassrls,
                pg_has_role(current_user, c.relowner, 'MEMBER') AS owns_table,
                c.relrowsecurity, c.relforcerowsecurity
                FROM pg_roles r CROSS JOIN pg_class c
                WHERE r.rolname = current_user AND c.oid = 'public.crm_memberships'::regclass");

            if ($state && $state->tenant !== null && $state->tenant !== '') {
                throw new ContaminatedTenantConnection();
            }
            if (!$state
                || $state->rolsuper || $state->rolbypassrls || $state->owns_table
                || !$state->relrowsecurity || !$state->relforcerowsecurity) {
                throw new LogicException('Unsafe tenant connection.');
            }

            // Candidate scope for this one IAM lookup ONLY. This is not authentication.
            // No business callback is reached until principal + membership + ACL pass.
            $tenantId = strtolower($tenantId);
            $db->selectOne("SELECT set_config('app.tenant_id', ?, true)", [$tenantId]);
            $membership = $db->table('public.crm_memberships')
                ->where('organization_id', $tenantId)
                ->where('global_identity_id', $principal->principalId)
                ->lockForUpdate()->first();

            if (!$membership || $membership->status !== MembershipStatus::Active->value
                || (int) $membership->version < 1) {
                throw new MembershipDenied();
            }

            // Lock serializes revocation with this unit; every new unit re-reads.
            return $operation(new MembershipSnapshot(
                $tenantId, $principal->principalId, MembershipStatus::Active,
                (int) $membership->version,
            ));
        }, 1); // No automatic replay of effects.
    }
}
