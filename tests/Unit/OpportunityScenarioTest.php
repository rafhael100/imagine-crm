<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Homologation\OpportunityScenario;
use PHPUnit\Framework\TestCase;

/**
 * Anonymous commercial example: scenario arithmetic only. The method never
 * writes leads, issues trials, requests contact details or touches providers.
 */
final class OpportunityScenarioTest extends TestCase
{
    public function test_defaults_and_integer_rounding_are_explicit(): void
    {
        self::assertSame([
            'visits'=>1000,
            'lead_rate'=>4,
            'client_rate'=>15,
            'leads'=>40,
            'clients'=>6,
            'scenario_only'=>true,
        ],OpportunityScenario::fromQuery([]));

        $scenario=OpportunityScenario::fromQuery([
            'visits'=>'2500','lead_rate'=>'8','client_rate'=>'13',
        ]);
        self::assertSame(200,$scenario['leads']);
        self::assertSame(26,$scenario['clients']);
        self::assertSame(2500,$scenario['visits']);
        self::assertTrue($scenario['scenario_only']);
    }

    public function test_zero_and_upper_limits_never_produce_more_clients_than_leads(): void
    {
        self::assertSame(0,OpportunityScenario::fromQuery([
            'visits'=>'0','lead_rate'=>'100','client_rate'=>'100',
        ])['clients']);

        $max=OpportunityScenario::fromQuery([
            'visits'=>'1000000','lead_rate'=>'100','client_rate'=>'100',
        ]);
        self::assertSame(1000000,$max['leads']);
        self::assertSame(1000000,$max['clients']);

        foreach ([
            ['visits'=>'1','lead_rate'=>'50','client_rate'=>'50'],
            ['visits'=>'99','lead_rate'=>'13','client_rate'=>'17'],
            ['visits'=>'1000000','lead_rate'=>'1','client_rate'=>'1'],
        ] as $inputs) {
            $scenario=OpportunityScenario::fromQuery($inputs);
            self::assertLessThanOrEqual($scenario['visits'],$scenario['leads']);
            self::assertLessThanOrEqual($scenario['leads'],$scenario['clients']);
        }
    }

    public function test_malformed_or_out_of_bounds_query_fields_fail_to_safe_defaults(): void
    {
        foreach ([
            '-5','12.7','1e6','010',' 1','1 ','+1','%20','1000001',
            '999999999999999999999999', 'x123', '1;DROP TABLE crm_leads',
            ['0'=>'1000'], ['tenant_id'=>'SYNTHETIC_BAD'],
        ] as $bad) {
            self::assertSame(1000,OpportunityScenario::fromQuery([
                'visits'=>$bad,
            ])['visits']);
        }
        foreach (['101','-1','2.5','1e2','0001',[],['100']] as $badRate) {
            self::assertSame(4,OpportunityScenario::fromQuery([
                'lead_rate'=>$badRate,
            ])['lead_rate']);
            self::assertSame(15,OpportunityScenario::fromQuery([
                'client_rate'=>$badRate,
            ])['client_rate']);
        }
        self::assertSame(0,OpportunityScenario::fromQuery([
            'visits'=>'0','lead_rate'=>'0','client_rate'=>'0',
        ])['clients']);
    }

    public function test_non_scenario_fields_are_never_passed_through_or_persisted(): void
    {
        $result=OpportunityScenario::fromQuery([
            'visits'=>'2500','lead_rate'=>'8','client_rate'=>'13',
            'tenant_ref'=>'SYNTHETIC_BAD_TENANT',
            'name'=>'CONTACT_TEST_ONLY',
            'email'=>'contact@example.invalid',
            'role'=>'admin',
        ]);
        self::assertSame(
            ['visits','lead_rate','client_rate','leads','clients','scenario_only'],
            array_keys($result)
        );
        self::assertSame(26,$result['clients']);
    }
}
