<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Tests\Support\SignupTrialHttpFixture;
use Tests\TestCase;

/**
 * F3-254: real Laravel HTTP kernel exercises test-only resend boundaries.
 * No endpoint is added to routes/web.php or exposed outside PHPUnit.
 */
final class SignupTrialHttpFixtureResendTest extends TestCase
{
    private SignupTrialHttpFixture $lab;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = new SignupTrialHttpFixture();
        $lab = $this->lab;
        Route::post('/__ci/f3-254/{operation}',
            static fn (Request $request, string $operation) =>
                $lab->handle($request, $operation));
    }

    private function postDemo(string $operation, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/__ci/f3-254/'.$operation,
            array_merge(['fixture' => 'synthetic'], $extra));
    }

    public function test_http_resend_cooldown_rotation_expiration_and_replay_are_only_local_claims(): void
    {
        $first = $this->postDemo('issue', ['channel' => 'email'])->assertOk();
        $firstCode = $this->lab->otpForTestOnly('email');
        self::assertIsString($firstCode);

        $tooEarly = $this->postDemo('issue', ['channel' => 'email'])->assertOk();
        self::assertSame('sandbox_resend_cooldown',
            $this->lab->traceForTestOnly()[1]);
        self::assertSame($firstCode, $this->lab->otpForTestOnly('email'));
        self::assertSame($first->json(), $tooEarly->json());

        // Issue at t0, early retry at t0+1, then leap 58 ticks to t0+60.
        $this->lab->advanceClockForTestOnly(58);
        $resend = $this->postDemo('issue', ['channel' => 'email'])->assertOk();
        $newCode = $this->lab->otpForTestOnly('email');
        self::assertIsString($newCode);
        self::assertMatchesRegularExpression('/\A[0-9]{6}\z/D', $newCode);
        self::assertSame('sandbox_code_created', $this->lab->traceForTestOnly()[2]);
        self::assertSame($first->json(), $resend->json());

        // Numeric collision across generations remains possible. The old
        // verifier has been replaced, but same digits would be valid again.
        if ($firstCode !== $newCode) {
            $oldAfterResend = $this->postDemo('verify', [
                'channel' => 'email', 'code' => $firstCode,
            ])->assertOk();
            self::assertSame('sandbox_wrong_code',
                $this->lab->traceForTestOnly()[3]);
            self::assertSame($resend->json('can_register'), $oldAfterResend->json('can_register'));
        }
        $matched = $this->postDemo('verify', [
            'channel' => 'email', 'code' => $newCode,
        ])->assertOk();
        $replay = $this->postDemo('verify', [
            'channel' => 'email', 'code' => $newCode,
        ])->assertOk();
        self::assertSame($matched->json(), $replay->json());
        self::assertSame('sandbox_replay_denied',
            $this->lab->traceForTestOnly()[count($this->lab->traceForTestOnly()) - 1]);
        self::assertFalse($replay->json('otp_identity_verified'));
        self::assertFalse($replay->json('can_register'));
        self::assertFalse($replay->json('can_authenticate'));

        $body = $replay->getContent();
        self::assertIsString($body);
        self::assertStringNotContainsString($newCode, $body);
        self::assertStringNotContainsString('sandbox_replay_denied', $body);
        self::assertSame('no-store, private', $replay->headers->get('Cache-Control'));
        self::assertNull($replay->headers->get('Set-Cookie'));
    }

    public function test_cross_channel_resend_never_creates_identity_or_real_delivery(): void
    {
        $email = $this->postDemo('issue', ['channel' => 'email'])->assertOk();
        $phone = $this->postDemo('issue', ['channel' => 'phone'])->assertOk();
        self::assertSame($email->json(), $phone->json());

        $emailCode = $this->lab->otpForTestOnly('email');
        $phoneCode = $this->lab->otpForTestOnly('phone');
        self::assertIsString($emailCode);
        self::assertIsString($phoneCode);

        $phoneMatched = $this->postDemo('verify', [
            'channel' => 'phone', 'code' => $phoneCode,
        ])->assertOk();
        self::assertFalse($phoneMatched->json('can_send'));
        self::assertFalse($phoneMatched->json('can_register'));

        $emailMatched = $this->postDemo('verify', [
            'channel' => 'email', 'code' => $emailCode,
        ])->assertOk();
        self::assertSame($phoneMatched->json(), $emailMatched->json());
        $trial = $this->postDemo('trial')->assertOk();
        self::assertFalse($trial->json('can_reserve_trial'));
        self::assertFalse($trial->json('can_create_tenant'));
        self::assertSame(1,
            $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
    }

    public function test_otp_expires_at_the_exact_900_second_http_request_boundary(): void
    {
        $this->postDemo('issue', ['channel' => 'phone'])->assertOk();
        $code = $this->lab->otpForTestOnly('phone');
        self::assertIsString($code);

        // Issued at t0 and the fixture tick moved from 1 to 900.
        // Core deadline must reject candidate at exactly t0+900.
        $this->lab->advanceClockForTestOnly(899);
        $response = $this->postDemo('verify', [
            'channel' => 'phone', 'code' => $code,
        ])->assertOk();
        $trace = $this->lab->traceForTestOnly();
        self::assertSame('sandbox_expired', $trace[count($trace) - 1]);
        self::assertSame('REVIEW_ONLY_DENIED', $response->json('status'));
        self::assertFalse($response->json('can_register'));
        self::assertFalse($response->json('can_authenticate'));
        self::assertStringNotContainsString($code, (string) $response->getContent());
        self::assertStringNotContainsString('sandbox_expired',
            (string) $response->getContent());
    }

    public function test_oversized_clock_claims_and_unknown_routes_fail_without_exposure(): void
    {
        foreach ([0, -1, 901, PHP_INT_MAX] as $seconds) {
            try {
                $this->lab->advanceClockForTestOnly($seconds);
                self::fail('Invalid time travel accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Relogio ficticio de OTP invalido.', $e->getMessage());
            }
        }
        $this->get('/signup')->assertNotFound();
        $this->postJson('/__ci/f3-254/missing', ['fixture' => 'synthetic'])
            ->assertNotFound();
        $this->get('/__ci/f3-254/issue')->assertStatus(405);
        self::assertFalse($this->lab->diagnosticsForTestOnly()['can_register']);

        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $src = file_get_contents(dirname(__DIR__, 2).'/'.$file);
            self::assertIsString($src);
            self::assertStringNotContainsString('__ci/f3-254', $src);
        }
    }
}
