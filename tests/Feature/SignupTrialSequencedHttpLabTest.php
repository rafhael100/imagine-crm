<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Support\SignupTrialSequencedHttpLab;
use Tests\TestCase;

/**
 * F3-258: PHPUnit-only HTTP route tests for a sequential in-memory lab.
 * None of these signals grant identity, contact ownership, trial or access.
 */
final class SignupTrialSequencedHttpLabTest extends TestCase
{
    private SignupTrialSequencedHttpLab $lab;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = new SignupTrialSequencedHttpLab();
        $lab = $this->lab;
        Route::post('/__ci/f3-258/{operation}',
            static fn (Request $request, string $operation) =>
                $lab->handle($request, $operation));
    }

    private function send(string $operation, string $channel, ?string $code = null): TestResponse
    {
        $payload = ['fixture' => 'synthetic', 'channel' => $channel];
        if ($code !== null) {
            $payload['code'] = $code;
        }
        return $this->postJson('/__ci/f3-258/'.$operation, $payload);
    }

    private function raw(string $operation, string $wire): TestResponse
    {
        return $this->call(
            'POST', '/__ci/f3-258/'.$operation,
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $wire,
        );
    }

    private function assertNoGrant(TestResponse $res, ?string $code = null): void
    {
        $res->assertOk();
        self::assertSame('REVIEW_ONLY_DENIED', $res->json('status'));
        self::assertSame('demo_only_no_action', $res->json('public_status_code'));
        foreach ([
            'contact_ownership_verified', 'identity_verified', 'tenant_membership_verified',
            'durable_otp_consumption_verified', 'durable_trial_uniqueness_verified',
            'can_send', 'can_register', 'can_authenticate', 'can_create_tenant',
            'can_reserve_trial', 'can_allocate_quota', 'can_charge', 'can_persist',
        ] as $flag) {
            self::assertFalse($res->json($flag), $flag);
        }

        $wire = (string) $res->getContent();
        self::assertLessThan(1600, strlen($wire));
        foreach ([
            $code, 'sandbox_', '@', '+5511999988888', '12.ABC.345/01DE-35',
            'delivery_code', 'tenant_id', 'challenge', '"cnpj"',
        ] as $secret) {
            if ($secret !== null) {
                self::assertStringNotContainsString($secret, $wire);
            }
        }
        self::assertSame('no-store, private', $res->headers->get('Cache-Control'));
        self::assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $res->headers->get('X-Frame-Options'));
        self::assertNull($res->headers->get('Set-Cookie'));
    }

    public function test_trial_claim_requires_one_local_otp_match_without_creating_an_account(): void
    {
        $before = $this->send('trial', 'email');
        self::assertSame(0, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);

        $issued = $this->send('issue', 'email');
        $code = $this->lab->codeForTestOnly('email');
        self::assertIsString($code);
        self::assertMatchesRegularExpression('/\A[0-9]{6}\z/D', $code);
        $wrong = $code === '000000' ? '000001' : '000000';

        $failed = $this->send('verify', 'email', $wrong);
        $blocked = $this->send('trial', 'email');
        self::assertSame(0, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);

        $matched = $this->send('verify', 'email', $code);
        self::assertTrue($this->lab->diagnosticsForTestOnly()['local_email_match']);
        $first = $this->send('trial', 'email');
        self::assertFalse($this->lab->diagnosticsForTestOnly()['local_email_match']);
        self::assertSame(1, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);

        $second = $this->send('trial', 'email');
        $replay = $this->send('verify', 'email', $code);
        $afterReplay = $this->send('trial', 'email');
        self::assertSame(1, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertSame([
            'sandbox_trial_requires_local_otp',
            'sandbox_code_created',
            'sandbox_wrong_code',
            'sandbox_trial_requires_local_otp',
            'sandbox_match_consumed_in_memory',
            'sandbox_first_trial_marker',
            'sandbox_trial_requires_local_otp',
            'sandbox_replay_denied',
            'sandbox_trial_requires_local_otp',
        ], $this->lab->eventsForTestOnly());

        // The HTTP contract must never reveal which internal branch ran.
        self::assertSame($before->json(), $blocked->json());
        self::assertSame($blocked->json(), $first->json());
        self::assertSame($first->json(), $second->json());
        self::assertSame($second->json(), $afterReplay->json());
        self::assertSame($failed->json(), $matched->json());
        self::assertSame($matched->json(), $replay->json());

        foreach ([
            $before, $issued, $failed, $blocked, $matched,
            $first, $second, $replay, $afterReplay,
        ] as $res) {
            $this->assertNoGrant($res, $code);
        }
    }

    public function test_email_match_is_not_accepted_as_phone_match(): void
    {
        $this->send('issue', 'email')->assertOk();
        $this->send('issue', 'phone')->assertOk();
        $emailCode = $this->lab->codeForTestOnly('email');
        $phoneCode = $this->lab->codeForTestOnly('phone');
        self::assertIsString($emailCode);
        self::assertIsString($phoneCode);

        $emailMatched = $this->send('verify', 'email', $emailCode);
        self::assertTrue($this->lab->diagnosticsForTestOnly()['local_email_match']);
        self::assertFalse($this->lab->diagnosticsForTestOnly()['local_phone_match']);

        $phoneTrial = $this->send('trial', 'phone');
        self::assertSame(0, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        $emailTrial = $this->send('trial', 'email');
        self::assertSame(1, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertFalse($this->lab->diagnosticsForTestOnly()['local_email_match']);

        foreach ([$emailMatched, $phoneTrial, $emailTrial] as $res) {
            $this->assertNoGrant($res, $emailCode);
            $this->assertNoGrant($res, $phoneCode);
        }
    }

    public function test_second_local_otp_does_not_reserve_a_second_company_trial(): void
    {
        $this->send('issue', 'email')->assertOk();
        $code1 = $this->lab->codeForTestOnly('email');
        self::assertIsString($code1);
        $this->send('verify', 'email', $code1)->assertOk();
        $this->send('trial', 'email')->assertOk();

        $this->lab->advanceClockForTestOnly(60);
        $this->send('issue', 'email')->assertOk();
        $code2 = $this->lab->codeForTestOnly('email');
        self::assertIsString($code2);
        $this->send('verify', 'email', $code2)->assertOk();
        $second = $this->send('trial', 'email');

        self::assertSame('sandbox_duplicate_trial_claim',
            $this->lab->eventsForTestOnly()[5]);
        self::assertSame(1,
            $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertFalse($this->lab->diagnosticsForTestOnly()['local_email_match']);
        $this->assertNoGrant($second, $code2);
    }

    public function test_expired_local_proof_cannot_claim_trial_after_nine_hundred_seconds(): void
    {
        $this->send('issue', 'phone')->assertOk();
        $code = $this->lab->codeForTestOnly('phone');
        self::assertIsString($code);
        $this->send('verify', 'phone', $code)->assertOk();
        self::assertTrue($this->lab->diagnosticsForTestOnly()['local_phone_match']);

        $this->lab->advanceClockForTestOnly(900);
        $expired = $this->send('trial', 'phone');
        self::assertFalse($this->lab->diagnosticsForTestOnly()['local_phone_match']);
        self::assertSame(0, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertSame('sandbox_trial_requires_local_otp',
            $this->lab->eventsForTestOnly()[2]);
        $this->assertNoGrant($expired, $code);
    }

    public function test_duplicate_json_and_real_looking_payloads_do_not_mutate_the_lab(): void
    {
        foreach ([
            '{"fixture":"real","fixture":"synthetic","channel":"email"}',
            '{"fixture":"synthetic","channel":"email","channel":"phone"}',
            '{"fixture":"synthetic","fi\u0078ture":"synthetic","channel":"email"}',
            '{"fixture":"synthetic","channel":"email","email":"person@example.org"}',
            '{"fixture":"synthetic","channel":"phone","cnpj":"12.ABC.345/01DE-35"}',
            '{"fixture":"synthetic","channel":null}',
            '{"fixture":"synthetic","channel":"whatsapp"}',
        ] as $wire) {
            $this->assertNoGrant($this->raw('trial', $wire));
        }
        self::assertSame(0, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertSame(array_fill(0, 7, 'sandbox_rejected_untrusted_payload'),
            $this->lab->eventsForTestOnly());
    }

    public function test_test_only_http_route_does_not_escape_into_runtime(): void
    {
        $this->postJson('/signup', ['fixture' => 'synthetic'])->assertNotFound();
        $this->postJson('/api/trial', ['fixture' => 'synthetic'])->assertNotFound();
        $this->get('/__ci/f3-258/issue')->assertStatus(405);
        $this->postJson('/__ci/f3-258/unknown', ['fixture' => 'synthetic'])->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $path) {
            $source = file_get_contents(dirname(__DIR__, 2).'/'.$path);
            self::assertIsString($source);
            self::assertStringNotContainsString('f3-258', $source);
            self::assertStringNotContainsString('SignupTrialSequencedHttpLab', $source);
        }
        self::assertFalse($this->lab->diagnosticsForTestOnly()['can_register']);
        self::assertFalse($this->lab->diagnosticsForTestOnly()['can_authenticate']);
    }
}
