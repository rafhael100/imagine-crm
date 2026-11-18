<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * F248 — safe adaptation of AIA F246 for the PRIVATE CRM branch.
 *
 * NEVER registered in routes, injected into the service container, or
 * callable in the staging/production runtime. Diagnostic callback can only
 * run in the explicitly gated, disposable GitHub Actions test environment.
 *
 * A valid Ed25519 witness is NOT a user, tenant, grant, lead-read permission,
 * or operational ALLOW. All HTTP responses remain indistinguishable 404.
 */
final class AgentsSignedIngressCandidate
{
    /**
     * @param Closure(string,string,string):bool $testOnlyVerifier
     */
    public static function inspect(
        Request $request,
        Closure $testOnlyVerifier,
    ): JsonResponse {
        $deny = static fn (): JsonResponse => response()->json(
            AiaReadOnlyMockBoundary::inspectCanonicalJson(''),
            404,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Robots-Tag' => 'noindex, nofollow, noarchive',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );

        if (getenv('CRM_AIA_F248_PARITY_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')
            || $request->method() !== 'POST'
            || $request->headers->get('content-type') !== 'application/json'
            || $request->headers->has('authorization')
            || $request->headers->has('cookie')
            || $request->headers->has('x-tenant-id')
            || $request->headers->has('x-tenant-ref')
            || $request->headers->has('x-organization-id')
            || $request->headers->has('x-role')
            || $request->headers->has('x-service-identity')) {
            return $deny();
        }

        try {
            $body = $request->getContent();
            if (!is_string($body) || !AiaReadOnlyMockBoundary::hasCanonicalFixtureShape($body)) {
                return $deny();
            }

            // Signer key id, tenant, and service identity cannot be accepted
            // from parallel headers. Signed claims remain UNTRUSTED until
            // the original pinned Agents verifier validates the signature.
            $claimsHeaders = $request->headers->all('x-aia-claims');
            $sigHeaders = $request->headers->all('x-aia-signature');
            if (count($claimsHeaders) !== 1 || count($sigHeaders) !== 1
                || !is_string($claimsHeaders[0] ?? null)
                || !is_string($sigHeaders[0] ?? null)) {
                return $deny();
            }
            $claims = $claimsHeaders[0];
            $signature = $sigHeaders[0];
            if ($claims === '' || strlen($claims) > 1024
                || strlen($signature) !== 88) {
                return $deny();
            }

            $claimsData = json_decode($claims, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($claimsData)
                || !is_string($claimsData['key_id'] ?? null)
                || !is_string($claimsData['tenant_ref'] ?? null)
                || json_encode(
                    $claimsData,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ) !== $claims) {
                return $deny();
            }
            $signatureBytes = base64_decode($signature, true);
            if (!is_string($signatureBytes)
                || strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES
                || !hash_equals(base64_encode($signatureBytes), $signature)) {
                return $deny();
            }

            // This callback is only an evidence sink in the CI laboratory.
            // It may verify a synthetic signature but MUST NOT alter the
            // HTTP response, open a business route, or inspect CRM leads.
            $testOnlyVerifier($body, $claims, $signature);
        } catch (\Throwable) {
            // No raw exception, payload, tenant or signature in logs.
        }

        return $deny();
    }
}
