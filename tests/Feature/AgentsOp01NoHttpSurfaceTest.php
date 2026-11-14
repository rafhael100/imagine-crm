<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * F3-AIA OP-01: hostile HTTP callers cannot activate an unapproved CRM API.
 *
 * Tests the ACTUAL Laravel route collection and kernel with fake inputs.
 * Never deploys or creates a route, account, identity, token, ACL or client.
 */
final class AgentsOp01NoHttpSurfaceTest extends TestCase
{
    public static function paths(): array
    {
        return [
            'Agents contract' => ['/api/agents/op01/leads'],
            'AI Agents contract' => ['/api/ai-agents/op01/leads'],
            'AIA vendor' => ['/api/aia/lead/read'],
            'OP-01 direct' => ['/api/op01/leads'],
            'CRM integration' => ['/api/integrations/crm-agents/read'],
            'AIA internal' => ['/internal/aia/op01/leads'],
            'Service integration' => ['/integrations/agents/read'],
            'Legacy agent' => ['/api/crm/agents/leads'],
        ];
    }

    public static function methodsAndPaths(): array
    {
        $cases = [];
        foreach (self::paths() as $label => [$path]) {
            foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
                $cases[$label . ' ' . $method] = [$method, $path];
            }
        }
        return $cases;
    }

    public function test_no_aia_or_op01_route_is_registered_in_laravel(): void
    {
        $checked = 0;
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $path = '/' . ltrim((string) $route->uri(), '/');
            $this->assertDoesNotMatchRegularExpression(
                '~(?:^|/)(?:aia|op01|agents|ai-agents)(?:/|$)~i',
                $path,
                'Unapproved OP-01 endpoint registered: ' . $path,
            );
            ++$checked;
        }
        $this->assertGreaterThan(0, $checked); // Bootstrap /up exists.
    }

    #[DataProvider('methodsAndPaths')]
    public function test_all_attempted_op01_http_methods_are_not_found(
        string $method,
        string $path
    ): void {
        // Hostile flag/header claims do not grant service identity; no API
        // bootstrapping or real authorization is configured.
        config([
            'app.debug' => false,
            'identity.experimental' => true,
            'crm.aia.integration_enabled' => true,
            'crm.aia.op01_enabled' => true,
        ]);
        $this->withHeaders([
            'Authorization' => 'Bearer SYNTHETIC_FAKE_NOT_A_CREDENTIAL',
            'X-Service-Identity' => 'SYNTHETIC_AGENT_SERVICE',
            'X-Tenant-Ref' => 'SYNTHETIC_FOREIGN_TENANT',
            'Accept' => 'application/json',
        ]);
        $response = $this->call($method, $path, [
            'tenant_ref' => 'SYNTHETIC_A',
            'principal_ref' => 'SYNTHETIC_PRINCIPAL',
            'resource_ref' => 'SYNTHETIC_A_LEAD',
            'operation' => 'crm.lead.read_minimal',
            'is_admin' => true,
        ]);
        $response->assertNotFound();
        $body = $response->getContent();
        $this->assertIsString($body);
        foreach (['SYNTHETIC_A_LEAD', 'SYNTHETIC_FOREIGN_TENANT',
                  'SYNTHETIC_PRINCIPAL', 'SYNTHETIC_FAKE_NOT_A_CREDENTIAL',
                  'READ_SYNTHETIC_ONLY', 'access_token'] as $value) {
            $this->assertStringNotContainsString($value, $body);
        }
    }

    public function test_bootstrap_has_no_api_route_file_registration(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $web = file_get_contents(base_path('routes/web.php'));
        $this->assertIsString($bootstrap);
        $this->assertIsString($web);
        $this->assertStringNotContainsString('api:', $bootstrap);
        $this->assertStringNotContainsString('Route::', $web);
        $this->assertFileDoesNotExist(base_path('routes/api.php'));
    }
}
