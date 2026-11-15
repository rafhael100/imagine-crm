<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Synthetic\AiaStagingDeniedIngressLab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F246: exercise an explicitly test-mounted HTTP envelope, not an actual
 * CRM route. Even a valid diagnostic Ed25519 proof never returns ALLOW.
 */
final class AiaStagingDeniedIngressTest extends TestCase
{
    private const URI = '/__synthetic_f246_ingress';

    private static function requestBody(): string
    {
        return json_encode([
            'contract'=>'SYNTHETIC_CRM_AIA_READ_V1',
            'request_ref'=>'SYNTHETIC_F246_REQUEST',
            'operation'=>'crm.lead.read_minimal',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'principal_ref'=>'SYNTHETIC_F246_PRINCIPAL',
            'resource_ref'=>'SYNTHETIC_F246_RESOURCE',
            'purpose_ref'=>'SYNTHETIC_F246_PURPOSE',
            'fields'=>['ref','stage'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private static function signedClaims(string $body): array
    {
        $pair=sodium_crypto_sign_seed_keypair(hash('sha256','F246_TEST_FIXTURE_ONLY',true));
        $claims=json_encode([
            'contract'=>'SYNTHETIC_AIA_SERVICE_IDENTITY_V1',
            'issuer'=>'SYNTHETIC_AGENT_SERVICE',
            'audience'=>'SYNTHETIC_CRM_API',
            'key_id'=>'SYNTHETIC_F246_KEY_A',
            'subject'=>'SYNTHETIC_AGENTS_READ_ONLY',
            'tenant_ref'=>'SYNTHETIC_TENANT_A',
            'operation'=>'crm.lead.read_minimal',
            'nonce'=>'SYNTHETIC_F246_NONCE',
            'issued_at'=>1794690000,
            'expires_at'=>1794690020,
            'request_sha256'=>hash('sha256',$body),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $signature=base64_encode(sodium_crypto_sign_detached(
            "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$claims,
            sodium_crypto_sign_secretkey($pair)
        ));
        return [$claims,$signature,sodium_crypto_sign_publickey($pair)];
    }

    private static function headers(string $claims,string $signature): array
    {
        return [
            'CONTENT_TYPE'=>'application/json',
            'HTTP_X_AIA_CLAIMS'=>$claims,
            'HTTP_X_AIA_SIGNATURE'=>$signature,
        ];
    }

    private function mount(callable $callback): void
    {
        Route::match(['POST','GET'],self::URI,
            static fn (Request $req) =>
                AiaStagingDeniedIngressLab::respond($req,$callback(...))
        );
    }

    private function submit(string $body,array $server): void
    {
        $response=$this->call('POST',self::URI,[],[],[],$server,$body);
        $response->assertStatus(404)->assertExactJson([
            'contract'=>'SYNTHETIC_CRM_AIA_PUBLIC_V1',
            'state'=>'DENIED_OR_NOT_FOUND',
            'projection'=>null,
            'real_authorization'=>false,
            'external_calls'=>0,
        ]);
        $this->assertStringContainsString(
            'no-store',
            (string)$response->headers->get('cache-control')
        );
        $this->assertSame('',(string)$response->headers->get('set-cookie'));
    }

    public function test_f246_without_explicit_ci_flag_http_is_always_denied(): void
    {
        if (getenv('AIA_F246_HTTP_LAB')==='true') {
            $this->markTestSkipped('Ungated refusal is tested by the full suite.');
        }
        $probes=0;
        $this->mount(static function () use (&$probes): bool {
            $probes++;
            return true;
        });
        $body=self::requestBody();
        [$claims,$signature]=self::signedClaims($body);
        $this->submit($body,self::headers($claims,$signature));
        $this->assertSame(0,$probes);
    }

    public function test_f246_ci_valid_envelope_runs_diagnostic_and_never_replies_allow(): void
    {
        if (getenv('AIA_F246_HTTP_LAB')!=='true') {
            $this->markTestSkipped('F246 diagnostic runs only in disposable CI.');
        }
        $probes=0;
        $body=self::requestBody();
        [$claims,$signature,$public]=self::signedClaims($body);
        $this->mount(static function (
            string $seenBody,string $seenClaims,string $seenSignature
        ) use (&$probes,$body,$claims,$signature,$public): bool {
            $probes++;
            if ($seenBody!==$body || $seenClaims!==$claims
                || $seenSignature!==$signature) {
                return false;
            }
            $bytes=base64_decode($seenSignature,true);
            return is_string($bytes) && sodium_crypto_sign_verify_detached(
                $bytes,
                "SYNTHETIC_AIA_SERVICE_ED25519_V1\n".$seenClaims,
                $public
            );
        });

        $this->submit($body,self::headers($claims,$signature));
        $this->assertSame(1,$probes);
        // Replay is NOT admitted via HTTP. This endpoint is intentionally
        // diagnostic only; there is no success variant even when callback
        // reports a valid signature.
        $this->submit($body,self::headers($claims,$signature));
        $this->assertSame(2,$probes);
    }

    public function test_f246_header_injection_oversize_and_ambiguous_claims_fail_closed(): void
    {
        if (getenv('AIA_F246_HTTP_LAB')!=='true') {
            $this->markTestSkipped('F246 diagnostic runs only in disposable CI.');
        }
        $calls=0;
        $this->mount(static function () use (&$calls): bool {
            $calls++;
            throw new \RuntimeException('This should never be reached');
        });
        $body=self::requestBody();
        [$claims,$signature]=self::signedClaims($body);
        $valid=self::headers($claims,$signature);
        $invalid=[
            [$body,$valid+['HTTP_AUTHORIZATION'=>'Bearer SYNTHETIC_FORBIDDEN']],
            [$body,$valid+['HTTP_COOKIE'=>'session=SYNTHETIC_FORBIDDEN']],
            [$body,$valid+['HTTP_X_TENANT_ID'=>'SYNTHETIC_TENANT_B']],
            [$body,$valid+['HTTP_X_ORGANIZATION_ID'=>'SYNTHETIC_UUID']],
            [$body,array_replace($valid,['CONTENT_TYPE'=>'text/plain'])],
            [$body,array_replace($valid,['HTTP_X_AIA_SIGNATURE'=>'not-base64'])],
            [$body,array_replace($valid,['HTTP_X_AIA_CLAIMS'=>'{}'])],
            [$body,array_replace($valid,[
                'HTTP_X_AIA_CLAIMS'=>substr($claims,0,-1).',"key_id":"SYNTHETIC_FORGED"}',
            ])],
            [str_repeat('x',1025),$valid],
            ['{}',$valid],
            ['',$valid],
            [str_replace('"fields":["ref","stage"]',
                '"fields":["ref","stage"],"fields":["ref"]',$body),$valid],
        ];
        foreach ($invalid as [$payload,$headers]) {
            $this->submit($payload,$headers);
        }
        $this->assertSame(0,$calls);
    }

    public function test_f246_callback_errors_never_leak_details_or_change_status(): void
    {
        if (getenv('AIA_F246_HTTP_LAB')!=='true') {
            $this->markTestSkipped('F246 diagnostic runs only in disposable CI.');
        }
        $calls=0;
        $this->mount(static function () use (&$calls): never {
            $calls++;
            throw new \RuntimeException('SYNTHETIC_SECRET_MUST_NOT_ESCAPE');
        });
        $body=self::requestBody();
        [$claims,$signature]=self::signedClaims($body);
        $this->submit($body,self::headers($claims,$signature));
        $this->assertSame(1,$calls);
    }
}
