<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\AuthorizationDecision;
use App\Core\Portability\SyntheticBackupIntegrityInspector;
use App\Core\Portability\SyntheticCsvPreviewDecoder;
use App\Core\Portability\SyntheticImportReviewInspector;
use App\Core\Portability\SyntheticMappedImportDenyGate;
use App\Core\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F3-25 release tripwire: importing/exporting real records is not yet
 * authorized. These are negative assertions over inert synthetic fixtures,
 * NOT proof that the system is safe to release or authenticate users.
 */
final class PortabilityReleaseBoundaryTest extends TestCase
{
    private const OWN = '11111111-1111-4111-8111-111111111111';
    private const OTHER = '22222222-2222-4222-8222-222222222222';
    private const PERSON = '33333333-3333-4333-8333-333333333333';
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';

    public function test_no_public_portability_upload_backup_or_attachment_route_is_published(): void
    {
        // A future route needs its own CORE/SEG/AUT/LGPD review and negative
        // tests before this intentionally conservative tripwire can change.
        foreach (Route::getRoutes() as $route) {
            $path = trim($route->uri(), '/');
            $this->assertDoesNotMatchRegularExpression(
                '~(?:^|/)(?:import|imports|export|exports|portability|backup|backups|upload|uploads|attachment|attachments)(?:/|$)~i',
                $path,
                "Unexpected portability or attachment route: {$path}",
            );
        }
    }

    public function test_even_clean_decoded_csv_and_matching_fake_tenant_cannot_grant_import(): void
    {
        $mapped = (new SyntheticCsvPreviewDecoder())->previewMapped(
            "label,origin\nExample,site\n",
            ['description', 'source_system'],
            [['source' => 1, 'target' => 1], ['source' => 2, 'target' => 2]],
        );
        $review = (new SyntheticImportReviewInspector())->assess($mapped);

        $this->assertSame([], $review->findings);
        $this->assertFalse($review->needsReview());
        $this->assertFalse($review->canImport());
        $this->assertFalse($mapped->canImport());
        $this->assertFalse($mapped->mappingPreview->canImport());
        $this->assertFalse($mapped->csvPreview->canImport());

        $context = new TenantContext(self::OWN, self::PERSON, self::CORRELATION);
        $decision = (new SyntheticMappedImportDenyGate())->evaluate(
            $context, $mapped, self::OWN, 'import', [1, 2],
        );

        $this->assertInstanceOf(AuthorizationDecision::class, $decision);
        $this->assertFalse($decision->allowed);
        $this->assertSame('SCOPE_NOT_VERIFIED', $decision->reasonCode);
        $this->assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_cross_organization_extra_target_formula_and_empty_data_stay_denied(): void
    {
        $decoder = new SyntheticCsvPreviewDecoder();
        $gate = new SyntheticMappedImportDenyGate();
        $context = new TenantContext(self::OWN, self::PERSON, self::CORRELATION);
        $map = [['source' => 1, 'target' => 1], ['source' => 2, 'target' => 2]];
        $clean = $decoder->previewMapped(
            "label,origin\nExample,site\n",
            ['description', 'source_system'],
            $map,
        );

        foreach ([
            [$clean, self::OTHER, 'import', [1, 2]],
            [$clean, self::OWN, 'export', [1, 2]],
            [$clean, self::OWN, 'import', [1, 2, 3]],
            [$clean, self::OWN, 'import', [1, 1]],
            [$clean, self::OWN, 'import', ['*', 2]],
            [
                $decoder->previewMapped(
                    "label,origin\n=1+1,site\n",
                    ['description', 'source_system'],
                    $map,
                ),
                self::OWN, 'import', [1, 2],
            ],
            [
                $decoder->previewMapped(
                    "label,origin\n",
                    ['description', 'source_system'],
                    $map,
                ),
                self::OWN, 'import', [1, 2],
            ],
        ] as [$mapped, $tenant, $operation, $fields]) {
            $decision = $gate->evaluate($context, $mapped, $tenant, $operation, $fields);
            $this->assertFalse($decision->allowed);
            $this->assertSame('CONTRACT_INVALID', $decision->reasonCode);
            $this->assertSame(['code', 'correlation_id'], array_keys($decision->signal()));
        }
    }

    public function test_no_import_mutation_or_entitlement_entrypoint_is_exposed_by_lab_contracts(): void
    {
        $classes = [
            SyntheticCsvPreviewDecoder::class,
            SyntheticImportReviewInspector::class,
            SyntheticMappedImportDenyGate::class,
        ];

        foreach ($classes as $class) {
            foreach (['import', 'export', 'upload', 'save', 'persist', 'commit',
                'create', 'delete', 'merge', 'authorize', 'grant', 'allow', 'charge'] as $method) {
                $this->assertFalse(method_exists($class, $method), $class.'::'.$method);
            }
        }

        $this->assertSame(['evaluate'], get_class_methods(SyntheticMappedImportDenyGate::class));
        $this->assertFalse(AuthorizationDecision::deny()->allowed);
    }
    public function test_consistent_synthetic_backup_manifest_never_enables_restore_or_deletion(): void
    {
        $bytes = 'fake lab bytes only';
        $manifest = [
            'format' => 'synthetic-blob-v1',
            'declared_bytes' => strlen($bytes),
            'recorded_at_utc' => '2026-11-10T03:00:00Z',
            'sha256' => hash('sha256', $bytes),
        ];

        $assessment = (new SyntheticBackupIntegrityInspector())->inspect($manifest, $bytes);
        $this->assertTrue($assessment->matchesSelfDeclaredDigest());
        $this->assertFalse($assessment->canRestore());
        $this->assertFalse($assessment->canAcceptAsVerifiedBackup());
        $this->assertFalse($assessment->canDeleteOriginal());
        $this->assertSame(['reasonCode'], array_keys(get_object_vars($assessment)));
        $this->assertSame(['inspect'], get_class_methods(SyntheticBackupIntegrityInspector::class));
    }

}
