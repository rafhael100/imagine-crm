<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use App\Core\Tenancy\TenantAccessBoundary;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\AuthorizationCases as Cases;
use Tests\TestCase;

final class AuthorizationContractBoundaryTest extends TestCase
{
    use \Tests\Concerns\SyntheticIam;

    public static function adapters(): array
    {
        return ['query' => ['read', 'interactive'], 'search' => ['search', 'interactive'],
            'mutation' => ['update', 'interactive'], 'export' => ['export', 'interactive'],
            'report' => ['report', 'interactive'], 'async' => ['execute', 'job'], 'agent' => ['execute', 'ai']];
    }

    #[DataProvider('adapters')]
    public function test_simulated_adapters_never_query_mutate_export_report_or_enqueue(string $action, string $channel): void
    {
        $this->login();
        $observed = new ContractDenialObserver();
        $boundary = $this->boundary($observed);
        $effects = 0;
        $input = array_replace(Cases::intent(), ['action' => $action, 'channel' => $channel,
            'fields' => ['lab_person' => 'F-PII', 'lab_secret' => 'F-SEC', 'lab_restricted' => 'F-RES']]);
        try {
            // Test-only adapters have NO implementation/binding outside this suite.
            (new SimulatedProtectedAdapter($boundary))->run(self::C, $input, function () use (&$effects): array {
                $effects++;
                return ['lab_person' => 'synthetic-person', 'lab_secret' => 'synthetic-secret'];
            });
            $this->fail('No synthetic descriptor is an executable policy.');
        } catch (AuthorizationException $e) {
            $this->assertSame('Protected operation denied.', $e->getMessage());
        }
        $this->assertSame(0, $effects); // No read, index/count, file, queue or write.
        $this->assertCount(1, $observed->requests);
        $envelope = $observed->requests[0]->envelope;
        $this->assertSame(self::A, $envelope->subject->principalId);
        $this->assertSame(self::C, $envelope->membership->tenantId);
        $this->assertSame(7, $envelope->membership->version);
        $this->assertTrue(Str::isUuid($envelope->context->correlationId));
        $signal = json_encode($observed->decisions[0]->signal());
        foreach ([self::A, self::C, 'lab_person', 'synthetic-secret', 'token', 'factor'] as $private) {
            $this->assertStringNotContainsString($private, $signal);
        }
        $this->assertSame(0, $this->db->transactionLevel());
    }

    public static function malformed(): array { return Cases::malformed(); }

    #[DataProvider('malformed')]
    public function test_forged_or_incomplete_payload_never_reaches_an_effect(array $input): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Protected operation denied.');
        $this->boundary()->executeContract(self::C, $input, fn () => $this->fail('Untrusted contract must deny.'));
    }

    public function test_anonymous_headers_and_principal_payload_do_not_authenticate(): void
    {
        request()->headers->set('X-Principal-Id', self::A);
        request()->headers->set('X-Tenant-Id', self::C);
        request()->merge(['principal_id' => self::A, 'tenant_id' => self::C, 'mfa_verified' => true]);
        $this->expectException(AuthenticationException::class);
        $this->boundary()->executeContract(self::C, Cases::intent(), fn () => $this->fail('Headers cannot authenticate.'));
    }

    public function test_real_session_registry_is_required_after_password_before_mfa(): void
    {
        $this->auth->begin('fixture-a', self::PASSWORD);
        $this->expectException(AuthenticationException::class);
        $this->boundary()->executeContract(self::C, Cases::intent(), fn () => $this->fail('Pending MFA is no authority.'));
    }

    public static function invalidSessions(): array
    {
        return ['revoked' => [['state' => 'revoked']], 'expired' => [['absolute_expires_at' => 1]],
            'MFA missing' => [['mfa_verified_at' => null]], 'credential epoch' => [['credential_version' => 99]],
            'unresolved operation' => [['security_hold' => Cases::RECORD]]];
    }

    #[DataProvider('invalidSessions')]
    public function test_cached_guard_and_previous_denial_do_not_bypass_registry_revalidation(array $change): void
    {
        $this->login();
        $boundary = $this->boundary();
        try { $boundary->executeContract(self::C, Cases::intent(), fn () => $this->fail('No effect.')); }
        catch (AuthorizationException) {}
        $this->assertNotNull($this->guard->user());
        $this->fixtures->table('crm_auth_sessions')->where('identity_id', self::A)->where('state', 'active')->update($change);
        $this->expectException(AuthenticationException::class);
        $boundary->executeContract(self::C, Cases::intent(), fn () => $this->fail('Cached proof is not current.'));
    }

    public function test_membership_and_session_failures_are_rechecked_without_authorization_cache(): void
    {
        $this->login();
        $observed = new ContractDenialObserver();
        $boundary = $this->boundary($observed);
        for ($i = 0; $i < 2; $i++) {
            try { $boundary->executeContract(self::C, Cases::intent(), fn () => $this->fail('No effect.')); }
            catch (AuthorizationException) {}
        }
        $this->assertCount(2, $observed->requests);
        $this->assertNotSame($observed->requests[0]->context->correlationId, $observed->requests[1]->context->correlationId);
        $this->auth->logout();
        $this->expectException(AuthenticationException::class);
        $boundary->executeContract(self::C, Cases::intent(), fn () => $this->fail('Logout must deny.'));
    }

    public function test_postgres_contract_revalidates_tenancy_membership_and_cleans_rls_context(): void
    {
        $business = $this->business();
        $this->login();
        $observed = new ContractDenialObserver();
        $boundary = new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class), new PostgresActiveMembershipStore($business), $observed);
        foreach ([self::C, self::D, '', 'invalid'] as $tenant) {
            try { $boundary->executeContract($tenant, Cases::intent(), fn () => $this->fail('No tenant effects.')); $this->fail('Must deny.'); }
            catch (AuthorizationException) { $this->assertSame(0, $business->transactionLevel()); }
        }
        $this->assertCount(1, $observed->requests); // Only the active membership reached ACL.
        foreach (['suspended', 'disabled'] as $status) {
            $this->fixtures->table('crm_memberships')->where('organization_id', self::C)->update(['status' => $status, 'version' => 2]);
            try { $boundary->executeContract(self::C, Cases::intent(), fn () => $this->fail('Revoked membership.')); $this->fail('Must deny.'); }
            catch (AuthorizationException) { $this->assertCount(1, $observed->requests); }
        }
        $this->assertTrue(in_array($business->selectOne("SELECT current_setting('app.tenant_id', true) AS tenant")->tenant, [null, ''], true));
        $this->assertSame(0, $this->db->transactionLevel());
    }

    public function test_postgres_new_contract_holds_session_and_membership_locks_until_denial(): void
    {
        $business = $this->business();
        $this->login();
        config(['database.connections.crm_contract_revoker' => config('database.connections.pgsql')]);
        $revoker = DB::connection('crm_contract_revoker');
        $probe = function () use ($business, $revoker): void {
            $this->assertSame(1, $business->transactionLevel());
            $this->assertSame(1, $this->db->transactionLevel());
            foreach (['crm_auth_sessions', 'crm_memberships'] as $table) {
                try {
                    $revoker->transaction(function () use ($revoker, $table): void {
                        $revoker->statement("SET LOCAL lock_timeout = '100ms'");
                        if ($table === 'crm_auth_sessions') {
                            $revoker->table($table)->where('identity_id', self::A)->update(['state' => 'revoked']);
                        } else {
                            $revoker->table($table)->where('global_identity_id', self::A)->update(['status' => 'disabled']);
                        }
                    });
                    $this->fail('Concurrent revocation must wait.');
                } catch (QueryException $e) { $this->assertSame('55P03', $e->errorInfo[0] ?? null); }
            }
        };
        $observed = new ContractDenialObserver($probe);
        $boundary = new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class), new PostgresActiveMembershipStore($business), $observed);
        try { $boundary->executeContract(self::C, Cases::intent(), fn () => $this->fail('No business effect.')); $this->fail('Must deny.'); }
        catch (AuthorizationException) { $this->assertCount(1, $observed->requests); }
        $revoker->table('crm_auth_sessions')->where('identity_id', self::A)->update(['state' => 'revoked']);
        try { $boundary->executeContract(self::C, Cases::intent(), fn () => $this->fail('Revoked proof.')); $this->fail('Must deny.'); }
        catch (AuthenticationException) { $this->assertCount(1, $observed->requests); }
        $this->assertSame(0, $business->transactionLevel());
        $this->assertSame(0, $this->db->transactionLevel());
        DB::purge('crm_contract_revoker');
    }

    public function test_container_still_uses_only_deny_by_default_in_every_environment(): void
    {
        foreach (['testing', 'local', 'production'] as $environment) {
            $this->app->instance('env', $environment);
            $this->assertInstanceOf(DenyByDefaultAuthorizer::class, $this->app->make(Authorizer::class));
        }
        $this->app->instance('env', 'testing');
        foreach (['/authorization', '/export', '/reports', '/jobs', '/search'] as $route) {
            $this->get($route)->assertNotFound();
        }
    }

    private function boundary(?Authorizer $authorizer = null): TenantAccessBoundary
    {
        // Repository seam for cross-platform adapter tests only. PG tests below
        // exercise the real restricted-role RLS store, never this fixture.
        $store = new class implements ActiveMembershipStore {
            public function within(PrincipalReference $principal, string $tenantId, Closure $operation): mixed
            {
                return $operation(new MembershipSnapshot(Cases::TENANT, $principal->principalId, MembershipStatus::Active, 7));
            }
        };
        return new TenantAccessBoundary($this->app->make(GuardPrincipalSource::class), $store, $authorizer ?? $this->app->make(Authorizer::class));
    }

    private function business(): \Illuminate\Database\Connection
    {
        if ($this->db->getDriverName() !== 'pgsql') { $this->markTestSkipped('Requires PostgreSQL 16 restricted role and real row locks.'); }
        $this->fixtures->table('crm_organizations')->insert([['id' => self::C], ['id' => self::D]]);
        $this->fixtures->table('crm_memberships')->insert([
            ['id' => (string) Str::uuid(), 'organization_id' => self::C, 'global_identity_id' => self::A, 'status' => 'active', 'version' => 1],
            ['id' => (string) Str::uuid(), 'organization_id' => self::D, 'global_identity_id' => self::A, 'status' => 'suspended', 'version' => 1],
        ]);
        $settings = config('database.connections.pgsql');
        $settings['username'] = getenv('RLS_TEST_USERNAME');
        $settings['password'] = getenv('RLS_TEST_PASSWORD');
        $settings['url'] = null;
        config(['database.connections.crm_contract_business' => $settings]);
        return DB::connection('crm_contract_business');
    }
}

/** Test observer delegates ALL decisions to the real runtime authorizer. No ALLOW. */
final class ContractDenialObserver implements Authorizer
{
    public array $requests = [];
    public array $decisions = [];
    public function __construct(private ?Closure $probe = null) {}
    public function decide(AccessRequest $request): AuthorizationDecision
    {
        $this->requests[] = $request;
        if ($this->probe !== null) { ($this->probe)(); }
        return $this->decisions[] = (new DenyByDefaultAuthorizer())->decide($request);
    }
}

/** No real query/index/file/queue adapter, job class, route or container binding. */
final readonly class SimulatedProtectedAdapter
{
    public function __construct(private TenantAccessBoundary $boundary) {}
    public function run(string $tenantId, array $intent, Closure $effect): mixed
    {
        return $this->boundary->executeContract($tenantId, $intent, $effect);
    }
}
