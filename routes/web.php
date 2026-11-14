<?php

declare(strict_types=1);

use App\Core\Commercial\StagingPersistedDemoPreview;
use App\Core\Commercial\StagingDemoPreview;
use App\Core\Commercial\StagingMarketingPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    Route::get('/demo', static function (Request $request) {
        // Demonstration data are constant synthetic labels from the code,
        // never database rows, memberships, sessions, provider IDs or PII.
        $model = StagingDemoPreview::presentation($request->query('lang', 'pt'));
        $model['enable_persisted_demo'] = getenv('CRM_STAGING_PERSISTED_DEMO') === 'true';

        return response()->view('crm-staging-demo', $model)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Content-Security-Policy',
                "default-src 'none'; style-src 'unsafe-inline'; ".
                "img-src 'self' data:; frame-ancestors 'none'; ".
                "base-uri 'none'; form-action 'none'");
    })->name('crm.private.readonly-demo');

    if (getenv('CRM_STAGING_PERSISTED_DEMO') === 'true') {
        Route::get('/demo/live', static function (Request $request) {
            try {
                // The tenant UUID is FIXED IN SERVER CODE. Neither the query,
                // cookies nor request headers can select the SQL scope.
                $model = StagingPersistedDemoPreview::presentation(
                    DB::connection(),
                    $request->query('lang', 'pt'),
                );
            } catch (\Throwable) {
                // Fail closed on missing fixture/unsafe DB role. No raw
                // SQL details, fallback customer data or public error traces.
                abort(404);
            }

            return response()->view('crm-staging-demo', $model)
                ->header('Cache-Control', 'private, no-store, max-age=0')
                ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Referrer-Policy', 'no-referrer')
                ->header('Content-Security-Policy',
                    "default-src 'none'; style-src 'unsafe-inline'; ".
                    "img-src 'self' data:; frame-ancestors 'none'; ".
                    "base-uri 'none'; form-action 'none'");
        })->name('crm.private.synthetic-persisted-demo');
    }
}
