<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Async\AsyncAuthorityBridge;
use App\Core\Async\AsyncFailureKind;
use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\AuthorizationEnvelope;
use App\Core\Authorization\AuthorizationIntent;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\ExperimentalAuthenticator;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\LaboratoryProvisioner;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use App\Core\Tenancy\TenantContext;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\AsyncCases;
use Tests\Fixtures\AuthorizationCases as Cases;
use Tests\Fixtures\ConsultativeLedgerInspector;
use Tests\Fixtures\LedgerFixtureManifest;
use Tests\Fixtures\LedgerIntegrityProbe;
use Tests\Fixtures\PostgresLedgerReader;
use Tests\Fixtures\PostgresSyntheticLedger;
use Tests\Support\SecurityBaselineChecks as Checks;
use Tests\TestCase;

final class FastTrackSecurityBaselineTest extends TestCase
{
    public function test_profile_is_off_by_default_and_only_deny_authorizer_resolves_in_all_environments(): void
    {
        $this->assertFalse(config('identity.experimental'));
        foreach (['testing', 'local', 'staging', 'production'] as $environment) {
            foreach ([false, true] as $enabled) {
                $this->app->instance('env', $environment); config(['identity.experimental' => $enabled]);
                (new AppServiceProvider($this->app))->register();
                $this->assertTrue(Checks::authorizerIsContained($this->app->make(Authorizer::class), $this->descriptions()));
            }
        }
        $this->app->instance('env', 'testing');
    }

    private function descriptions(): array
    {
        $context = new TenantContext(Cases::TENANT, Cases::SUBJECT, (string) Str::uuid()); $requests = [];
        foreach (['read', 'search', 'update', 'export', 'report', 'execute'] as $action) {
            foreach (['interactive', 'job', 'ai', 'support'] as $channel) {
                $intent = AuthorizationIntent::fromArray(array_replace(Cases::intent(), ['action' => $action, 'channel' => $channel]));
                $envelope = new AuthorizationEnvelope($context, new PrincipalReference(Cases::SUBJECT),
                    new MembershipSnapshot(Cases::TENANT, Cases::SUBJECT, MembershipStatus::Active, 1), $intent);
                $requests[] = new AccessRequest($context, 'lab.record', $action, envelope: $envelope);
            }
        }
        $requests[] = new AccessRequest($context, 'unknown.resource', 'execute'); return $requests;
    }

    public function test_authorizer_verifier_rejects_replacement_even_if_it_currently_returns_deny(): void
    {
        $replacement = new class implements Authorizer {
            public function decide(AccessRequest $request): AuthorizationDecision { return AuthorizationDecision::deny(); }
        };
        $this->assertFalse(Checks::authorizerIsContained($replacement, $this->descriptions()));
        $this->app->instance(Authorizer::class, $replacement);
        $this->assertFalse(Checks::authorizerIsContained($this->app->make(Authorizer::class), $this->descriptions()));
    }

    public function test_decision_has_only_the_public_deny_factory_and_cannot_be_mutated(): void
    {
        $reflection = new \ReflectionClass(AuthorizationDecision::class);
        $this->assertTrue($reflection->isFinal()); $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->getConstructor()->isPrivate());
        $factories = array_values(array_map(static fn ($method): string => $method->getName(),
            array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn ($method): bool => $method->isStatic())));
        $this->assertSame(['deny'], $factories);
        foreach (['POLICY_NOT_CONFIGURED', 'CONTRACT_INVALID', 'ALLOW', '*'] as $reason) {
            $this->assertFalse(AuthorizationDecision::deny($reason)->allowed);
        }
    }

    public function test_all_experimental_migrations_stay_outside_normal_discovery_and_refuse_staging_production(): void
    {
        $files = glob(database_path('experimental-migrations/*/*.php')); $this->assertCount(4, $files);
        $this->assertContains(database_path('experimental-migrations/tenant/2026_11_04_000001_create_crm_identity_organizations_memberships.php'), $files);
        foreach (['production', 'staging', 'local', 'testing'] as $environment) {
            $this->app->instance('env', $environment); config(['identity.experimental' => true]);
            $normal = $this->app['migrator']->getMigrationFiles(array_merge([database_path('migrations')], $this->app['migrator']->paths()));
            foreach ($files as $file) {
                $this->assertArrayNotHasKey(basename($file, '.php'), $normal);
                if (in_array($environment, ['production', 'staging'], true)) {
                    foreach (['up', 'down'] as $direction) {
                        try { (require $file)->$direction(); $this->fail('Nonlaboratory migration was accepted.'); }
                        catch (\RuntimeException $e) { $this->assertMatchesRegularExpression('/laboratory only|isolated-development only/', $e->getMessage()); }
                    }
                }
            }
        }
        $this->app->instance('env', 'testing'); config(['identity.experimental' => false]);
        foreach ($files as $file) {
            foreach (['up', 'down'] as $direction) {
                try { (require $file)->$direction(); $this->fail('Disabled profile was accepted.'); }
                catch (\RuntimeException) { $this->addToAssertionCount(1); }
            }
        }
    }

    public function test_nonlaboratory_explicit_profile_does_not_enable_authentication_or_async_inspection(): void
    {
        $db = DB::connection();
        foreach (['staging', 'production'] as $environment) {
            $this->app->instance('env', $environment); config(['identity.experimental' => true]);
            (new AppServiceProvider($this->app))->register();
            foreach ([ExperimentalAuthenticator::class, LaboratoryProvisioner::class] as $service) {
                try { $this->app->make($service); $this->fail('Laboratory service enabled outside laboratory.'); }
                catch (\LogicException) { $this->addToAssertionCount(1); }
            }
            $bridge = new AsyncAuthorityBridge(new TenantAccessBoundary(new GuardPrincipalSource(), new PostgresActiveMembershipStore($db), $this->app->make(Authorizer::class)));
            $result = $bridge->inspect(AsyncCases::request());
            $this->assertSame(AsyncFailureKind::LaboratoryDisabled, $result->diagnostic->kind); $this->assertFalse($result->decision->allowed);
        }
        $this->app->instance('env', 'testing');
    }

    public function test_runtime_routes_container_providers_and_commands_have_no_laboratory_hooks(): void
    {
        foreach ($this->app->getBindings() as $abstract => $binding) {
            $this->assertFalse(Checks::hasLaboratoryReference([$abstract, $binding['concrete']]), 'Synthetic container hook detected.');
        }
        foreach ($this->app->getProviders(\Illuminate\Support\ServiceProvider::class) as $provider) { $this->assertFalse(Checks::hasLaboratoryReference($provider)); }
        foreach ($this->app['router']->getRoutes() as $route) {
            $this->assertFalse(Checks::hasLaboratoryReference($route->getAction()));
            // Only bootstrap health and framework-local storage infrastructure are present.
            $this->assertContains($route->uri(), ['up', 'storage/{path}']);
        }
        foreach (Artisan::all() as $command) {
            $this->assertFalse(Checks::hasLaboratoryReference($command));
            if ($command instanceof \Illuminate\Foundation\Console\ClosureCommand) {
                $callback = (new \ReflectionObject($command))->getProperty('callback')->getValue($command);
                $this->assertFalse(Checks::hasLaboratoryReference($callback));
            }
        }
        foreach (['app', 'bootstrap', 'config', 'routes', 'public'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($directory), \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php' && !str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR)) {
                    $this->assertFalse(Checks::sourceReferencesLaboratory(file_get_contents($file->getPathname())), 'Runtime PHP references laboratory code.');
                }
            }
        }
    }

    public function test_hook_verifier_detects_fixture_route_binding_and_php_names_but_ignores_comments(): void
    {
        $closure = static fn () => 'synthetic-only';
        $route = $this->app['router']->get('/synthetic-baseline-only', $closure);
        $this->assertTrue(Checks::hasLaboratoryReference($route->getAction()));
        $this->app->bind('synthetic.baseline', $closure);
        $this->assertTrue(Checks::hasLaboratoryReference($this->app->getBindings()['synthetic.baseline']['concrete']));
        foreach (['<?php use Tests\\Fixtures\\PostgresSyntheticLedger;', '<?php $x = "Tests\\\\Fixtures\\\\PostgresSyntheticLedger";'] as $source) {
            $this->assertTrue(Checks::sourceReferencesLaboratory($source));
        }
        $this->assertFalse(Checks::sourceReferencesLaboratory('<?php /* Tests\\Fixtures\\Example */ return [];'));
    }

    public function test_bridge_and_ledger_consultation_cannot_dispatch_or_promote_a_denial(): void
    {
        config(['identity.experimental' => true]); $db = DB::connection();
        $bridge = new AsyncAuthorityBridge(new TenantAccessBoundary(new GuardPrincipalSource(), new PostgresActiveMembershipStore($db), new DenyByDefaultAuthorizer()));
        Bus::fake(); Queue::fake(); Mail::fake(); Http::fake(); Event::fake();
        foreach (AsyncCases::classes() as [$class]) {
            $result = $bridge->inspect(AsyncCases::request($class)); $this->assertFalse($result->decision->allowed);
        }
        $receipt = (new PostgresSyntheticLedger($db, $bridge, Cases::TENANT))->record(AsyncCases::request('B'));
        $this->assertSame('inconclusive', $receipt->outcome); $this->assertFalse($receipt->denial->decision->allowed);
        $manifest = new LedgerFixtureManifest([]);
        $report = (new ConsultativeLedgerInspector(new PostgresLedgerReader($db, Cases::TENANT), $bridge, Cases::TENANT))->inspect(AsyncCases::request('B'), $manifest);
        $this->assertFalse($report->denial->decision->allowed);
        $sample = array_fill_keys(array_keys(LedgerIntegrityProbe::COLUMNS), []);
        $this->assertSame('needs_owner', (new LedgerIntegrityProbe())->inspect($sample, $manifest, Cases::TENANT)->signal()['owner_review']);
        Bus::assertNothingDispatched(); Queue::assertNothingPushed(); Mail::assertNothingSent(); Http::assertNothingSent(); Event::assertNothingDispatched();
    }

    public static function metadataCases(): array
    {
        $good = ['pull_request' => ['head' => ['ref' => 'codex/f1-10-fast-track-security-baseline', 'repo' => ['full_name' => 'rafhael100/imagine-crm']],
            'base' => ['ref' => 'dev/fast-track-crm-2026-11-04', 'repo' => ['full_name' => 'rafhael100/imagine-crm']]]];
        $main = $good; $main['pull_request']['base']['ref'] = 'main';
        $fork = $good; $fork['pull_request']['head']['repo']['full_name'] = 'synthetic/fork';
        $legacy = $main; $legacy['pull_request']['head']['ref'] = 'dev/fast-track-crm-2026-11-04';
        return [[$good, true], [$main, false], [$fork, false], [[], false], [['pull_request' => []], false], [$legacy, true]];
    }

    #[DataProvider('metadataCases')]
    public function test_codex_target_verifier_has_fail_closed_counterexamples(array $event, bool $expected): void
    {
        $this->assertSame($expected, Checks::codexPullRequestTargetsDevelopment($event));
    }

    public function test_actual_github_pull_request_event_targets_development_for_codex_branch(): void
    {
        if (getenv('GITHUB_EVENT_NAME') !== 'pull_request') { $this->markTestSkipped('Requires actual GitHub pull_request metadata; pure counterexamples still run.'); }
        $path = getenv('GITHUB_EVENT_PATH'); $this->assertIsString($path); $this->assertFileExists($path);
        $event = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue(Checks::codexPullRequestTargetsDevelopment($event), 'Codex PR must target development, never main.');
    }

    public function test_gate_document_contains_the_exact_67_open_core_identifiers(): void
    {
        $document = file_get_contents(base_path('docs/desenvolvimento/F1-10-consolidacao-gates-seguranca.md'));
        preg_match_all('/^\| (CORE\d{2}-D\d{2}) \|/m', $document, $matches);
        $expected = [];
        for ($core = 1; $core <= 10; $core++) {
            for ($decision = 4; $decision <= ($core === 1 ? 7 : 10); $decision++) { $expected[] = sprintf('CORE%02d-D%02d', $core, $decision); }
        }
        $this->assertCount(67, $matches[1]); $this->assertSame($expected, $matches[1]);
        foreach ($expected as $id) { $this->assertMatchesRegularExpression('/^\| '.preg_quote($id, '/').' \|.*ABERTO.*$/m', $document); }
    }
}
