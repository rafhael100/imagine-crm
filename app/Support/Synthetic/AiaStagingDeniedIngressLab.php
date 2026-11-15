<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * F246: inert HTTP staging-envelope *candidate* for CRM ↔ Agents.
 *
 * This is NOT registered on any production/application route. Tests mount
 * it on an ephemeral testing-only URI. No success response exists: the
 * caller gets the exact same 404 denial for signed or unsigned messages.
 *
 * A verification callback, supplied only by the gated synthetic CI caller,
 * may inspect the original Agents Ed25519 proof. It cannot send credentials
 * or data back to the HTTP peer. All inputs and failures are fail-closed.
 */
final class AiaStagingDeniedIngressLab
{
    /** @param Closure(string,string,string):mixed $verifyDiagnostic */
    public static function respond(
        Request $request,
        Closure $verifyDiagnostic,
    ): JsonResponse {
        $denied = static fn (): JsonResponse => response()->json(
            AiaReadOnlyMockBoundary::inspectCanonicalJson(''),
            404,
            ['Cache-Control' => 'no-store, private'],
        );

        // Even for an internally mounted test route, never activate outside
        // the exact disposable GitHub Actions environment and explicit gate.
        if (getenv('AIA_F246_HTTP_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || $request->method() !== 'POST'
            || $request->headers->get('content-type') !== 'application/json') {
            return $denied();
        }

        try {
            $body = $request->getContent();
            if (!is_string($body) || strlen($body) > 1024
                || !AiaReadOnlyMockBoundary::hasCanonicalFixtureShape($body)
                || $request->headers->has('authorization')
                || $request->headers->has('cookie')
                || $request->headers->has('x-tenant-id')
                || $request->headers->has('x-organization-id')) {
                return $denied();
            }

            // Disallow ambiguous/multiple wire header values. Key-id is not
            // accepted from a separate header: only canonical, signed claims
            // may select the CRM-owned key->tenant binding.
            $claimHeaders = $request->headers->all('x-aia-claims');
            $signatureHeaders = $request->headers->all('x-aia-signature');
            if (count($claimHeaders) !== 1
                || count($signatureHeaders) !== 1
                || !is_string($claimHeaders[0] ?? null)
                || !is_string($signatureHeaders[0] ?? null)) {
                return $denied();
            }

            $claims = $claimHeaders[0];
            $signature = $signatureHeaders[0];
            if ($claims === '' || strlen($claims) > 1024
                || $signature === '' || strlen($signature) !== 88
                || !is_array($decoded = json_decode(
                    $claims, true, 4, JSON_THROW_ON_ERROR
                ))
                || !is_string($decoded['key_id'] ?? null)
                || !is_string($decoded['tenant_ref'] ?? null)
                || json_encode(
                    $decoded, JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ) !== $claims) {
                return $denied();
            }

            $bytes = base64_decode($signature, true);
            if (!is_string($bytes)
                || strlen($bytes) !== SODIUM_CRYPTO_SIGN_BYTES
                || !hash_equals(base64_encode($bytes), $signature)) {
                return $denied();
            }

            // A diagnostic may return a positive synthetic proof; its value
            // must NEVER influence HTTP status, headers, or response body.
            $verifyDiagnostic($body, $claims, $signature);
        } catch (\Throwable) {
            // Failures and callback exceptions are indistinguishable from
            // other denials. Never log request bytes/credentials/PII.
        }

        return $denied();
    }
}
