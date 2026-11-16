<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Agents\Op01SyntheticLeadProjectionLab;
use Illuminate\Support\Facades\Route;
use ImagineAI\Domain\MockError;
use ImagineAI\Domain\TenantContext;
use ImagineAI\MockBridge\MockCrmReadRequest;
use ReflectionClass;
use Tests\TestCase;

/**
 * P14 reciprocal test of REAL source classes on an immutable Agents checkout.
 * Read-only contract compatibility; NOT an operational CRM authorization.
 */
final class Op01AgentsReciprocalFieldContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('AIA_OP01_P14_PINNED_LAB') !== 'true') {
            $this->markTestSkipped('P14 requires exact pinned Agents checkout.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing') {
            throw new \RuntimeException('P14_UNREVIEWED_ENVIRONMENT_DENIED');
        }
        $root=dirname(__DIR__,2).'/_agents_pinned_source';
        foreach ([
            '/src/Domain/MockError.php',
            '/src/Domain/TenantContext.php',
            '/src/MockBridge/MockCrmReadRequest.php',
        ] as $relative) {
            if (!is_file($root.$relative)) {
                throw new \RuntimeException('P14_SOURCE_CHECKOUT_MISSING');
            }
            require_once $root.$relative;
        }
    }

    /** @return array<string,mixed> */
    private static function canonicalWire(array $fields): array
    {
        return [
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_REQUEST_P14',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'principal_ref'=>'SYNTHETIC_PRINCIPAL_A',
            'resource_ref'=>'SYNTHETIC_LEAD_A',
            'purpose_ref'=>'SYNTHETIC_PURPOSE_A',
            'fields'=>$fields,
        ];
    }

    public function test_real_agents_request_fields_equal_real_crm_column_projection(): void
    {
        $constant=(new ReflectionClass(Op01SyntheticLeadProjectionLab::class))
            ->getReflectionConstant('FIELDS');
        self::assertNotFalse($constant);
        self::assertSame(['ref','stage'],$constant->getValue());

        foreach ([['ref'],['stage'],['ref','stage'],['stage','ref']] as $fields) {
            $wire=self::canonicalWire($fields);
            $request=new MockCrmReadRequest($wire);
            self::assertSame($fields,$request->fields);
            $bytes=MockCrmReadRequest::encodeCanonicalJson($wire);
            self::assertSame($wire,MockCrmReadRequest::decodeCanonicalJson($bytes));
            $context=new TenantContext(
                'SYNTHETIC_CRM','SYNTHETIC_TENANT_A',
                'SYNTHETIC_PRINCIPAL_A','SYNTHETIC_PURPOSE_A',
                'SYNTHETIC_LEAD_A'
            );
            $request->assertMatches($context);
            foreach ($fields as $field) {
                self::assertContains($field,$constant->getValue());
            }
        }
    }

    public function test_personal_fields_or_duplicate_fields_are_rejected_by_agents_parser(): void
    {
        foreach ([
            ['contact_email'],['secret_note'],['ref','contact_email'],
            ['stage','stage'],[],['ref','stage','secret_note'],
        ] as $fields) {
            $denied=false;
            try {
                new MockCrmReadRequest(self::canonicalWire($fields));
            } catch (MockError) {
                $denied=true;
            }
            self::assertTrue($denied,'Unsafe projection must deny.');
        }
    }

    public function test_noncanonical_or_duplicate_wire_names_are_rejected(): void
    {
        $wire=self::canonicalWire(['ref','stage']);
        $canonical=MockCrmReadRequest::encodeCanonicalJson($wire);
        self::assertSame($wire,MockCrmReadRequest::decodeCanonicalJson($canonical));
        $cases=[
            $canonical."\n",
            ' '.$canonical,
            str_replace(
                '"operation":',
                '"tenant_ref":"SYNTHETIC_OTHER", "operation":',
                $canonical
            ),
            str_replace('"fields":["ref","stage"]',
                '"fields":["ref","ref"]',$canonical),
        ];
        foreach ($cases as $invalid) {
            $denied=false;
            try {
                MockCrmReadRequest::decodeCanonicalJson($invalid);
            } catch (MockError) {
                $denied=true;
            }
            self::assertTrue($denied,'Noncanonical source must not parse.');
        }
    }

    public function test_no_agent_ingress_or_route_was_enabled_by_this_contract(): void
    {
        $agentRoutes=collect(Route::getRoutes())->filter(
            static fn($route): bool => str_contains(
                strtolower($route->uri()),'agents/op01'
            )
        );
        self::assertCount(0,$agentRoutes);
        self::assertTrue(class_exists(Op01SyntheticLeadProjectionLab::class));
        self::assertFalse(Route::has('agents.op01.read'));
    }
}
