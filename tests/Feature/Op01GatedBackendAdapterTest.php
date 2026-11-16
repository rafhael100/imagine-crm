<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Support\Agents\Op01GatedBackendAdapter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * P0: real CRM IAM seam is wired, but can neither ALLOW nor read leads.
 * Test intentionally does not create keys, users, database rows or routes.
 */
final class Op01GatedBackendAdapterTest extends TestCase
{
    private const TENANT = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
    private const PRINCIPAL = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private static function denial(): array
    {
        return [
            'contract' => 'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state' => 'DENIED_OR_NOT_FOUND',
            'projection' => null,
            'real_authorization' => false,
            'external_calls' => 0,
        ];
    }

    private static function intent(): array
    {
        return [
            'schema_version' => 1,
            'policy_version' => 'lab-deny-v1',
            'resource' => 'lab.record',
            'resource_id' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
            'action' => 'read',
            'fields' => ['lab_status' => 'F-BIZ'],
            'scope' => ['kind' => 'tenant', 'id' => self::TENANT],
            'channel' => 'interactive',
        ];
    }

    public function test_default_configuration_keeps_all_external_effects_disabled(): void
    {
        foreach (['op01_review_probe_enabled','op01_business_read_enabled',
                  'op01_service_ingress_enabled','provider_calls_enabled'] as $flag) {
            $this->assertFalse(config('aia_agents.'.$flag));
        }
        $this->assertInstanceOf(DenyByDefaultAuthorizer::class,
            $this->app->make(Authorizer::class));
        $this->assertSame(self::denial(), $this->app->make(
            Op01GatedBackendAdapter::class
        )->inspect(self::TENANT,self::PRINCIPAL,self::intent()));
    }

    public static function invalidInputs(): array
    {
        $valid=self::intent();
        $extra=$valid;
        $extra['grant_from_agent'] = true;
        return [
            'unknown tenant' => ['SYNTHETIC_TENANT_A',self::PRINCIPAL,$valid],
            'foreign principal' => [self::TENANT,'X_FAKE_USER',$valid],
            'tenant header spoof' => ['',self::PRINCIPAL,$valid],
            'missing consent' => [self::TENANT,self::PRINCIPAL,[]],
            'extra grant flag' => [self::TENANT,self::PRINCIPAL,$extra],
            'unsupported operation' => [
                self::TENANT,self::PRINCIPAL,
                array_replace($valid,['action'=>'write']),
            ],
            'service impersonation' => [
                self::TENANT,self::PRINCIPAL,
                array_replace($valid,['channel'=>'service']),
            ],
            'untrusted policy' => [
                self::TENANT,self::PRINCIPAL,
                array_replace($valid,['policy_version'=>'OP01_ALLOW']),
            ],
            'too many fields' => [
                self::TENANT,self::PRINCIPAL,
                $valid+array_fill_keys(array_map(
                    static fn (int $n): string => 'extra_'.$n,
                    range(1,20)
                ),'fake'),
            ],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_bad_evidence_never_produces_business_data(
        string $tenant,string $principal,array $intent
    ): void {
        config(['aia_agents.op01_review_probe_enabled'=>true]);
        $this->assertSame(
            self::denial(),
            $this->app->make(Op01GatedBackendAdapter::class)
                ->inspect($tenant,$principal,$intent),
        );
    }

    public function test_even_forced_runtime_flags_cannot_activate_lead_read(): void
    {
        config([
            'aia_agents.op01_review_probe_enabled'=>true,
            'aia_agents.op01_business_read_enabled'=>true,
            'aia_agents.op01_service_ingress_enabled'=>true,
        ]);
        $before=Route::getRoutes()->count();
        $result=$this->app->make(Op01GatedBackendAdapter::class)
            ->inspect(self::TENANT,self::PRINCIPAL,self::intent());
        $this->assertSame(self::denial(),$result);
        $this->assertSame($before,Route::getRoutes()->count());
        $this->postJson('/api/agents/op01/leads',[
            'tenant_id'=>self::TENANT,
            'principal_id'=>self::PRINCIPAL,
            'allow'=>true,
        ])->assertNotFound();
    }
}
