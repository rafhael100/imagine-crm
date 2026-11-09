<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\AuthorizationEnvelope;
use App\Core\Authorization\AuthorizationIntent;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
        return $this->within($tenantId, $resource, $action, $effect, $field);
    }

    /** Strict experimental contract; input can describe an intent, never its authority. */
    public function executeContract(string $tenantId, array $input, Closure $effect, ?string $expectedPrincipalId = null): mixed
    {
        try {
            $intent = AuthorizationIntent::fromArray($input);
        } catch (InvalidArgumentException) {
            throw new AuthorizationException('Protected operation denied.');
        }

        return $this->within($tenantId, $intent->resource, $intent->action, $effect, null, $intent, $expectedPrincipalId);
    }

    private function within(string $tenantId, string $resource, string $action, Closure $effect, ?string $field, ?AuthorizationIntent $intent = null, ?string $expectedPrincipalId = null): mixed
    {
        // Optional causal ID only RESTRICTS the actual locked principal. It
        // never selects/authenticates a user or supplies a session proof.
        return $this->principals->withinCurrent(function (PrincipalReference $principal) use ($tenantId, $resource, $action, $field, $effect, $intent, $expectedPrincipalId): mixed {
            if ($expectedPrincipalId !== null && $principal->principalId !== $expectedPrincipalId) {
                throw new AuthorizationException('Protected operation denied.');
            }
            return $this->memberships->within($principal, $tenantId, function (MembershipSnapshot $membership) use ($principal, $tenantId, $resource, $action, $field, $effect, $intent): mixed {
                $context = (new TenantContextSelector())->select(
                    $principal, $membership, strtolower($tenantId), (string) Str::uuid(),
                );
                if ($context === null) {
                    throw new MembershipDenied();
                }

                $envelope = $intent === null ? null : new AuthorizationEnvelope($context, $principal, $membership, $intent);
                $decision = $this->authorizer->decide(new AccessRequest($context, $resource, $action, $field, $envelope));
                if (!$decision->allowed) {
                    throw new AuthorizationException('Protected operation denied.');
                }

                return $effect($context);
            });
        });
    }
}
