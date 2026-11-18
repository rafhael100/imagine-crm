<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Commercial\StagingMarketingPreview;
use App\Core\Plans\CommercialPlanReferenceCatalog;
use PHPUnit\Framework\TestCase;

final class StagingMarketingPreviewTest extends TestCase
{
    public function test_portuguese_preview_uses_only_server_reference_prices_and_disables_checkout(): void
    {
        $model = StagingMarketingPreview::presentation('pt');
        $this->assertSame('pt', $model['lang']);
        $this->assertTrue($model['private_preview']);
        $this->assertFalse($model['can_signup']);
        $this->assertFalse($model['can_trial']);
        $this->assertFalse($model['can_checkout']);
        $this->assertCount(4, $model['cards']);
        $this->assertSame(
            array_column(CommercialPlanReferenceCatalog::plans(), 'code'),
            array_column($model['cards'], 'code'),
        );
        $this->assertSame(
            ['R$ 69,00', 'R$ 89,00', 'R$ 149,00', 'R$ 299,00'],
            array_column($model['cards'], 'display_price'),
        );
        foreach ($model['cards'] as $plan) {
            $this->assertFalse($plan['price_homologated']);
            $this->assertFalse($plan['can_checkout']);
            $this->assertGreaterThan(0, $plan['seats']);
        }
    }

    public function test_english_and_spanish_are_localized_without_changing_reference_prices(): void
    {
        $pt = StagingMarketingPreview::presentation('pt');
        foreach (['en', 'es'] as $lang) {
            $model = StagingMarketingPreview::presentation($lang);
            $this->assertSame($lang, $model['lang']);
            $this->assertSame(
                array_column($pt['cards'], 'display_price'),
                array_column($model['cards'], 'display_price'),
            );
            $this->assertNotSame($pt['copy']['headline'], $model['copy']['headline']);
            $this->assertFalse($model['can_signup']);
            $this->assertFalse($model['can_trial']);
            $this->assertFalse($model['can_checkout']);
        }
    }

    public function test_malicious_or_array_locale_falls_back_to_portuguese_and_never_enters_output(): void
    {
        foreach (['<script>alert(1)</script>', '../../en', 'EN', '', ['en'], null, 1] as $input) {
            $model = StagingMarketingPreview::presentation($input);
            $this->assertSame('pt', $model['lang']);
            $this->assertSame(
                'Imagine CRM | Transforme oportunidades em resultados',
                $model['copy']['title'],
            );
            $this->assertFalse($model['can_checkout']);
        }
    }
}
