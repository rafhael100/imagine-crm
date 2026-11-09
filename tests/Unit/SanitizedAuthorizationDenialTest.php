<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\AuthorizationEnvelope;
use App\Core\Authorization\AuthorizationIntent;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\AuthorizationCases as Cases;

/**
 * CORE02-MX-03: denial telemetry is a MINIMAL internal signal.
 * Fixtures are synthetic; this is not an audit implementation or a grant.
 */
final class SanitizedAuthorizationDenialTest extends TestCase
{
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';
    private const FIELD_CLASSES = [
        'lab_id' => 'F-ID',
        'lab_status' => 'F-BIZ',
        'lab_person' => 'F-PII',
        'lab_restricted' => 'F-RES',
        'lab_consent' => 'F-CONS',
        'lab_amount' => 'F-FIN',
        'lab_secret' => 'F-SEC',
        'lab_evidence' => 'F-EVID',
        'lab_artifact' => 'F-ART',
    ];

    public function test_all_denial_signals_omit_tenant_principal_fields_actions_and_channel(): void
    {
        $context = new TenantContext(Cases::TENANT, Cases::SUBJECT, self::CORRELATION);
        $principal = new PrincipalReference(Cases::SUBJECT);
        $membership = new MembershipSnapshot(Cases::TENANT, Cases::SUBJECT, MembershipStatus::Active, 1);
        $authorizer = new DenyByDefaultAuthorizer();
        $count = 0;

        foreach (['read', 'search', 'update', 'export', 'report', 'execute'] as $action) {
            foreach (self::FIELD_CLASSES as $field => $class) {
                foreach (['interactive', 'job', 'ai', 'support'] as $channel) {
                    foreach ([
                        'tenant' => Cases::TENANT,
                        'record' => Cases::RECORD,
                    ] as $scope => $scopeId) {
                        $intent = AuthorizationIntent::fromArray(array_replace(Cases::intent(), [
                            'action' => $action,
                            'fields' => [$field => $class],
                            'scope' => ['kind' => $scope, 'id' => $scopeId],
                            'channel' => $channel,
                        ]));
                        $envelope = new AuthorizationEnvelope($context, $principal, $membership, $intent);
                        $decision = $authorizer->decide(new AccessRequest(
                            $context, 'lab.record', $action, envelope: $envelope,
                        ));

                        self::assertFalse($decision->allowed);
                        self::assertContains($decision->reasonCode, [
                            'POLICY_NOT_CONFIGURED',
                            'SCOPE_NOT_VERIFIED',
                            'CHANNEL_AUTHORITY_MISSING',
                        ]);
                        self::assertSame([
                            'code' => $decision->reasonCode,
                            'correlation_id' => self::CORRELATION,
                        ], $decision->signal());

                        $encoded = json_encode($decision, JSON_THROW_ON_ERROR);
                        $signal = json_encode($decision->signal(), JSON_THROW_ON_ERROR);
                        foreach ([
                            Cases::TENANT, Cases::SUBJECT, Cases::RECORD,
                            'lab_', 'F-', 'lab.record', $field, $class,
                            'membership', 'principal', 'field', 'resource', 'scope', 'channel',
                            'action', 'secret', 'token', 'role', 'email', 'phone',
                        ] as $forbidden) {
                            self::assertStringNotContainsString($forbidden, $encoded);
                            self::assertStringNotContainsString($forbidden, $signal);
                        }
                        $count++;
                    }
                }
            }
        }

        self::assertSame(432, $count);
        self::assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_arbitrary_payload_in_invalid_contract_is_never_reflected_by_exception(): void
    {
        $sensitiveFixtures = [
            'example-person@example.invalid',
            'synthetic-secret-do-not-log',
            'lead-sensitive-note-fake',
            'tenant-from-header',
        ];

        foreach ($sensitiveFixtures as $unsafe) {
            $input = Cases::intent();
            $input['grant'] = $unsafe;
            try {
                AuthorizationIntent::fromArray($input);
                self::fail('Unknown proof claim was accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Invalid authorization contract.', $exception->getMessage());
                foreach ($sensitiveFixtures as $secret) {
                    self::assertStringNotContainsString($secret, $exception->getMessage());
                }
            }
        }
    }

    public function test_invalid_correlation_never_enters_signal_even_when_request_contains_it(): void
    {
        $context = new TenantContext(
            Cases::TENANT,
            Cases::SUBJECT,
            'invented-person@example.invalid',
        );
        $decision = (new DenyByDefaultAuthorizer())->decide(
            new AccessRequest($context, 'lab.record', 'export'),
        );

        self::assertFalse($decision->allowed);
        self::assertSame(['code' => 'POLICY_NOT_CONFIGURED', 'correlation_id' => null], $decision->signal());
        self::assertStringNotContainsString(
            'invented-person@example.invalid',
            json_encode($decision, JSON_THROW_ON_ERROR),
        );
    }

    public function test_unknown_reason_and_unsafe_correlation_are_both_reduced_to_closed_codes(): void
    {
        $decision = AuthorizationDecision::deny(
            'sensitive-client-note-in-reason',
            'client-address@example.invalid',
        );
        self::assertFalse($decision->allowed);
        self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
        self::assertSame(['code' => 'CONTRACT_INVALID', 'correlation_id' => null], $decision->signal());
        $encoded = json_encode($decision, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('sensitive-client-note-in-reason', $encoded);
        self::assertStringNotContainsString('client-address@example.invalid', $encoded);
    }
}
