<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F3-48: the application boot must not turn synthetic interop tests into API
 * access. These checks exercise actual Laravel container/routing, whereas
 * SyntheticRuntimeIsolationTest does a static source scan.
 */
final class IntegrationBoundariesRemainClosedTest extends TestCase
{
    public function test_application_authorizer_is_still_deny_by_default(): void
    {
        self::assertInstanceOf(
            DenyByDefaultAuthorizer::class,
            $this->app->make(Authorizer::class),
        );
    }

    public function test_experimental_identity_profile_is_off_at_boot(): void
    {
        self::assertFalse(
            (bool) config('identity.experimental'),
            'Experimental authentication cannot be the default CRM service profile.',
        );
    }

    public function test_no_business_routes_are_exposed_by_the_booted_framework(): void
    {
        $methods = [];

        foreach (Route::getRoutes() as $route) {
            $uri = ltrim($route->uri(), '/');
            self::assertSame(
                'up',
                $uri,
                'Only /up may exist before CORE/IAM/SEG/API approval; got '.$uri,
            );
            $methods = [...$methods, ...$route->methods()];
        }

        self::assertContains('GET', $methods, 'Health route must remain available.');
    }

    public function test_portal_agents_and_import_endpoints_do_not_exist(): void
    {
        foreach ([
            '/api/crm/portal/handoff',
            '/api/crm/agents/leads/SYNTHETIC_LEAD_1',
            '/api/crm/leads/import',
            '/api/crm/leads/export',
        ] as $path) {
            $this->getJson($path)->assertNotFound();
            $this->postJson($path, [
                'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
                'operation' => 'crm.lead.read_minimal',
                'tenant_ref' => 'SYNTHETIC_TENANT_A',
                'is_admin' => true,
            ])->assertNotFound();
        }
    }

    public function test_no_local_private_storage_auto_serving_route_is_enabled(): void
    {
        self::assertFalse(
            (bool) config('filesystems.disks.local.serve', false),
            'Private local-storage auto-serving must remain off.',
        );
    }
}
