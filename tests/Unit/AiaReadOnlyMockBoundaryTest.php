<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Synthetic\AiaReadOnlyMockBoundary;
use PHPUnit\Framework\TestCase;

final class AiaReadOnlyMockBoundaryTest extends TestCase
{
    private static function validWire(): array
    {
        return [
            'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref' => 'SYNTHETIC_REQ_1',
            'operation' => 'crm.lead.read_minimal',
            'tenant_ref' => 'SYNTHETIC_TENANT_A',
            'principal_ref' => 'SYNTHETIC_USER_A',
            'resource_ref' => 'SYNTHETIC_LEAD_A',
            'purpose_ref' => 'SYNTHETIC_DEMO',
            'fields' => ['ref', 'stage'],
        ];
    }

    private static function assertDenied(array $request): void
    {
        self::assertSame([
            'contract' => 'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state' => 'DENIED_OR_NOT_FOUND',
            'projection' => null,
            'real_authorization' => false,
            'external_calls' => 0,
        ], AiaReadOnlyMockBoundary::inspect($request));
    }

    public function test_valid_agents_f1_12_request_remains_denied(): void
    {
        self::assertTrue(AiaReadOnlyMockBoundary::hasFixtureShape(self::validWire()));
        self::assertDenied(self::validWire());
    }

    public function test_single_allowed_field_and_order_permutations_are_still_denied(): void
    {
        foreach ([['ref'], ['stage'], ['stage', 'ref']] as $fields) {
            $request = self::validWire();
            $request['fields'] = $fields;
            self::assertTrue(AiaReadOnlyMockBoundary::hasFixtureShape($request));
            self::assertDenied($request);
        }
    }

    public function test_cross_tenant_claims_do_not_grant_access(): void
    {
        foreach (['SYNTHETIC_TENANT_B', 'SYNTHETIC_TENANT_A'] as $tenant) {
            $request = self::validWire();
            $request['tenant_ref'] = $tenant;
            self::assertDenied($request);
        }
    }

    public function test_mutations_roles_sensitive_fields_and_unknown_keys_remain_denied(): void
    {
        foreach ([
            ['operation' => 'crm.lead.write'],
            ['fields' => ['email']],
            ['fields' => ['ref', 'ref']],
            ['fields' => []],
            ['fields' => ['ref', 'stage', 'email']],
            ['principal_ref' => 'admin'],
            ['tenant_ref' => 'SYNTHETIC_'],
            ['purpose_ref' => str_repeat('A', 120)],
            ['is_admin' => true],
            ['role' => 'owner'],
            ['approval' => true],
            ['contract' => 'SYNTHETIC_CRM_AIA_READ_V2'],
        ] as $change) {
            $request = array_replace(self::validWire(), $change);
            self::assertFalse(AiaReadOnlyMockBoundary::hasFixtureShape($request));
            self::assertDenied($request);
        }
    }

    public function test_missing_fields_malformed_types_and_numeric_keys_remain_denied(): void
    {
        foreach (array_keys(self::validWire()) as $key) {
            $request = self::validWire();
            unset($request[$key]);
            self::assertFalse(AiaReadOnlyMockBoundary::hasFixtureShape($request));
            self::assertDenied($request);
        }

        foreach ([
            ['fields' => 'ref'],
            ['fields' => [0 => 'ref', 2 => 'stage']],
            ['fields' => [null]],
            ['request_ref' => []],
            ['resource_ref' => 4],
            ['contract' => null],
        ] as $change) {
            $request = array_replace(self::validWire(), $change);
            self::assertFalse(AiaReadOnlyMockBoundary::hasFixtureShape($request));
            self::assertDenied($request);
        }

        self::assertFalse(AiaReadOnlyMockBoundary::hasFixtureShape([0 => 'SYNTHETIC_CRM_AIA_READ_V1']));
        self::assertDenied([0 => 'SYNTHETIC_CRM_AIA_READ_V1']);
    }

    public function test_response_never_includes_request_details_or_pii(): void
    {
        $request = self::validWire();
        $request['unexpected_email'] = 'private@example.test';
        self::assertFalse(AiaReadOnlyMockBoundary::hasFixtureShape($request));
        $output = json_encode(AiaReadOnlyMockBoundary::inspect($request), JSON_THROW_ON_ERROR);
        foreach ([
            'private@example.test', 'SYNTHETIC_TENANT_A', 'SYNTHETIC_USER_A',
            'SYNTHETIC_LEAD_A', 'SYNTHETIC_REQ_1',
        ] as $value) {
            self::assertStringNotContainsString($value, $output);
        }
        self::assertDenied($request);
    }
}
