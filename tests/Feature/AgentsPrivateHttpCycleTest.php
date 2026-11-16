<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\AgentsSignedIngressCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F241 — the ORIGINAL pinned Agents F238/F239 classes inside private CRM
 * Laravel. Pure, disposable CI; NO ROUTE, NO ALLOW, NO BUSINESS READ.
 */
final class AgentsPrivateHttpCycleTest extends TestCase
{
    private const TENANT = 'SYNTHETIC_TENANT_A';
    private const NOW = 1794690010;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('CRM_AIA_F241_PARITY_LAB') !== 'true') {
            $this->markTestSkipped('F241 runs only with explicitly isolated CI flag.');
        }
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || !app()->environment('testing')) {
            throw new \RuntimeException('F241_NON_CI_ENVIRONMENT_DENIED');
        }

        $dir = base_path('_aia_f241_fixture');
        foreach ([
            'src/Domain/MockError.php',
            'src/MockBridge/MockCrmReadRequest.php',
            'src/MockBridge/MockCrmPublicResponseContract.php',
            'src/MockBridge/MockServiceIdentityPreflight.php',
            'src/MockBridge/MockSyntheticScopePreflight.php',
            'src/MockBridge/MockSharedNonceLedger.php',
            'src/MockBridge/MockSyntheticEvidenceGate.php',
            'src/MockBridge/MockSyntheticHttpEnvelope.php',
            'src/MockBridge/MockSyntheticHttpDenialReceipt.php',
        ] as $relative) {
            self::assertFileExists($dir.'/'.$relative);
        }
        spl_autoload_register(static function (string $class) use ($dir): void {
            $prefix = 'ImagineAI\\';
            if (str_starts_with($class, $prefix)) {
                $path = $dir.'/src/'.str_replace(
                    '\\', '/', substr($class, strlen($prefix))
                ).'.php';
                if (is_file($path)) {
                    require_once $path;
                }
            }
        });
    }

    /** @return array{0:array<string,mixed>,1:array<string,mixed>,2:string} */
    private static function signedFixture(): array
    {
        $pair = sodium_crypto_sign_seed_keypair(
            hash('sha256', 'SYNTHETIC_PUBLIC_F241_LAB_ONLY', true)
        );
        $public = sodium_crypto_sign_publickey($pair);
        $keyId = 'SYNTHETIC_F241_KEY_A';
        $keys = [$keyId => ['public_key'=>$public,'enabled'=>true]];
        $tenant = self::TENANT;
        $wire = [
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F241_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>$tenant,
            'principal_ref'=>'SYNTHETIC_F241_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F241_LEAD',
            'purpose_ref'=>'SYNTHETIC_F241_PURPOSE',
            'fields'=>['ref','stage'],
        ];
        $scope = [
            'tenant_ref'=>$tenant,
            'principal_ref'=>'SYNTHETIC_F241_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F241_LEAD',
            'purpose_ref'=>'SYNTHETIC_F241_PURPOSE',
            'fields'=>['ref','stage'],
        ];
        $body=\ImagineAI\MockBridge\MockCrmReadRequest::encodeCanonicalJson($wire);
        $claims=[
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>$keyId,
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>$tenant,
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>'SYNTHETIC_F241_NONCE_FIRST',
            'issued_at'=>self::NOW - 10,
            'expires_at'=>self::NOW + 10,
            'request_sha256'=>hash('sha256',$body),
        ];
        $claimsBytes=json_encode(
            $claims,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
        );
        $signature=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claimsBytes,
            sodium_crypto_sign_secretkey($pair)
        ));
        $gate=new \ImagineAI\MockBridge\MockSyntheticEvidenceGate(
            new \ImagineAI\MockBridge\MockServiceIdentityPreflight(4),
            new \ImagineAI\MockBridge\MockSharedNonceLedger(4)
        );
        $envelope=\ImagineAI\MockBridge\MockSyntheticHttpEnvelope::assemble(
            $gate,$body,$claimsBytes,$signature,$keys,$scope,$tenant,self::NOW
        );
        self::assertNotNull($envelope);
        self::assertFalse($envelope['transmission_authorized']);
        self::assertFalse($envelope['real_authorization']);
        self::assertSame('NO_GO',$envelope['operational_decision']);
        return [$envelope,$keys,$body];
    }

    /** @param array<string,mixed> $fixture */
    private static function request(array $fixture, array $extra=[],string $method='POST'): Request
    {
        return Request::create(
            '/__synthetic_f241_not_registered',
            $method,
            [],[],[],
            array_merge([
                'CONTENT_TYPE'=>'application/json',
                'HTTP_X_AIA_CLAIMS'=>$fixture['headers']['x-aia-claims'],
                'HTTP_X_AIA_SIGNATURE'=>$fixture['headers']['x-aia-signature'],
            ],$extra),
            $fixture['body']
        );
    }

    private function assertUniformDenial(\Illuminate\Http\JsonResponse $response): void
    {
        self::assertSame(404,$response->getStatusCode());
        self::assertSame([
            'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state'=>'DENIED_OR_NOT_FOUND',
            'projection'=>null,
            'real_authorization'=>false,
            'external_calls'=>0,
        ],$response->getData(true));
        self::assertNull($response->headers->get('set-cookie'));
        $headers=[
            'content-type'=>(string)$response->headers->get('content-type'),
            'cache-control'=>(string)$response->headers->get('cache-control'),
            'x-robots-tag'=>(string)$response->headers->get('x-robots-tag'),
            'x-content-type-options'=>(string)$response->headers->get('x-content-type-options'),
        ];
        $responseBody = $response->getContent();
        self::assertTrue(\ImagineAI\MockBridge\MockSyntheticHttpDenialReceipt::matches(
            404,$headers,$responseBody
        ), 'F241_PUBLIC_HEADER_PROFILE='.json_encode($headers,JSON_THROW_ON_ERROR)
            .' body_len='.strlen($responseBody)
            .' body_sha256='.hash('sha256',$responseBody));
    }

    public function test_f241_original_agents_envelope_passes_private_callback_but_http_stays_denied(): void
    {
        [$envelope,$keys] = self::signedFixture();
        $originalVerifier = new \ImagineAI\MockBridge\MockServiceIdentityPreflight(4);
        $results=[];
        $callback=static function (string $body,string $claims,string $sig)
            use ($originalVerifier,$keys,&$results): bool {
            $verified=$originalVerifier->verifyOnce(
                $body,$claims,$sig,$keys,self::TENANT,self::NOW
            );
            $results[]=$verified;
            return $verified;
        };
        $before=Route::getRoutes()->count();
        $this->assertUniformDenial(AgentsSignedIngressCandidate::inspect(
            self::request($envelope),$callback
        ));
        $this->assertUniformDenial(AgentsSignedIngressCandidate::inspect(
            self::request($envelope),$callback
        ));
        self::assertSame([true,false],$results);
        self::assertSame($before,Route::getRoutes()->count());
    }

    public function test_f241_injected_headers_are_denied_before_any_callback(): void
    {
        [$envelope,$keys]=self::signedFixture();
        $before=Route::getRoutes()->count();
        foreach ([
            ['HTTP_AUTHORIZATION'=>'Bearer SYNTHETIC_FORBIDDEN'],
            ['HTTP_COOKIE'=>'session=SYNTHETIC_FORBIDDEN'],
            ['HTTP_X_TENANT_ID'=>'SYNTHETIC_TENANT_B'],
            ['HTTP_X_ORGANIZATION_ID'=>'SYNTHETIC_FAKE'],
            ['HTTP_X_ROLE'=>'admin'],
            ['HTTP_X_SERVICE_IDENTITY'=>'SYNTHETIC_IMPERSONATION'],
        ] as $extra) {
            $calls=0;
            $callback=static function () use (&$calls): bool {
                $calls++;
                return true;
            };
            $this->assertUniformDenial(AgentsSignedIngressCandidate::inspect(
                self::request($envelope,$extra),$callback
            ));
            self::assertSame(0,$calls);
        }
        self::assertSame($before,Route::getRoutes()->count());
    }

    public function test_f241_wrong_method_and_payload_cannot_invoke_verifier(): void
    {
        [$envelope,$keys]=self::signedFixture();
        $calls=0;
        $callback=static function () use (&$calls): bool {
            $calls++;
            return true;
        };
        $this->assertUniformDenial(AgentsSignedIngressCandidate::inspect(
            self::request($envelope,[],'GET'),$callback
        ));
        $modified=$envelope;
        $modified['body']=$envelope['body']." ";
        $this->assertUniformDenial(AgentsSignedIngressCandidate::inspect(
            self::request($modified),$callback
        ));
        self::assertSame(0,$calls);
    }
}
