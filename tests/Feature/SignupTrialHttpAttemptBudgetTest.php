<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Support\SignupTrialHttpFixture;
use Tests\TestCase;

/**
 * F3-257: exercise anti-abuse budgets through Laravel's real HTTP kernel,
 * but register the route only inside PHPUnit. All contacts and time are fake.
 *
 * Process-local claims do not provide durable throttling, authenticated
 * origin, identity, trial eligibility, provider delivery or account access.
 */
final class SignupTrialHttpAttemptBudgetTest extends TestCase
{
    private SignupTrialHttpFixture $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = new SignupTrialHttpFixture();
        $fixture = $this->fixture;
        Route::post('/__ci/f3-257/{operation}',
            static fn (Request $request, string $operation) =>
                $fixture->handle($request, $operation));
    }

    private function send(string $operation, array $extra = []): TestResponse
    {
        return $this->postJson('/__ci/f3-257/'.$operation,
            array_merge(['fixture' => 'synthetic'], $extra));
    }

    private static function wrongCode(string $actual): string
    {
        return $actual === '000000' ? '000001' : '000000';
    }

    private function assertNeutral(TestResponse $response, string $code): void
    {
        $response->assertOk();
        self::assertSame('REVIEW_ONLY_DENIED', $response->json('status'));
        self::assertSame('demo_only_no_action', $response->json('public_status_code'));
        foreach ([
            'can_send', 'can_register', 'can_authenticate',
            'can_reserve_trial', 'can_persist', 'atomic_otp_consume_verified',
        ] as $key) {
            self::assertFalse($response->json($key), $key);
        }
        $wire = (string) $response->getContent();
        self::assertStringNotContainsString($code, $wire);
        self::assertStringNotContainsString('sandbox_', $wire);
        self::assertSame('no-store, private',
            $response->headers->get('Cache-Control'));
        self::assertNull($response->headers->get('Set-Cookie'));
    }

    public function test_five_wrong_candidates_exhaust_the_local_challenge_even_for_the_correct_code(): void
    {
        $issued = $this->send('issue')->assertOk();
        $code = $this->fixture->otpForTestOnly('email');
        self::assertIsString($code);
        $this->assertNeutral($issued, $code);

        $wrong = self::wrongCode($code);
        for ($i = 0; $i < 5; $i++) {
            $response = $this->send('verify', ['code' => $wrong]);
            $this->assertNeutral($response, $code);
            self::assertSame('sandbox_wrong_code',
                $this->fixture->traceForTestOnly()[$i + 1]);
        }

        $locked = $this->send('verify', ['code' => $code]);
        $this->assertNeutral($locked, $code);
        self::assertSame('sandbox_attempt_limit',
            $this->fixture->traceForTestOnly()[6]);
        self::assertSame($response->json(), $locked->json());
        self::assertFalse($this->fixture->diagnosticsForTestOnly()['can_register']);
    }

    public function test_sixty_fictional_origin_verifications_block_the_next_one_across_channels(): void
    {
        $email = $this->send('issue', ['channel' => 'email'])->assertOk();
        $phone = $this->send('issue', ['channel' => 'phone'])->assertOk();
        $emailCode = $this->fixture->otpForTestOnly('email');
        $phoneCode = $this->fixture->otpForTestOnly('phone');
        self::assertIsString($emailCode);
        self::assertIsString($phoneCode);

        for ($i = 0; $i < 60; $i++) {
            $channel = $i % 2 === 0 ? 'email' : 'phone';
            $actual = $channel === 'email' ? $emailCode : $phoneCode;
            $this->send('verify', [
                'channel' => $channel,
                'code' => self::wrongCode($actual),
            ])->assertOk();
        }

        $blocked = $this->send('verify', [
            'channel' => 'phone', 'code' => $phoneCode,
        ]);
        $this->assertNeutral($blocked, $phoneCode);
        $this->assertNeutral($email, $emailCode);
        $this->assertNeutral($phone, $phoneCode);
        self::assertSame('sandbox_origin_verification_budget_exhausted',
            $this->fixture->traceForTestOnly()[62]);
        self::assertFalse($this->fixture->diagnosticsForTestOnly()['can_authenticate']);
    }

    public function test_three_issues_per_contact_stop_a_fourth_without_erasing_the_latest_code(): void
    {
        $first = $this->send('issue')->assertOk();
        $code = $this->fixture->otpForTestOnly('email');
        self::assertIsString($code);

        for ($i = 0; $i < 2; $i++) {
            // A successful issue increments the fake clock by one.
            $this->fixture->advanceClockForTestOnly(59);
            $this->send('issue')->assertOk();
            self::assertSame('sandbox_code_created',
                $this->fixture->traceForTestOnly()[$i + 1]);
        }

        $latest = $this->fixture->otpForTestOnly('email');
        self::assertIsString($latest);
        $this->fixture->advanceClockForTestOnly(59);
        $denied = $this->send('issue')->assertOk();
        self::assertSame('sandbox_contact_budget_exhausted',
            $this->fixture->traceForTestOnly()[3]);
        self::assertSame($latest, $this->fixture->otpForTestOnly('email'));
        self::assertSame($first->json(), $denied->json());
        $this->assertNeutral($denied, $latest);

        $valid = $this->send('verify', ['code' => $latest])->assertOk();
        self::assertSame('sandbox_match_consumed_in_memory',
            $this->fixture->traceForTestOnly()[4]);
        $this->assertNeutral($valid, $latest);
    }

    public function test_no_public_runtime_route_is_created(): void
    {
        $this->postJson('/signup', ['fixture' => 'synthetic'])->assertNotFound();
        $this->get('/__ci/f3-257/issue')->assertStatus(405);
        self::assertFalse($this->fixture->diagnosticsForTestOnly()['can_register']);
        foreach (['routes/web.php', 'bootstrap/app.php'] as $path) {
            $src = file_get_contents(dirname(__DIR__, 2).'/'.$path);
            self::assertIsString($src);
            self::assertStringNotContainsString('__ci/f3-257', $src);
        }
    }
}
