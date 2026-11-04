<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

final class DenyByDefaultAuthorizerTest extends TestCase
{
    public function test_it_denies_actions_even_with_tenant_context(): void
    {
        $request = new AccessRequest(
            new TenantContext('org-a', 'principal-a', 'correlation-1'),
            'lead', 'read'
        );

        $decision = (new DenyByDefaultAuthorizer())->decide($request);

        self::assertFalse($decision->allowed);
        self::assertSame('POLICY_NOT_CONFIGURED', $decision->reasonCode);
    }

    public function test_invalid_tenant_context_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TenantContext('', 'principal-a', 'correlation-1');
    }
}
