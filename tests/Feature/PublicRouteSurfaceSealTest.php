<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * SEC-ROUTE-01 — interim public-surface seal for the unapproved CRM.
 *
 * The previous keyword-based gate protects known business paths, but a
 * new harmless-sounding GET path could otherwise bypass that vocabulary.
 * Only the framework health endpoint /up is released at this stage.
 *
 * This is a CI tripwire, not authentication, firewalling or an allow policy.
 * Any later public route must undergo specific CORE/SEG/LGPD review first.
 */
final class PublicRouteSurfaceSealTest extends TestCase
{
    public function test_only_the_get_head_health_route_is_registered(): void
    {
        $routes = Route::getRoutes()->getRoutes();

        $this->assertCount(1, $routes, 'A new public route requires an explicit release-gate review.');

        foreach ($routes as $route) {
            $this->assertSame('up', trim($route->uri(), '/'), 'Only /up may be registered before approval.');
            $methods = $route->methods();
            sort($methods);
            $this->assertSame(['GET', 'HEAD'], $methods, 'Only health GET/HEAD is permitted.');
            $this->assertFalse($route->isFallback(), 'Fallback routes are not a reviewed public API.');
        }
    }

    public function test_health_is_available_without_exposing_unapproved_read_endpoints(): void
    {
        $this->get('/up')->assertOk();

        // These examples intentionally include innocuous aliases that the
        // older keyword regex does not necessarily recognize. The route
        // inventory check above protects *all* unreviewed names.
        foreach ([
            '/',
            '/users',
            '/companies',
            '/dashboard',
            '/billing',
            '/search',
            '/reports',
            '/api/v1/organizations',
            '/crm/activities',
            '/download',
            '/internal/status',
            '/health',
        ] as $uri) {
            $this->get($uri)->assertNotFound();
        }
    }
}
