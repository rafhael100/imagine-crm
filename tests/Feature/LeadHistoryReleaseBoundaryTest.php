<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Leads\SyntheticLeadHistoryAssessment;
use App\Core\Leads\SyntheticLeadHistoryClaim;
use App\Core\Leads\SyntheticLeadHistoryInspector;
use App\Core\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F3-27: regression-only release tripwire for the new inert history lab.
 * It cannot authenticate an actor, authorize a history reader or review
 * migrations. Every assertion must fail before accidental public exposure.
 */
final class LeadHistoryReleaseBoundaryTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const OTHER = '22222222-2222-4222-8222-222222222222';
    private const LEAD = '33333333-3333-4333-8333-333333333333';
    private const ACTOR = '44444444-4444-4444-8444-444444444444';
    private const EVENT = '55555555-5555-4555-8555-555555555555';
    private const CORRELATION = '66666666-6666-4666-8666-666666666666';

    public function test_no_history_timeline_events_or_lead_record_routes_are_public(): void
    {
        foreach (Route::getRoutes() as $route) {
            $path = trim($route->uri(), '/');
            $this->assertDoesNotMatchRegularExpression(
                '~(?:^|/)(?:lead|leads|contact|contacts|history|histories|timeline|activity|activities|events)(?:/|$)~i',
                $path,
                "Unexpected lead or history route: {$path}",
            );
        }
    }

    public function test_structurally_consistent_fake_history_never_qualifies_as_read_or_export_authority(): void
    {
        $claim = new SyntheticLeadHistoryClaim(
            self::TENANT, self::LEAD, self::EVENT, self::ACTOR, 1, 'created',
        );

        $assessment = (new SyntheticLeadHistoryInspector())->inspect(self::TENANT, self::LEAD, [$claim]);
        $this->assertSame('PROVENANCE_UNVERIFIED', $assessment->reasonCode);
        $this->assertSame(['code' => 'PROVENANCE_UNVERIFIED'], $assessment->signal());
        $this->assertFalse($assessment->canReadHistory());
        $this->assertFalse($assessment->canExportHistory());
        $this->assertFalse($assessment->canWriteHistory());
        $this->assertFalse($assessment->canProveActorOrChronology());

        $context = new TenantContext(self::TENANT, self::ACTOR, self::CORRELATION);
        $authorizer = new DenyByDefaultAuthorizer();

        foreach (['read', 'search', 'export', 'update', 'delete'] as $action) {
            $decision = $authorizer->decide(new AccessRequest($context, 'lead.history', $action));

            $this->assertInstanceOf(AuthorizationDecision::class, $decision);
            $this->assertFalse($decision->allowed);
            $this->assertSame('POLICY_NOT_CONFIGURED', $decision->reasonCode);
        }

        $this->assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_mismatched_tenant_and_reused_event_are_denied_without_payload(): void
    {
        $inspector = new SyntheticLeadHistoryInspector();
        $claim = new SyntheticLeadHistoryClaim(self::TENANT, self::LEAD, self::EVENT, self::ACTOR, 1, 'created');

        $crossTenant = $inspector->inspect(self::OTHER, self::LEAD, [$claim]);
        $this->assertSame('HISTORY_SCOPE_MISMATCH', $crossTenant->reasonCode);
        $this->assertFalse($crossTenant->canReadHistory());
        $this->assertFalse($crossTenant->canExportHistory());

        $replay = $inspector->inspect(self::TENANT, self::LEAD, [$claim, $claim]);
        $this->assertSame('EVENT_REFERENCE_REUSED', $replay->reasonCode);
        $this->assertFalse($replay->canWriteHistory());

        foreach ([$crossTenant, $replay] as $outcome) {
            $json = json_encode($outcome->signal(), JSON_THROW_ON_ERROR);
            foreach ([self::TENANT, self::OTHER, self::LEAD, self::EVENT, self::ACTOR] as $id) {
                $this->assertStringNotContainsString($id, $json);
            }
        }
    }

    public function test_unknown_or_missing_actor_and_out_of_order_history_can_never_be_authority(): void
    {
        $inspector = new SyntheticLeadHistoryInspector();
        $noActor = new SyntheticLeadHistoryClaim(
            self::TENANT, self::LEAD, self::EVENT, null, 1, 'created',
        );
        $outOfOrder = new SyntheticLeadHistoryClaim(
            self::TENANT, self::LEAD, '77777777-7777-4777-8777-777777777777',
            self::ACTOR, 3, 'assigned',
        );

        $missing = $inspector->inspect(self::TENANT, self::LEAD, [$noActor]);
        $sequence = $inspector->inspect(self::TENANT, self::LEAD, [
            new SyntheticLeadHistoryClaim(self::TENANT, self::LEAD, self::EVENT, self::ACTOR, 1, 'created'),
            $outOfOrder,
        ]);

        $this->assertSame('ACTOR_REFERENCE_MISSING', $missing->reasonCode);
        $this->assertSame('HISTORY_ORDER_UNVERIFIED', $sequence->reasonCode);
        foreach ([$missing, $sequence] as $assessment) {
            $this->assertFalse($assessment->canReadHistory());
            $this->assertFalse($assessment->canExportHistory());
            $this->assertFalse($assessment->canProveActorOrChronology());
            $this->assertSame(['code' => $assessment->reasonCode], $assessment->signal());
        }
    }

    public function test_lab_history_api_has_no_persistence_or_authorization_entrypoint(): void
    {
        $this->assertSame(['inspect'], get_class_methods(SyntheticLeadHistoryInspector::class));
        foreach ([SyntheticLeadHistoryInspector::class, SyntheticLeadHistoryClaim::class,
            SyntheticLeadHistoryAssessment::class] as $class) {
            foreach ([
                'load', 'read', 'write', 'save', 'persist', 'import', 'export', 'restore',
                'delete', 'merge', 'fetch', 'authorize', 'grant', 'allow', 'publish', 'dispatch',
            ] as $method) {
                $this->assertFalse(method_exists($class, $method), $class.'::'.$method);
            }
        }
        $this->assertFalse(AuthorizationDecision::deny()->allowed);
    }
}
