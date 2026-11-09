<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Leads\SyntheticDeliveryEvidenceAssessment;
use App\Core\Leads\SyntheticIdempotencyCasAssessment;
use App\Core\Leads\SyntheticLeadEnvelopeV1Assessment;
use App\Core\Leads\SyntheticLeadReceiptMacAssessment;
use App\Core\Leads\SyntheticReconciliationAssessment;
use App\Core\Leads\SyntheticRetentionWindowAssessment;
use App\Core\Portability\SyntheticImportPreviewInspector;
use App\Core\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

/**
 * Until independent CORE/SEG/DBA approval, any accidental public business
 * endpoint or synthetic-to-real capability flip MUST fail the CI gate.
 *
 * It does NOT authenticate anyone, review migrations or grant permissions.
 */
final class LeadIntakeReleaseGateTest extends TestCase
{
    public function test_no_public_mutating_route_is_published_before_security_review(): void
    {
        // Private local disk route must remain OFF until signed-file access is reviewed.
        $this->assertFalse(config('filesystems.disks.local.serve'));
        $routes = Route::getRoutes();
        $this->assertGreaterThan(0, $routes->count(), 'Expected at least the built-in /up health route.');

        foreach ($routes as $route) {
            $path = ltrim($route->uri(), '/');
            $methods = array_values(array_intersect(
                ['POST', 'PUT', 'PATCH', 'DELETE'],
                $route->methods(),
            ));

            $this->assertSame([], $methods, "Unexpected public mutating route: {$path}");
            $this->assertDoesNotMatchRegularExpression(
                '~^(?:api(?:/|$)|(?:crm/)?(?:leads|contacts|imports|exports)(?:/|$)|login(?:/|$)|register(?:/|$))~i',
                $path,
                "Public business route requires dedicated gate approval: {$path}",
            );
        }
    }

    public function test_constructing_tenant_context_never_enables_core_business_access(): void
    {
        $context = new TenantContext(
            '11111111-1111-4111-8111-111111111111',
            '22222222-2222-4222-8222-222222222222',
            '33333333-3333-4333-8333-333333333333',
        );
        $authorizer = new DenyByDefaultAuthorizer();

        foreach (['create', 'read', 'update', 'export', 'delete'] as $action) {
            $decision = $authorizer->decide(new AccessRequest($context, 'lead', $action));

            $this->assertFalse($decision->allowed);
            $this->assertSame('POLICY_NOT_CONFIGURED', $decision->reasonCode);
        }
    }

    public function test_core_decision_has_no_public_allow_constructor_or_factory(): void
    {
        $reflection = new ReflectionClass(AuthorizationDecision::class);

        $this->assertFalse($reflection->getConstructor()->isPublic());
        $this->assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
        $this->assertFalse(AuthorizationDecision::deny()->allowed);
    }

    public function test_all_lab_receipt_reconciliation_and_ledger_assessments_remain_inert(): void
    {
        // Synthetic descriptions are NOT real authorization, persistence,
        // identity checks, idempotency, delivery or replay protection.
        $envelope = new SyntheticLeadEnvelopeV1Assessment('STRUCTURE_ONLY_UNVERIFIED');
        $mac = new SyntheticLeadReceiptMacAssessment('LAB_MAC_MATCH_UNTRUSTED');
        $cas = new SyntheticIdempotencyCasAssessment('ATOMIC_STORE_REQUIRED', 1);
        $reconcile = new SyntheticReconciliationAssessment('DUAL_RECEIVER_CLAIMS_REVIEW');
        $delivery = new SyntheticDeliveryEvidenceAssessment('TRUSTED_EVIDENCE_REQUIRED');
        $retention = new SyntheticRetentionWindowAssessment('EXPIRY_RECONCILIATION_REQUIRED', true, true, true);

        $this->assertFalse($envelope->canAcceptSubmission());
        $this->assertFalse($envelope->canAcknowledgeDurableReceipt());
        $this->assertFalse($envelope->canDeliverToCrm());

        $this->assertTrue($mac->isMacConsistentWithFixture());
        $this->assertFalse($mac->canAcceptLead());
        $this->assertFalse($mac->canAcknowledgeDurableReceipt());
        $this->assertFalse($mac->canDeliverToCrm());
        $this->assertFalse($mac->canTreatAsReplaySafe());

        $this->assertFalse($cas->canCommitReservation());
        $this->assertFalse($cas->canRetryDelivery());
        $this->assertFalse($cas->canCreateLead());

        $this->assertFalse($reconcile->canMarkDelivered());
        $this->assertFalse($reconcile->canScheduleRetry());
        $this->assertFalse($reconcile->canDeleteTemporaryPayload());
        $this->assertFalse($reconcile->canAcknowledgeVisitor());

        $this->assertFalse($delivery->canAcknowledgeVisitor());
        $this->assertFalse($delivery->canMarkDeliveredToCrm());
        $this->assertFalse($delivery->canRetryAutomatically());

        $this->assertFalse($retention->canDeleteTemporaryPayload());
        $this->assertFalse($retention->canRetryAutomatically());
        $this->assertFalse($retention->canSendNotification());

        $preview = (new SyntheticImportPreviewInspector())->inspect(['label'], [['Sample']]);
        $this->assertFalse($preview->canImport());
    }
}
