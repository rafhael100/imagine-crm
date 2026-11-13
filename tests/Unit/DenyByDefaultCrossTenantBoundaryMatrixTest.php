<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\AuthorizationEnvelope;
use App\Core\Authorization\AuthorizationIntent;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Authorization\ExperimentalResourceCatalog;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * F3-204 / SEG-01: regression tests against the real existing F0 authorizer.
 *
 * Self-declared "active", an exact synthetic resource catalog match and a
 * plausible tenant UUID are never IAM/RLS evidence. No live DB, routes,
 * credential checks or ALLOW policy is introduced by these tests.
 */
final class DenyByDefaultCrossTenantBoundaryMatrixTest extends TestCase
{
    private const TENANT_A = '11111111-1111-4111-8111-111111111111';
    private const TENANT_B = '22222222-2222-4222-8222-222222222222';
    private const PRINCIPAL_A = '33333333-3333-4333-8333-333333333333';
    private const PRINCIPAL_B = '44444444-4444-4444-8444-444444444444';
    private const CORRELATION = '55555555-5555-4555-8555-555555555555';
    private const RESOURCE = '66666666-6666-4666-8666-666666666666';

    private static function context(string $tenant = self::TENANT_A, string $principal = self::PRINCIPAL_A): TenantContext
    {
        return new TenantContext($tenant, $principal, self::CORRELATION);
    }

    /** @return array<string,mixed> */
    private static function intent(
        string $action = 'read',
        string $channel = 'interactive',
        string $scope = 'tenant',
        string $scopeId = self::TENANT_A,
    ): array {
        return [
            'schema_version' => 1,
            'policy_version' => ExperimentalResourceCatalog::POLICY_VERSION,
            'resource' => 'lab.record',
            'resource_id' => self::RESOURCE,
            'action' => $action,
            'fields' => [
                'lab_id' => 'F-ID',
                'lab_person' => 'F-PII',
                'lab_consent' => 'F-CONS',
                'lab_secret' => 'F-SEC',
            ],
            'scope' => ['kind' => $scope, 'id' => $scopeId],
            'channel' => $channel,
        ];
    }

    private static function request(
        array $intent,
        ?TenantContext $requestContext = null,
        ?string $field = null,
    ): AccessRequest {
        $originalContext = self::context();
        $subject = new PrincipalReference(self::PRINCIPAL_A);
        $membership = new MembershipSnapshot(
            self::TENANT_A,
            self::PRINCIPAL_A,
            MembershipStatus::Active,
            10,
        );
        $envelope = new AuthorizationEnvelope(
            $originalContext,
            $subject,
            $membership,
            AuthorizationIntent::fromArray($intent),
        );

        return new AccessRequest(
            $requestContext ?? $originalContext,
            'lab.record',
            $intent['action'],
            $field,
            $envelope,
        );
    }

    private static function denied(AccessRequest $request, string $reason): void
    {
        $decision = (new DenyByDefaultAuthorizer())->decide($request);
        self::assertFalse($decision->allowed, 'Never permit a synthetic envelope as IAM evidence');
        self::assertSame($reason, $decision->reasonCode);
        self::assertSame(self::CORRELATION, $decision->correlationId);
        self::assertSame([
            'code' => $reason,
            'correlation_id' => self::CORRELATION,
        ], $decision->signal());
        self::assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_even_exact_catalog_match_and_active_snapshot_never_grant_actions(): void
    {
        foreach (['read', 'search', 'update', 'export', 'report', 'execute'] as $action) {
            self::assertTrue(
                (new ExperimentalResourceCatalog())->describes(AuthorizationIntent::fromArray(
                    self::intent($action),
                )),
                'Fixture must match the full synthetic catalog, not a malformed shortcut.',
            );
            self::denied(self::request(self::intent($action)), 'POLICY_NOT_CONFIGURED');
        }
    }

    public function test_all_noninteractive_channels_are_explicitly_denied_for_each_action(): void
    {
        foreach (['job', 'ai', 'support'] as $channel) {
            foreach (['read', 'search', 'update', 'export', 'report', 'execute'] as $action) {
                self::denied(
                    self::request(self::intent($action, $channel)),
                    'CHANNEL_AUTHORITY_MISSING',
                );
            }
        }
    }

    public function test_cross_tenant_scope_claim_is_rejected_before_policy(): void
    {
        self::denied(
            self::request(self::intent('read', 'interactive', 'tenant', self::TENANT_B)),
            'CONTRACT_INVALID',
        );
        // A separately constructed request context is NOT trusted even when
        // all UUID strings happen to match: the source must stay bound.
        self::denied(
            self::request(self::intent(), self::context()),
            'CONTRACT_INVALID',
        );
        self::denied(
            self::request(self::intent(), self::context(self::TENANT_B)),
            'CONTRACT_INVALID',
        );
        self::denied(
            self::request(self::intent(), self::context(self::TENANT_A, self::PRINCIPAL_B)),
            'CONTRACT_INVALID',
        );
    }

    public function test_record_team_and_unit_claims_cannot_escalate_scope(): void
    {
        foreach ([
            ['record', self::RESOURCE],
            ['team', self::TENANT_B],
            ['unit', self::TENANT_B],
        ] as [$scope, $id]) {
            self::denied(
                self::request(self::intent('read', 'interactive', $scope, $id)),
                'SCOPE_NOT_VERIFIED',
            );
        }
        self::denied(
            self::request(self::intent('read', 'interactive', 'record', self::TENANT_B)),
            'CONTRACT_INVALID',
        );
    }

    public function test_field_level_claims_are_never_authorized_even_with_matching_resource(): void
    {
        foreach (['lab_id', 'lab_person', 'lab_consent', 'lab_secret', 'name'] as $field) {
            self::denied(self::request(self::intent(), null, $field), 'CONTRACT_INVALID');
        }
    }

    public function test_catalog_policy_or_classification_spoof_does_not_grant_access(): void
    {
        $policy = self::intent();
        $policy['policy_version'] = 'lab-deny-v2';
        self::denied(self::request($policy), 'CONTRACT_INVALID');

        $classification = self::intent();
        $classification['fields']['lab_secret'] = 'F-PII';
        self::denied(self::request($classification), 'CONTRACT_INVALID');

        $resource = self::intent();
        $resource['resource'] = 'lab.other';
        // The request matches the intent for other resource; the catalog
        // still rejects the resource description as unregistered.
        $context = self::context();
        $env = new AuthorizationEnvelope(
            $context,
            new PrincipalReference(self::PRINCIPAL_A),
            new MembershipSnapshot(self::TENANT_A, self::PRINCIPAL_A, MembershipStatus::Active, 2),
            AuthorizationIntent::fromArray($resource),
        );
        self::denied(
            new AccessRequest($context, 'lab.other', 'read', null, $env),
            'CONTRACT_INVALID',
        );
    }

    public function test_missing_envelope_denied_without_exposing_identifiers(): void
    {
        $ctx = self::context();
        self::denied(new AccessRequest($ctx, 'lab.record', 'read'), 'POLICY_NOT_CONFIGURED');
        $signal = (new DenyByDefaultAuthorizer())
            ->decide(new AccessRequest($ctx, 'lab.record', 'read'))->signal();
        $json = json_encode($signal, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(self::TENANT_A, $json);
        self::assertStringNotContainsString(self::PRINCIPAL_A, $json);
    }

    public function test_suspended_membership_cannot_create_plausible_envelope(): void
    {
        foreach ([MembershipStatus::Suspended, MembershipStatus::Disabled] as $status) {
            try {
                new AuthorizationEnvelope(
                    self::context(),
                    new PrincipalReference(self::PRINCIPAL_A),
                    new MembershipSnapshot(self::TENANT_A, self::PRINCIPAL_A, $status, 1),
                    AuthorizationIntent::fromArray(self::intent()),
                );
                self::fail('Inactive membership was accepted as a synthetic envelope');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Invalid authorization contract.', $e->getMessage());
            }
        }
    }

    public function test_claims_for_another_tenant_or_principal_cannot_form_valid_envelope(): void
    {
        foreach ([
            [self::TENANT_B, self::PRINCIPAL_A],
            [self::TENANT_A, self::PRINCIPAL_B],
        ] as [$tenant, $principal]) {
            try {
                new AuthorizationEnvelope(
                    self::context(),
                    new PrincipalReference(self::PRINCIPAL_A),
                    new MembershipSnapshot($tenant, $principal, MembershipStatus::Active, 1),
                    AuthorizationIntent::fromArray(self::intent()),
                );
                self::fail('Unbound membership accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Invalid authorization contract.', $e->getMessage());
            }
        }
    }
}
