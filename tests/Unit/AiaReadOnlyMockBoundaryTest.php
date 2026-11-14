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

    public function test_canonical_json_bytes_are_validated_without_any_grant(): void
    {
        $valid = self::validWire();
        $body = json_encode(
            $valid,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        self::assertTrue(AiaReadOnlyMockBoundary::hasCanonicalFixtureShape($body));
        self::assertDenied($valid);
        self::assertSame(AiaReadOnlyMockBoundary::inspect($valid),
            AiaReadOnlyMockBoundary::inspectCanonicalJson($body));
        foreach ([['ref'], ['stage'], ['stage', 'ref']] as $fields) {
            $valid['fields'] = $fields;
            $raw = json_encode($valid, JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            self::assertTrue(AiaReadOnlyMockBoundary::hasCanonicalFixtureShape($raw));
            self::assertSame(AiaReadOnlyMockBoundary::inspect($valid),
                AiaReadOnlyMockBoundary::inspectCanonicalJson($raw));
        }
    }

    public function test_canonical_json_rejects_ambiguity_and_remains_uniform(): void
    {
        $wire = self::validWire();
        $raw = json_encode($wire, JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $extra = $wire; $extra['is_admin'] = true;
        $unknown = $wire; $unknown['fields'] = ['email'];
        $reordered = array_reverse($wire, true);
        $dupe = $wire; $dupe['fields'] = ['ref', 'ref'];
        foreach ([
            'duplicate key' => substr($raw, 0, -1)
                . ',"tenant_ref":"SYNTHETIC_TENANT_A"}',
            'whitespace' => str_replace(',"request_ref"', ', "request_ref"', $raw),
            'escape alias' => str_replace('SYNTHETIC_CRM_AIA_READ_V1',
                '\u0053YNTHETIC_CRM_AIA_READ_V1', $raw),
            'trailing json' => $raw . '{}',
            'wrong key order' => json_encode($reordered, JSON_THROW_ON_ERROR),
            'forged admin key' => json_encode($extra, JSON_THROW_ON_ERROR),
            'unknown personal field' => json_encode($unknown, JSON_THROW_ON_ERROR),
            'duplicate field' => json_encode($dupe, JSON_THROW_ON_ERROR),
            'oversized' => str_repeat('X', 2048),
            'invalid UTF-8' => $raw . "\xFF",
            'nested flood' => '{"fields":' . str_repeat('[', 9) .
                'null' . str_repeat(']', 9) . '}',
            'array root' => '[]',
            'null root' => 'null',
            'empty' => '',
        ] as $label => $bad) {
            self::assertFalse(AiaReadOnlyMockBoundary::hasCanonicalFixtureShape($bad),
                'Unexpected accepted mock input: '.$label);
            self::assertSame(
                AiaReadOnlyMockBoundary::inspect($wire),
                AiaReadOnlyMockBoundary::inspectCanonicalJson($bad),
                'Denial unexpectedly reveals schema status: '.$label
            );
        }
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

    public function test_oversized_unknown_key_surface_cannot_enable_read(): void
    {
        $flood = self::validWire();
        for ($i = 0; $i < 512; ++$i) {
            $flood['unknown_'.$i] = 'SYNTHETIC_FORGED_GRANT';
        }
        self::assertFalse(AiaReadOnlyMockBoundary::hasFixtureShape($flood));
        self::assertDenied($flood);

        foreach ([[['ref']], [true], [42], [null]] as $fields) {
            $wire = self::validWire();
            $wire['fields'] = $fields;
            self::assertFalse(AiaReadOnlyMockBoundary::hasFixtureShape($wire));
            self::assertDenied($wire);
        }
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
