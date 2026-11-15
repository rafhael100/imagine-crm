<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\AiaReadOnlyMockBoundary;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F245: explicit NO-GO release guard. No active CRM/Agents business API
 * is authorized by the F232-F244 synthetic cryptographic experiments.
 * These tests run in the entire suite without special PG lab flags.
 */
final class AiaNoGoHttpBoundaryTest extends TestCase
{
    public function test_f245_no_real_service_or_crm_lead_route_can_be_published(): void
    {
        // The F0 branch is restricted to the Laravel /up health check.
        // A route registered for Agents, service read or CRM leads MUST
        // fail this test until independent security gates approve it.
        foreach (Route::getRoutes() as $route) {
            $path = strtolower(trim($route->uri(), '/'));
            $name = strtolower((string) $route->getName());
            $this->assertFalse(
                preg_match(
                    '~(^|/)(api|agents|aia|ai-agents|crm-leads|leads)(/|$)~',
                    $path
                ) === 1,
                'NO-GO: unexpected active business route '.$path
            );
            $this->assertFalse(
                str_contains($name, 'agents.')
                    || str_contains($name, 'aia.')
                    || str_contains($name, 'crm.leads.'),
                'NO-GO: unexpected named business route '.$name
            );
        }

        $body = [
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F245_DENY',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'principal_ref'=>'SYNTHETIC_AIA_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_CRM_LEAD',
            'purpose_ref'=>'SYNTHETIC_PROBE',
            'fields'=>['ref','stage'],
        ];
        foreach ([
            '/api/agents/crm/read',
            '/api/aia/lead/read',
            '/api/v1/agents/leads',
            '/api/crm/leads',
            '/api/v1/ai-agents/crm/leads',
        ] as $url) {
            $this->withHeaders([
                'Authorization'=>'Bearer SYNTHETIC_F245_NOT_A_CREDENTIAL',
                'X-Imagination-Tenant'=>'SYNTHETIC_TENANT_A',
                'X-Imagination-Key-Id'=>'SYNTHETIC_F242_KEY_A',
            ])->postJson($url,$body)->assertNotFound();
            $this->getJson($url)->assertNotFound();
        }
    }

    public function test_f245_public_mock_never_admits_signed_or_unsigned_input(): void
    {
        $request = [
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F245_DENY',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'principal_ref'=>'SYNTHETIC_AIA_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_CRM_LEAD',
            'purpose_ref'=>'SYNTHETIC_PROBE',
            'fields'=>['ref','stage'],
        ];
        $payloads = [
            json_encode($request,JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            '{}',
            '{"tenant_ref":"SYNTHETIC_TENANT_B","unauthorized":true}',
            '',
        ];
        foreach ($payloads as $json) {
            $this->assertSame([
                'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
                'state'=>'DENIED_OR_NOT_FOUND',
                'projection'=>null,
                'real_authorization'=>false,
                'external_calls'=>0,
            ],AiaReadOnlyMockBoundary::inspectCanonicalJson($json));
        }
    }
}
