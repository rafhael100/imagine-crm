<?php

declare(strict_types=1);

use App\Core\Commercial\StagingMarketingPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// CRM-STG-MKT-01: private, read-only commercial page only in staging.
// Production/testing environments remain completely route-less for business.
// No login, signup, trial, checkout, customer data or stateful effect.
if (app()->environment('staging')
    && getenv('CRM_STAGING_MARKETING_PREVIEW') === 'true') {
    Route::get('/', static function (Request $request) {
        $model = StagingMarketingPreview::presentation($request->query('lang', 'pt'));

        return response()->view('crm-staging-marketing', $model)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Content-Security-Policy',
                "default-src 'none'; style-src 'unsafe-inline'; ".
                "img-src 'self' data:; frame-ancestors 'none'; ".
                "base-uri 'none'; form-action 'none'");
    })->name('crm.private.marketing-preview');
}
