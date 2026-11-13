<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Support\SignupTrialHttpFixture;
use Tests\TestCase;

/**
 * F3-252 registers a fixture route only AFTER PHPUnit boots an isolated app.
 * Never import this class in routes/, a service provider or a deploy script.
 */
final class SignupTrialHttpFixtureFlowTest extends TestCase
{
    private SignupTrialHttpFixture $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new SignupTrialHttpFixture();
        $fixture = $this->fixture;
        Route::post('/__ci/f3-252/{operation}',
            static fn (Request $request, string $operation) =>
                $fixture->handle($request, $operation));
    }

    private function endpoint(string $operation): string
    {
        return '/__ci/f3-252/'.$operation;
    }

    private function postDemo(string $operation, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson($this->endpoint($operation),
            array_merge(['fixture' => 'synthetic'], $extra));
    }

    public function test_actual_laravel_kernel_exercises_otp_and_trial_with_generic_responses(): void
    {
        $issue = $this->postDemo('issue')->assertOk();
        $code = $this->fixture->otpForTestOnly();
        self::assertIsString($code);
        self::assertMatchesRegularExpression('/\A[0-9]{6}\z/D', $code);

        $wrong = $code === '000000' ? '000001' : '000000';
        $failed = $this->postDemo('verify', ['code' => $wrong])->assertOk();
        $matched = $this->postDemo('verify', ['code' => $code])->assertOk();
        $replayed = $this->postDemo('verify', ['code' => $code])->assertOk();

        self::assertSame($failed->json(), $matched->json());
        self::assertSame($matched->json(), $replayed->json());
        self::assertSame('demo_only_no_action', $matched->json('public_status_code'));

        $trial = $this->postDemo('trial')->assertOk();
        $duplicate = $this->postDemo('trial')->assertOk();
        self::assertSame($trial->json(), $duplicate->json());
        self::assertFalse($duplicate->json('can_reserve_trial'));
        self::assertSame(
            ['sandbox_code_created', 'sandbox_wrong_code',
                'sandbox_match_consumed_in_memory', 'sandbox_replay_denied',
                'sandbox_first_trial_marker', 'sandbox_duplicate_trial_claim'],
            $this->fixture->traceForTestOnly(),
        );
        self::assertSame(1,
            $this->fixture->diagnosticsForTestOnly()['trial_markers_in_memory']);

        foreach ([$issue, $failed, $matched, $replayed, $trial, $duplicate] as $response) {
            $this->assertSafeResponse($response, $code);
        }
    }

    public function test_fake_trial_step_is_never_authorized_by_previous_otp(): void
    {
        $beforeOtp = $this->postDemo('trial')->assertOk();
        self::assertSame('sandbox_first_trial_marker',
            $this->fixture->traceForTestOnly()[0]);
        self::assertFalse($beforeOtp->json('otp_identity_verified'));
        self::assertFalse($beforeOtp->json('can_create_tenant'));
        self::assertFalse($beforeOtp->json('can_register'));
        self::assertSame('sandbox_duplicate_trial_claim',
            $this->postDemo('trial')->json('can_reserve_trial') === false
                ? $this->fixture->traceForTestOnly()[1] : 'unsafe');
    }

    public function test_malformed_and_sensitive_payloads_are_ignored_without_echo_or_side_effect(): void
    {
        $issue = $this->postDemo('issue')->assertOk();
        $code = $this->fixture->otpForTestOnly();
        self::assertIsString($code);
        $baseline = $this->postDemo('verify', ['code' => '12345'])->assertOk();

        foreach ([
            ['fixture' => 'synthetic', 'code' => "123456\r\nBcc:private@example.org"],
            ['fixture' => 'synthetic', 'code' => ['123456']],
            ['fixture' => 'synthetic', 'code' => '+5511999988888'],
            ['fixture' => 'synthetic', 'code' => '123456', 'email' => 'private@example.org'],
            ['fixture' => 'synthetic', 'code' => '123456', 'cnpj' => '11.222.333/0001-81'],
            ['fixture' => 'synthetic', 'code' => '123456', 'tenant_id' => 'guess'],
            ['fixture' => 'real', 'code' => '123456'],
        ] as $payload) {
            $res = $this->postJson($this->endpoint('verify'), $payload)->assertOk();
            self::assertSame($baseline->json(), $res->json());
            $this->assertSafeResponse($res, $code);
        }

        self::assertSame(
            ['sandbox_code_created',
                'sandbox_rejected_untrusted_payload',
                'sandbox_rejected_untrusted_payload',
                'sandbox_rejected_untrusted_payload',
                'sandbox_rejected_untrusted_payload',
                'sandbox_rejected_untrusted_payload',
                'sandbox_rejected_untrusted_payload',
                'sandbox_rejected_untrusted_payload',
                'sandbox_rejected_untrusted_payload'],
            $this->fixture->traceForTestOnly(),
        );
        $this->postDemo('verify', ['code' => $code])->assertOk();
        self::assertSame('sandbox_match_consumed_in_memory',
            $this->fixture->traceForTestOnly()[9]);
    }

    public function test_real_project_still_has_no_published_signup_routes(): void
    {
        $this->get('/')->assertNotFound();
        $this->get('/api/signup')->assertNotFound();
        $this->postJson('/signup', ['fixture' => 'synthetic'])->assertNotFound();
        $this->get($this->endpoint('issue'))->assertStatus(405);
        $this->postDemo('unknown')->assertNotFound();

        foreach ([
            'routes/web.php',
            'routes/console.php',
            'bootstrap/app.php',
        ] as $path) {
            $source = file_get_contents(dirname(__DIR__, 2).'/'.$path);
            self::assertIsString($source);
            self::assertStringNotContainsString('__ci/f3-252', $source);
            self::assertStringNotContainsString('SignupTrialHttpFixture', $source);
        }
    }


    public function test_email_and_phone_otp_use_separate_core_scopes_and_identical_public_contracts(): void
    {
        $emailIssued = $this->postDemo('issue', ['channel' => 'email'])->assertOk();
        $emailCode = $this->fixture->otpForTestOnly('email');
        self::assertIsString($emailCode);

        $phoneIssued = $this->postDemo('issue', ['channel' => 'phone'])->assertOk();
        $phoneCode = $this->fixture->otpForTestOnly('phone');
        self::assertIsString($phoneCode);
        self::assertMatchesRegularExpression('/\A[0-9]{6}\z/D', $phoneCode);
        self::assertSame($emailIssued->json(), $phoneIssued->json());

        // Presentation is real Core code, but NEVER delivered by Mail or
        // WhatsApp. Do not pretend provider approval or contact ownership.
        $emailPresentation = new \App\Mail\OfflineSignupOtpMail($emailCode);
        $phonePresentation = (new \App\Core\Identity\OfflineSignupOtpWhatsAppDraft())
            ->compose($phoneCode);
        self::assertSame($emailCode, $emailPresentation->content()->with['otpCode']);
        self::assertSame(15, $emailPresentation->content()->with['validityMinutes']);
        self::assertSame($phoneCode, $phonePresentation['synthetic_parameter_code']);
        self::assertSame(900, $phonePresentation['validity_seconds']);
        self::assertFalse($phonePresentation['template_approved_by_provider']);
        self::assertFalse($phonePresentation['can_send']);
        self::assertFalse($phonePresentation['recipient_binding_verified']);

        // A six-digit collision is statistically possible. When different,
        // a code from the email challenge fails for the phone challenge.
        if ($emailCode !== $phoneCode) {
            $wrongChannel = $this->postDemo('verify', [
                'channel' => 'phone', 'code' => $emailCode,
            ])->assertOk();
            self::assertSame('sandbox_wrong_code',
                $this->fixture->traceForTestOnly()[count($this->fixture->traceForTestOnly()) - 1]);
            self::assertSame($emailIssued->json('can_send'), $wrongChannel->json('can_send'));
        }

        $phoneOk = $this->postDemo('verify', [
            'channel' => 'phone', 'code' => $phoneCode,
        ])->assertOk();
        $emailOk = $this->postDemo('verify', [
            'channel' => 'email', 'code' => $emailCode,
        ])->assertOk();
        self::assertSame($phoneOk->json(), $emailOk->json());
        self::assertSame('sandbox_match_consumed_in_memory',
            $this->fixture->traceForTestOnly()[count($this->fixture->traceForTestOnly()) - 1]);
        $phoneReplay = $this->postDemo('verify', [
            'channel' => 'phone', 'code' => $phoneCode,
        ])->assertOk();
        self::assertSame($emailOk->json(), $phoneReplay->json());

        foreach ([$emailIssued, $phoneIssued, $phoneOk, $emailOk, $phoneReplay] as $response) {
            $this->assertSafeResponse($response, $emailCode);
            $this->assertSafeResponse($response, $phoneCode);
        }
        self::assertFalse($emailOk->json('otp_identity_verified'));
        self::assertFalse($phoneOk->json('can_register'));
    }

    public function test_fake_channel_validation_rejects_unknown_payloads_without_state_change(): void
    {
        $this->postDemo('issue', ['channel' => 'phone'])->assertOk();
        $code = $this->fixture->otpForTestOnly('phone');
        self::assertIsString($code);

        $before = count($this->fixture->traceForTestOnly());
        foreach ([
            ['fixture' => 'synthetic', 'channel' => 'whatsapp'],
            ['fixture' => 'synthetic', 'channel' => 'sms'],
            ['fixture' => 'synthetic', 'channel' => 'phone', 'to' => '+5511999988888'],
            ['fixture' => 'synthetic', 'channel' => 'email', 'email' => 'real@example.org'],
            ['fixture' => 'synthetic', 'channel' => ['phone']],
            ['fixture' => 'synthetic', 'channel' => null],
        ] as $payload) {
            $response = $this->postJson($this->endpoint('issue'), $payload)->assertOk();
            $this->assertSafeResponse($response, $code);
        }

        self::assertCount($before + 6, $this->fixture->traceForTestOnly());
        foreach (array_slice($this->fixture->traceForTestOnly(), $before) as $state) {
            self::assertSame('sandbox_rejected_untrusted_payload', $state);
        }
        self::assertSame($code, $this->fixture->otpForTestOnly('phone'));
        $verify = $this->postDemo('verify', [
            'channel' => 'phone', 'code' => $code,
        ])->assertOk();
        $this->assertSafeResponse($verify, $code);
        self::assertSame('sandbox_match_consumed_in_memory',
            $this->fixture->traceForTestOnly()[count($this->fixture->traceForTestOnly()) - 1]);
    }

    private function assertSafeResponse(
        \Illuminate\Testing\TestResponse $response,
        string $code,
    ): void {
        $json = $response->json();
        self::assertSame('imagine.crm.fixture_http_only.v1', $json['protocol']);
        self::assertSame('REVIEW_ONLY_DENIED', $json['status']);
        foreach ([
            'contact_existence_known', 'otp_identity_verified',
            'trial_eligibility_verified', 'tenant_membership_verified',
            'atomic_otp_consume_verified', 'durable_trial_uniqueness_verified',
            'transport_verified', 'can_send', 'can_register', 'can_authenticate',
            'can_create_tenant', 'can_reserve_trial', 'can_allocate_quota',
            'can_charge', 'can_persist',
        ] as $key) {
            self::assertFalse($json[$key], $key);
        }
        $wire = $response->getContent();
        self::assertIsString($wire);
        self::assertLessThan(2000, strlen($wire));
        foreach ([
            $code, 'private@example.org', '+5511999988888', 'tenant_id',
            '11222333000181', '12ABC34501DE35', 'nonce',
            'digest', 'delivery_code', 'sandbox_wrong_code',
            'sandbox_match_consumed_in_memory', 'sandbox_duplicate_trial_claim',
            '@', '"cnpj"',
        ] as $secret) {
            self::assertStringNotContainsString($secret, $wire);
        }
        self::assertSame('no-store, private',
            $response->headers->get('Cache-Control'));
        self::assertSame('nosniff',
            $response->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY',
            $response->headers->get('X-Frame-Options'));
        self::assertSame("default-src 'none'",
            $response->headers->get('Content-Security-Policy'));
        self::assertNull($response->headers->get('Set-Cookie'));
    }
}
