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
use Tests\Fixtures\AuthorizationCases as Cases;

/**
 * CORE02-MX-01: exhaustive DENY regression over the EXISTING laboratory
 * resource catalog. No actual CRM roles, grants, entitlements or client data.
 */
final class AuthorizationDenyMatrixCoverageTest extends TestCase
{
    private const FIELDS = [
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

    /** @return array{TenantContext, PrincipalReference, MembershipSnapshot} */
    private static function fakeContext(): array
    {
        $context = new TenantContext(Cases::TENANT, Cases::SUBJECT, Cases::RECORD);

        return [
            $context,
            new PrincipalReference(Cases::SUBJECT),
            new MembershipSnapshot(Cases::TENANT, Cases::SUBJECT, MembershipStatus::Active, 1),
        ];
    }

    public function test_full_action_field_scope_and_channel_matrix_never_produces_allow(): void
    {
        [$context, $subject, $membership] = self::fakeContext();
        $catalog = new ExperimentalResourceCatalog();
        $authorizer = new DenyByDefaultAuthorizer();
        $counts = [
            'POLICY_NOT_CONFIGURED' => 0,
            'SCOPE_NOT_VERIFIED' => 0,
            'CHANNEL_AUTHORITY_MISSING' => 0,
        ];
        $total = 0;

        foreach (['read', 'search', 'update', 'export', 'report', 'execute'] as $action) {
            foreach (self::FIELDS as $field => $classification) {
                foreach ([
                    'tenant' => Cases::TENANT,
                    'record' => Cases::RECORD,
                    'team' => Cases::OTHER,
                    'unit' => Cases::OTHER,
                ] as $scope => $scopeId) {
                    foreach (['interactive', 'job', 'ai', 'support'] as $channel) {
                        $description = array_replace(Cases::intent(), [
                            'action' => $action,
                            'fields' => [$field => $classification],
                            'scope' => ['kind' => $scope, 'id' => $scopeId],
                            'channel' => $channel,
                        ]);
                        $intent = AuthorizationIntent::fromArray($description);
                        self::assertTrue($catalog->describes($intent));
                        $envelope = new AuthorizationEnvelope($context, $subject, $membership, $intent);
                        $decision = $authorizer->decide(
                            new AccessRequest($context, $intent->resource, $action, envelope: $envelope),
                        );

                        $expected = $channel !== 'interactive'
                            ? 'CHANNEL_AUTHORITY_MISSING'
                            : ($scope !== 'tenant' ? 'SCOPE_NOT_VERIFIED' : 'POLICY_NOT_CONFIGURED');

                        self::assertFalse($decision->allowed, $action.'/'.$field.'/'.$scope.'/'.$channel);
                        self::assertSame($expected, $decision->reasonCode);
                        self::assertSame(['code', 'correlation_id'], array_keys($decision->signal()));
                        self::assertSame(Cases::RECORD, $decision->signal()['correlation_id']);
                        $counts[$expected]++;
                        $total++;
                    }
                }
            }
        }

        self::assertSame(864, $total);
        self::assertSame([
            'POLICY_NOT_CONFIGURED' => 54,
            'SCOPE_NOT_VERIFIED' => 162,
            'CHANNEL_AUTHORITY_MISSING' => 648,
        ], $counts);
        self::assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_all_nine_field_classes_together_still_never_grant_access(): void
    {
        [$context, $subject, $membership] = self::fakeContext();
        $intent = AuthorizationIntent::fromArray(array_replace(Cases::intent(), [
            'action' => 'export',
            'fields' => self::FIELDS,
        ]));
        self::assertTrue((new ExperimentalResourceCatalog())->describes($intent));
        $envelope = new AuthorizationEnvelope($context, $subject, $membership, $intent);
        $decision = (new DenyByDefaultAuthorizer())->decide(
            new AccessRequest($context, 'lab.record', 'export', envelope: $envelope),
        );

        self::assertFalse($decision->allowed);
        self::assertSame('POLICY_NOT_CONFIGURED', $decision->reasonCode);
    }

    public function test_field_specific_request_and_replayed_envelope_fail_closed(): void
    {
        [$context, $subject, $membership] = self::fakeContext();
        $intent = AuthorizationIntent::fromArray(Cases::intent());
        $envelope = new AuthorizationEnvelope($context, $subject, $membership, $intent);
        $auth = new DenyByDefaultAuthorizer();

        foreach (['lab_status', 'lab_secret', 'lab_person', '*'] as $field) {
            $decision = $auth->decide(
                new AccessRequest($context, 'lab.record', 'read', field: $field, envelope: $envelope),
            );
            self::assertFalse($decision->allowed);
            self::assertSame('CONTRACT_INVALID', $decision->reasonCode);
        }

        // Even equal string values do not make a different unverified context
        // object carry evidence from an earlier request.
        $reconstructed = new TenantContext(Cases::TENANT, Cases::SUBJECT, Cases::RECORD);
        $replayed = $auth->decide(new AccessRequest($reconstructed, 'lab.record', 'read', envelope: $envelope));
        self::assertFalse($replayed->allowed);
        self::assertSame('CONTRACT_INVALID', $replayed->reasonCode);

        $wrongTenant = new TenantContext(Cases::OTHER, Cases::SUBJECT, Cases::RECORD);
        $crossTenant = $auth->decide(new AccessRequest($wrongTenant, 'lab.record', 'read', envelope: $envelope));
        self::assertFalse($crossTenant->allowed);
        self::assertSame('CONTRACT_INVALID', $crossTenant->reasonCode);
    }

    public function test_inactive_or_mismatched_memberships_cannot_be_wrapped_in_envelope(): void
    {
        [$context, $subject] = self::fakeContext();
        $intent = AuthorizationIntent::fromArray(Cases::intent());

        foreach ([
            new MembershipSnapshot(Cases::TENANT, Cases::SUBJECT, MembershipStatus::Suspended, 1),
            new MembershipSnapshot(Cases::TENANT, Cases::SUBJECT, MembershipStatus::Disabled, 1),
            new MembershipSnapshot(Cases::OTHER, Cases::SUBJECT, MembershipStatus::Active, 1),
            new MembershipSnapshot(Cases::TENANT, Cases::OTHER, MembershipStatus::Active, 1),
        ] as $membership) {
            try {
                new AuthorizationEnvelope($context, $subject, $membership, $intent);
                self::fail('Forged or inactive membership wrapped as an envelope.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Invalid authorization contract.', $exception->getMessage());
            }
        }
    }

    public function test_forged_role_approval_and_tenant_proof_claims_cannot_extend_intent(): void
    {
        foreach ([
            'role' => 'tenant_admin',
            'roles' => ['tenant_admin'],
            'is_admin' => true,
            'tenant_verified' => true,
            'membership_verified' => true,
            'mfa_verified' => true,
            'field_grants' => ['lab_person' => true],
            'allow' => true,
            'support_mandate' => Cases::RECORD,
            'service_principal' => Cases::SUBJECT,
        ] as $key => $value) {
            $description = Cases::intent();
            $description[$key] = $value;
            try {
                AuthorizationIntent::fromArray($description);
                self::fail('Forged '.$key.' was accepted as an intent property.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Invalid authorization contract.', $exception->getMessage());
            }
        }
    }
}
