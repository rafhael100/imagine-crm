<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantContext;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * References to the checks held by TenantAccessBoundary, NOT portable proof.
 * Constructing/caching/serializing this object never authenticates its subject.
 */
final readonly class AuthorizationEnvelope
{
    public const SUBJECT_EVIDENCE = 'crm_session_registry';

    public function __construct(
        public TenantContext $context,
        public PrincipalReference $subject,
        public MembershipSnapshot $membership,
        public AuthorizationIntent $intent,
    ) {
        if (!Str::isUuid($context->tenantId) || !Str::isUuid($context->principalId) || !Str::isUuid($context->correlationId)
            || $context->principalId !== $subject->principalId || $membership->principalId !== $subject->principalId
            || $membership->tenantId !== $context->tenantId || $membership->status !== MembershipStatus::Active
            || $membership->version < 1) {
            throw new InvalidArgumentException('Invalid authorization contract.');
        }
    }
}
