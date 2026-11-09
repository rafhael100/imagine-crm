<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantContextSelector;
use PHPUnit\Framework\TestCase;

final class TenantContextSelectorTest extends TestCase
{
    public function test_active_membership_matches_exact_principal_and_tenant(): void
    {
        $context = (new TenantContextSelector())->select(
            new PrincipalReference('principal-1'),
            new MembershipSnapshot('org-a', 'principal-1', MembershipStatus::Active, 1),
            'org-a',
            'correlation-1',
        );

        self::assertNotNull($context);
        self::assertSame('org-a', $context->tenantId);
    }

    public function test_other_tenant_membership_is_not_accepted(): void
    {
        $context = (new TenantContextSelector())->select(
            new PrincipalReference('principal-1'),
            new MembershipSnapshot('org-a', 'principal-1', MembershipStatus::Active, 1),
            'org-b',
            'correlation-1',
        );

        self::assertNull($context);
    }

    public function test_suspended_membership_is_not_accepted(): void
    {
        $context = (new TenantContextSelector())->select(
            new PrincipalReference('principal-1'),
            new MembershipSnapshot('org-a', 'principal-1', MembershipStatus::Suspended, 1),
            'org-a',
            'correlation-1',
        );

        self::assertNull($context);
    }

    public function test_principal_mismatch_is_not_accepted(): void
    {
        $context = (new TenantContextSelector())->select(
            new PrincipalReference('principal-2'),
            new MembershipSnapshot('org-a', 'principal-1', MembershipStatus::Active, 1),
            'org-a',
            'correlation-1',
        );

        self::assertNull($context);
    }
}
