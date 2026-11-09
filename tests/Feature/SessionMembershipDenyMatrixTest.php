<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantAccessBoundary;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Tests\Concerns\SyntheticIam;
use Tests\Fixtures\AuthorizationCases as Cases;
use Tests\TestCase;

/**
 * CORE02-MX-02: real laboratory session, invented membership repository,
 * real DENY policy. No production authorization or real CRM records.
 *
 * The physical PostgreSQL membership/RLS path is tested independently.
 */
final class SessionMembershipDenyMatrixTest extends TestCase
{
    use SyntheticIam;

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

    private function fakeMemberships(): ActiveMembershipStore
    {
        // Explicitly a TEST DOUBLE: it supplies a description, never a
        // verified database association and never a grant.
        return new class implements ActiveMembershipStore {
            public int $readCount = 0;
            public MembershipStatus $status = MembershipStatus::Active;
            public bool $wrongOrganization = false;

            public function within(PrincipalReference $principal, string $tenantId, Closure $operation): mixed
            {
                $this->readCount++;

                return $operation(new MembershipSnapshot(
                    $this->wrongOrganization ? Cases::OTHER : $tenantId,
                    $principal->principalId,
                    $this->status,
                    7,
                ));
            }
        };
    }

    public function test_authenticated_session_and_fake_active_membership_never_execute_any_action_or_field(): void
    {
        $this->login();
        $store = $this->fakeMemberships();
        $boundary = new TenantAccessBoundary(
            $this->app->make(GuardPrincipalSource::class),
            $store,
            new DenyByDefaultAuthorizer(),
        );
        $effects = 0;
        $tested = 0;

        foreach (['read', 'search', 'update', 'export', 'report', 'execute'] as $action) {
            foreach (self::FIELDS as $field => $class) {
                $intent = array_replace(Cases::intent(), [
                    'action' => $action,
                    'fields' => [$field => $class],
                ]);
                try {
                    $boundary->executeContract(Cases::TENANT, $intent, function () use (&$effects): void {
                        $effects++;
                    });
                    self::fail('A synthetic membership ran an effect: '.$action.'/'.$field);
                } catch (AuthorizationException $exception) {
                    self::assertSame('Protected operation denied.', $exception->getMessage());
                }

                $tested++;
            }
        }

        self::assertSame(54, $tested);
        self::assertSame(54, $store->readCount);
        self::assertSame(0, $effects);
    }

    public function test_membership_revocation_or_cross_organization_mismatch_never_runs_a_callback(): void
    {
        $this->login();
        $store = $this->fakeMemberships();
        $boundary = new TenantAccessBoundary(
            $this->app->make(GuardPrincipalSource::class),
            $store,
            new DenyByDefaultAuthorizer(),
        );

        $effects = 0;
        $run = function () use ($boundary, &$effects): void {
            $boundary->executeContract(
                Cases::TENANT,
                Cases::intent(),
                function () use (&$effects): void { $effects++; },
            );
        };

        try {
            $run();
            self::fail('A fake active membership must remain denied.');
        } catch (AuthorizationException $exception) {
            self::assertSame('Protected operation denied.', $exception->getMessage());
        }

        foreach ([MembershipStatus::Suspended, MembershipStatus::Disabled] as $status) {
            $store->status = $status;
            try {
                $run();
                self::fail('Inactive organization membership must deny.');
            } catch (AuthorizationException $exception) {
                self::assertSame('Tenant access denied.', $exception->getMessage());
            }
        }

        $store->status = MembershipStatus::Active;
        $store->wrongOrganization = true;
        try {
            $run();
            self::fail('A membership from the wrong organization must deny.');
        } catch (AuthorizationException $exception) {
            self::assertSame('Tenant access denied.', $exception->getMessage());
        }

        self::assertSame(4, $store->readCount);
        self::assertSame(0, $effects);
    }

    public function test_claimed_principal_cannot_override_session_and_logout_drops_existing_authority(): void
    {
        $this->login();
        $store = $this->fakeMemberships();
        $boundary = new TenantAccessBoundary(
            $this->app->make(GuardPrincipalSource::class),
            $store,
            new DenyByDefaultAuthorizer(),
        );
        $effects = 0;

        try {
            $boundary->executeContract(
                Cases::TENANT,
                Cases::intent(),
                function () use (&$effects): void { $effects++; },
                expectedPrincipalId: Cases::OTHER,
            );
            self::fail('A conflicting expected principal must deny.');
        } catch (AuthorizationException $exception) {
            self::assertSame('Protected operation denied.', $exception->getMessage());
        }
        self::assertSame(0, $store->readCount);

        $this->auth->logout();
        try {
            $boundary->executeContract(
                Cases::TENANT,
                Cases::intent(),
                function () use (&$effects): void { $effects++; },
            );
            self::fail('A logged-out identity must deny.');
        } catch (AuthenticationException) {
            self::assertSame(0, $store->readCount);
        }

        self::assertSame(0, $effects);
    }
}
