<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Homologation\Controller;
use App\Homologation\ExecutionMonitor;
use App\Homologation\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

/** Configuration and negative entry checks; real session/MFA reads run in the private HTTP harness. */
final class ExecutionMonitorSurfaceTest extends TestCase
{
    private string|false $oldMonitor;
    private string|false $oldJourney;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oldMonitor = getenv('CRM_N8N_EXECUTION_MONITOR');
        $this->oldJourney = getenv('CRM_PRIVATE_JOURNEY');
        putenv('CRM_N8N_EXECUTION_MONITOR');
        putenv('CRM_PRIVATE_JOURNEY');
    }

    protected function tearDown(): void
    {
        putenv($this->oldMonitor === false ? 'CRM_N8N_EXECUTION_MONITOR' : 'CRM_N8N_EXECUTION_MONITOR='.$this->oldMonitor);
        putenv($this->oldJourney === false ? 'CRM_PRIVATE_JOURNEY' : 'CRM_PRIVATE_JOURNEY='.$this->oldJourney);
        parent::tearDown();
    }

    private function privateConfiguration(): void
    {
        $this->app->detectEnvironment(fn () => 'staging');
        config(['identity.experimental' => true,
            'identity.private_staging_profile' => 'private-homologation-synthetic-v1',
            'identity.abuse_store' => 'redis', 'session.driver' => 'redis',
            'app.debug' => false, 'app.url' => 'https://crm-dev.imaginecomunica.com.br',
            'database.default' => 'pgsql']);
        $connection = ['driver' => 'pgsql', 'database' => 'imagine_crm_private_homologation',
            'host' => 'postgres', 'port' => 5432, 'url' => null];
        config(['database.connections.pgsql' => $connection + ['username' => 'crm_homologation_app', 'password' => str_repeat('A', 32)],
            'database.connections.crm_iam' => $connection + ['username' => 'crm_homologation_iam', 'password' => str_repeat('B', 32)]]);
        putenv('CRM_PRIVATE_JOURNEY=synthetic-v1');
        putenv('CRM_N8N_EXECUTION_MONITOR=synthetic-private-v1');
    }

    private function assertDashboardDenied(array $query, int $status): void
    {
        $resolved = false;
        $this->app->bind(Workspace::class, static function () use (&$resolved) {
            $resolved = true;
            throw new \LogicException('Workspace must not be resolved for this denied request.');
        });
        try {
            (new Controller())->dashboard(Request::create('/painel', 'GET', $query));
            self::fail('Denied monitor request returned a page.');
        } catch (HttpExceptionInterface $error) {
            self::assertSame($status, $error->getStatusCode());
        }
        self::assertFalse($resolved);
    }

    public function test_optional_flag_does_not_open_any_public_or_service_surface(): void
    {
        putenv('CRM_N8N_EXECUTION_MONITOR=synthetic-private-v1');
        putenv('CRM_PRIVATE_JOURNEY=synthetic-v1');
        config(['identity.experimental' => true, 'app.debug' => false]);
        self::assertFalse(ExecutionMonitor::enabled());
        $routes = Route::getRoutes()->getRoutes();
        self::assertCount(1, $routes);
        self::assertSame('up', trim($routes[0]->uri(), '/'));
        foreach (['/painel?section=n8n', '/automacoes/n8n', '/api/n8n/executions',
            '/private/events/execution'] as $uri) {
            $this->withHeaders(['X-Service-Identity' => 'SYNTHETIC_SERVICE',
                'X-Tenant-Id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'])
                ->get($uri)->assertNotFound();
        }
    }

    public function test_monitor_flag_requires_the_entire_private_runtime_profile(): void
    {
        $this->privateConfiguration();
        self::assertTrue(ExecutionMonitor::enabled()); // Configuration admission only, not identity authority.
        foreach (['', 'true', '1', 'synthetic-v1', 'synthetic-private-v1 '] as $flag) {
            putenv('CRM_N8N_EXECUTION_MONITOR='.$flag);
            self::assertFalse(ExecutionMonitor::enabled());
        }
        foreach (['testing', 'local', 'production'] as $environment) {
            $this->privateConfiguration();
            $this->app->detectEnvironment(fn () => $environment);
            self::assertFalse(ExecutionMonitor::enabled());
        }
        foreach (['session.driver' => 'array', 'identity.experimental' => false,
            'identity.private_staging_profile' => null, 'app.debug' => true,
            'database.connections.pgsql.port' => 5433] as $key => $bad) {
            $this->privateConfiguration();
            config([$key => $bad]);
            self::assertFalse(ExecutionMonitor::enabled(), $key);
        }
        $this->privateConfiguration();
        putenv('CRM_PRIVATE_JOURNEY');
        self::assertFalse(ExecutionMonitor::enabled());
    }

    public function test_disabled_monitor_cannot_resolve_workspace_or_its_database_reader(): void
    {
        $this->assertDashboardDenied(['section' => 'n8n'], 404);
        $this->privateConfiguration();
        putenv('CRM_N8N_EXECUTION_MONITOR');
        $this->assertDashboardDenied(['section' => 'n8n', 'event' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'], 404);
    }

    public function test_bad_navigation_is_rejected_before_any_authority_or_reader_is_resolved(): void
    {
        $this->privateConfiguration();
        foreach ([['section' => 'n8n', 'event' => ['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']], ['section' => 'n8n', 'lang' => ['pt']],
            ['section' => 'n8n', 'company_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'],
            ['section' => 'n8n', 'event' => 'invalid'], ['section' => 'n8n', 'action' => 'retry']]
            as $query) {
            $this->assertDashboardDenied($query, 422);
        }
    }
}
