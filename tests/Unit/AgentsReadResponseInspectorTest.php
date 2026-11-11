<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\AgentsReadResponseAssessment;
use App\Core\Leads\AgentsReadResponseInspector;
use PHPUnit\Framework\TestCase;

final class AgentsReadResponseInspectorTest extends TestCase
{
    private static function reply(
        string $state = 'READ_SYNTHETIC_ONLY',
        mixed $projection = ['ref' => 'SYNTHETIC_A_LEAD'],
    ): array {
        return [
            'contract' => 'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state' => $state,
            'projection' => $projection,
            'real_authorization' => false,
            'external_calls' => 0,
        ];
    }

    private static function assess(mixed $response, mixed $fields = ['ref']): AgentsReadResponseAssessment
    {
        return (new AgentsReadResponseInspector())->inspect($response, $fields);
    }

    private static function assertNoAuthority(AgentsReadResponseAssessment $assessment): void
    {
        self::assertFalse($assessment->canReadRealLead());
        self::assertFalse($assessment->canUseCustomerData());
        self::assertFalse($assessment->canGrantAgentAccess());
        self::assertFalse($assessment->canWriteLead());
        self::assertFalse($assessment->canExportLead());
        self::assertFalse($assessment->canEnableRoute());
        self::assertSame(['reasonCode'], array_keys((array) $assessment));
    }

    public function test_two_positive_agent_mock_projections_are_never_a_crm_allow(): void
    {
        foreach ([
            [['ref' => 'SYNTHETIC_A_LEAD'], ['ref']],
            [['stage' => 'SYNTHETIC_STAGE', 'ref' => 'SYNTHETIC_A_LEAD'], ['stage', 'ref']],
        ] as [$projection, $fields]) {
            $assessment = self::assess(self::reply(projection: $projection), $fields);
            self::assertSame('SYNTHETIC_PROJECTION_UNTRUSTED', $assessment->reasonCode);
            self::assertTrue($assessment->isSyntheticFormatConsistent());
            self::assertNoAuthority($assessment);
        }
    }

    public function test_denied_reply_has_no_projection_and_no_business_authority(): void
    {
        $result = self::assess(self::reply('DENIED_OR_NOT_FOUND', null));
        self::assertSame('SYNTHETIC_DENIAL_ONLY', $result->reasonCode);
        self::assertTrue($result->isSyntheticFormatConsistent());
        self::assertNoAuthority($result);
        $bad = self::assess(self::reply('DENIED_OR_NOT_FOUND', ['ref' => 'SYNTHETIC_LEAD']));
        self::assertSame('PUBLIC_REPLY_INVALID', $bad->reasonCode);
        self::assertNoAuthority($bad);
    }

    public function test_hostile_outer_shape_and_authorization_spoofing_never_pass(): void
    {
        $invalid = [
            ['contract' => 'SYNTHETIC_CRM_AIA_READ_V1'],
            ['contract' => 'SYNTHETIC_CRM_AIA_PUBLIC_V2'],
            ['state' => 'ALLOW'],
            ['state' => 'READ_REAL'],
            ['real_authorization' => true],
            ['real_authorization' => 1],
            ['real_authorization' => 'false'],
            ['external_calls' => 1],
            ['external_calls' => false],
            ['external_calls' => '0'],
            ['tenant_ref' => 'SYNTHETIC_OTHER'],
            ['grant' => true],
            ['token' => 'Bearer SYNTHETIC_FAKE'],
            ['operation' => 'crm.lead.write'],
            ['email' => 'person@example.test'],
        ];
        foreach ($invalid as $change) {
            $reply = array_replace(self::reply(), $change);
            $result = self::assess($reply);
            self::assertSame('PUBLIC_REPLY_INVALID', $result->reasonCode);
            self::assertFalse($result->isSyntheticFormatConsistent());
            self::assertNoAuthority($result);
        }
        foreach (array_keys(self::reply()) as $key) {
            $reply = self::reply();
            unset($reply[$key]);
            $result = self::assess($reply);
            self::assertSame('PUBLIC_REPLY_INVALID', $result->reasonCode);
            self::assertNoAuthority($result);
        }
        foreach ([null, false, 0, '', [], ['x'], new \stdClass()] as $body) {
            self::assertSame('PUBLIC_REPLY_INVALID', self::assess($body)->reasonCode);
        }
    }

    public function test_only_exact_requested_synthetic_projection_is_recognized(): void
    {
        foreach ([
            [['stage' => 'SYNTHETIC_STAGE'], ['ref']],
            [['ref' => 'SYNTHETIC_A', 'stage' => 'SYNTHETIC_STAGE'], ['stage', 'ref']],
            [['email' => 'x@example.test'], ['email']],
            [['ref' => 'REAL_CUSTOMER_ID'], ['ref']],
            [['ref' => 'SYNTHETIC_'], ['ref']],
            [['ref' => 'SYNTHETIC_A'."\n"], ['ref']],
            [['ref' => 123], ['ref']],
            [['ref' => null], ['ref']],
            [['ref' => true], ['ref']],
            [['ref' => ['SYNTHETIC_A']], ['ref']],
            [['ref' => 'SYNTHETIC_A'], ['ref', 'ref']],
            [['ref' => 'SYNTHETIC_A'], []],
            [['ref' => 'SYNTHETIC_A'], 'ref'],
            [['ref' => 'SYNTHETIC_A'], [0 => 'ref', 2 => 'stage']],
            [['ref' => 'SYNTHETIC_A'], ['ref', 'email']],
            [['ref' => 'SYNTHETIC_A'], [[ 'ref' ]]],
            [['ref' => 'SYNTHETIC_A'], [false]],
            [[], ['ref']],
            [['ref' => str_repeat('A', 512)], ['ref']],
        ] as [$projection, $fields]) {
            $result = self::assess(self::reply(projection: $projection), $fields);
            self::assertSame('PROJECTION_INVALID', $result->reasonCode);
            self::assertNoAuthority($result);
        }
    }

    public function test_large_unknown_key_surface_is_denied_before_allocation_or_sort(): void
    {
        $reply = self::reply();
        foreach (range(1, 512) as $i) {
            $reply['unknown_'.$i] = 'SYNTHETIC_FAKE';
        }
        self::assertSame('PUBLIC_REPLY_INVALID', self::assess($reply)->reasonCode);
        self::assertNoAuthority(self::assess($reply));
    }

    public function test_public_assessment_never_echoes_untrusted_values(): void
    {
        $reply = self::reply(projection: ['ref' => 'SYNTHETIC_PRIVATE_MARKER']);
        $reply['email'] = 'sensitive@example.test';
        $result = self::assess($reply);
        $serialized = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['sensitive@example.test', 'SYNTHETIC_PRIVATE_MARKER', 'email', 'token'] as $value) {
            self::assertStringNotContainsString($value, $serialized);
        }
        self::assertNoAuthority($result);
    }
}
