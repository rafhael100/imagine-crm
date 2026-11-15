<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Homologation\AgentsSignedIngressCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F248 — private journey adaptation of the draft Ed25519 HTTP candidate.
 * Never mounts or exposes a route; all evidence is synthetic.
 */
final class AgentsPrivateIngressCandidateTest extends TestCase
{
    private const NOW = 1794690010;
    private const TENANT = 'SYNTHETIC_TENANT_A';
    private const KEY = 'SYNTHETIC_F248_KEY_A';

    private static function canonicalRequest(): string
    {
        return json_encode([
            'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref' => 'SYNTHETIC_F248_PROBE',
            'operation' => 'crm.lead.read_minimal',
            'tenant_ref' => self::TENANT,
            'principal_ref' => 'SYNTHETIC_F248_PRINCIPAL',
            'resource_ref' => 'SYNTHETIC_F248_RESOURCE',
            'purpose_ref' => 'SYNTHETIC_F248_PURPOSE',
            'fields' => ['ref', 'stage'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @return array{0:string,1:string,2:string} */
    private static function signedEnvelope(string $body): array
    {
        $pair = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'SYNTHETIC_PUBLIC_F248_TEST_KEY_ONLY', true)
        );
        $claims = json_encode([
            'contract' => 'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer' => 'SYNTHETIC_AGENT_SERVICE',
            'audience' => 'SYNTHETIC_CRM_API',
            'key_id' => self::KEY,
            'subject' => 'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref' => self::TENANT,
            'operation' => 'crm.lead.read_minimal',
            'nonce' => 'SYNTHETIC_F248_NONCE_FIRST',
            'issued_at' => self::NOW - 10,
            'expires_at' => self::NOW + 10,
            'request_sha256' => hash('sha256', $body),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return [
            $claims,
            base64_encode(sodium_crypto_sign_detached(
                "SYNTHETIC_AIA_SERVICE_ED25519_V1\n" . $claims,
                sodium_crypto_sign_secretkey($pair),
            )),
            sodium_crypto_sign_publickey($pair),
        ];
    }

    private static function request(
        string $body,
        string $claims,
        string $signature,
        array $additional = [],
    ): Request {
        return Request::create(
            '/__synthetic_f248_not_registered',
            'POST',
            [],
            [],
            [],
            array_merge([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_AIA_CLAIMS' => $claims,
                'HTTP_X_AIA_SIGNATURE' => $signature,
            ], $additional),
            $body,
        );
    }

    private function assertDenied(\Illuminate\Http\JsonResponse $response): void
    {
        self::assertSame(404, $response->getStatusCode());
        self::assertSame([
            'contract' => 'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state' => 'DENIED_OR_NOT_FOUND',
            'projection' => null,
            'real_authorization' => false,
            'external_calls' => 0,
        ], $response->getData(true));
        self::assertStringContainsString(
            'no-store',
            (string) $response->headers->get('cache-control'),
        );
        self::assertNull($response->headers->get('set-cookie'));
        self::assertSame('nosniff', $response->headers->get('x-content-type-options'));
    }

    public function test_f248_default_private_runtime_does_not_evaluate_proof(): void
    {
        if (getenv('CRM_AIA_F248_PARITY_LAB') === 'true') {
            $this->markTestSkipped('Ungated default tested in regular CI without F248 flag.');
        }
        $body = self::canonicalRequest();
        [$claims, $signature] = self::signedEnvelope($body);
        $before = Route::getRoutes()->count();
        $calls = 0;
        $this->assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($body, $claims, $signature),
            static function () use (&$calls): bool {
                $calls++;
                return true;
            },
        ));
        self::assertSame(0, $calls);
        self::assertSame($before, Route::getRoutes()->count());
    }

    public function test_f248_uses_original_agents_signature_verifier_but_never_returns_allow(): void
    {
        if (getenv('CRM_AIA_F248_PARITY_LAB') !== 'true') {
            $this->markTestSkipped('Only signed original-Agents GitHub CI fixture may run this test.');
        }
        foreach ([
            'src/Domain/MockError.php',
            'src/Domain/TenantContext.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
        ] as $path) {
            $file = base_path('_aia_f248_fixture/' . $path);
            self::assertFileExists($file);
            require_once $file;
        }

        $body = self::canonicalRequest();
        [$claims, $sig, $public] = self::signedEnvelope($body);
        $trusted = [self::KEY => ['public_key' => $public, 'enabled' => true]];
        $original = new \ImagineAI\MockBridge\MockServiceIdentityPreflight(4);
        $results = [];
        $callback = static function (
            string $wire,
            string $signed,
            string $signature,
        ) use ($original, $trusted, &$results): bool {
            $valid = $original->verifyOnce(
                $wire, $signed, $signature, $trusted, self::TENANT, self::NOW
            );
            $results[] = $valid;
            return $valid;
        };

        $before = Route::getRoutes()->count();
        $this->assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($body, $claims, $sig), $callback
        ));
        $this->assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($body, $claims, $sig), $callback
        ));
        self::assertSame([true, false], $results, 'Original verifier must deny replay.');

        // Original pinned verifier rejects a different canonical body
        // paired with the old digest even if transport passes its shape.
        $different = json_decode($body, true, 4, JSON_THROW_ON_ERROR);
        $different['resource_ref'] = 'SYNTHETIC_F248_OTHER_RESOURCE';
        $differentBody = json_encode(
            $different,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $this->assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($differentBody, $claims, $sig), $callback
        ));
        self::assertSame([true, false, false], $results);

        // A structurally valid signature which was altered after signing
        // is still passed only to the original verifier and is rejected.
        $badSignature = base64_encode(str_repeat("\x00", 64));
        $this->assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($body, $claims, $badSignature), $callback
        ));
        self::assertSame([true, false, false, false], $results);
        self::assertSame($before, Route::getRoutes()->count());
        self::assertSame(0, count(array_filter(
            Route::getRoutes()->getRoutes(),
            static fn ($route): bool =>
                str_contains((string) $route->uri(), '__synthetic_f248'),
        )));
    }

    public function test_f248_headers_and_bad_envelopes_never_reach_verifier(): void
    {
        if (getenv('CRM_AIA_F248_PARITY_LAB') !== 'true') {
            $this->markTestSkipped('Gated negative tests only.');
        }
        $body = self::canonicalRequest();
        [$claims, $sig] = self::signedEnvelope($body);
        $calls = 0;
        $callback = static function () use (&$calls): bool {
            $calls++;
            throw new \RuntimeException('SYNTHETIC_F248_SECRET_NOT_FOR_HTTP');
        };
        foreach ([
            [$body, $claims, $sig, ['HTTP_AUTHORIZATION' => 'Bearer SYNTHETIC_FORBIDDEN']],
            [$body, $claims, $sig, ['HTTP_COOKIE' => 'session=SYNTHETIC_FORBIDDEN']],
            [$body, $claims, $sig, ['HTTP_X_TENANT_ID' => 'SYNTHETIC_FOREIGN']],
            [$body, $claims, $sig, ['HTTP_X_TENANT_REF' => 'SYNTHETIC_FOREIGN']],
            [$body, $claims, $sig, ['HTTP_X_ORGANIZATION_ID' => 'SYNTHETIC_FOREIGN']],
            [$body, $claims, $sig, ['HTTP_X_ROLE' => 'admin']],
            [$body, $claims, $sig, ['HTTP_X_SERVICE_IDENTITY' => 'admin']],
            [$body, $claims, $sig, ['CONTENT_TYPE' => 'text/plain']],
            [str_repeat('x', 1025), $claims, $sig, []],
            ['{}', $claims, $sig, []],
            [$body, '{}', $sig, []],
            [$body, $claims, 'NOT_BASE64', []],
            [$body, $claims . " ", $sig, []],
        ] as [$wire, $signed, $signature, $headers]) {
            $this->assertDenied(AgentsSignedIngressCandidate::inspect(
                self::request($wire, $signed, $signature, $headers), $callback
            ));
        }
        self::assertSame(0, $calls);
        // Even exceptions from the diagnostics are indistinguishable on HTTP.
        $this->assertDenied(AgentsSignedIngressCandidate::inspect(
            self::request($body, $claims, $sig), $callback
        ));
        self::assertSame(1, $calls);
    }
}
