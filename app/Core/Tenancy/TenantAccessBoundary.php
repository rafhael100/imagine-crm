<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\Authorizer;
use App\Core\Identity\GuardPrincipalSource;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

/** Only entry point for future protected human effects. No HTTP route exposes it. */
final readonly class TenantAccessBoundary
{
    public function __construct(
        private GuardPrincipalSource $principals,
        private ActiveMembershipStore $memberships,
        private Authorizer $authorizer,
    ) {}

    public function execute(string $tenantId, string $resource, string $action, Closure $effect, ?string $field = null): mixed
    {
        // Neither principal IDs, request user resolvers nor snapshots are arguments.
        $principal = $this->principals->current();

        return $this->memberships->within($principal, $tenantId, function (MembershipSnapshot $membership) use ($principal, $tenantId, $resource, $action, $field, $effect): mixed {
            $context = (new TenantContextSelector())->select(
                $principal, $membership, strtolower($tenantId), (string) Str::uuid(),
            );
            if ($context === null) {
                throw new AuthorizationException('Tenant access denied.');
            }

            $decision = $this->authorizer->decide(new AccessRequest($context, $resource, $action, $field));
            if (!$decision->allowed) {
                throw new AuthorizationException('Protected operation denied.');
            }

            return $effect($context);
        });
    }
}
