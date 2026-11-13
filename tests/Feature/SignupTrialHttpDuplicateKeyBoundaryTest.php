<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Support\SignupTrialHttpFixture;
use Tests\TestCase;

/**
 * F3-256: wire-level ambiguity checks in a PHPUnit-only Laravel route.
 *
 * A duplicate key must never be normalized by json_decode() into a valid
 * fictional issue/verify/trial command. This does not expose an endpoint,
 * authorize signup or establish a production-grade JSON parser.
 */
final class SignupTrialHttpDuplicateKeyBoundaryTest extends TestCase
{
    private SignupTrialHttpFixture $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new SignupTrialHttpFixture();
        $fixture = $this->fixture;
        Route::post('/__ci/f3-256/{operation}',
            static fn (Request $request, string $operation) =>
                $fixture->handle($request, $operation));
    }

    private function rawPost(string $operation, string $wire): TestResponse
    {
        return $this->call(
            'POST', '/__ci/f3-256/'.$operation,
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $wire,
        );
    }

    private function validPost(string $operation, array $extra = []): TestResponse
    {
        return $this->postJson('/__ci/f3-256/'.$operation,
            array_merge(['fixture' => 'synthetic'], $extra));
    }

    public function test_repeated_or_escaped_fixture_keys_cannot_issue_an_otp(): void
    {
        foreach ([
            '{"fixture":"real","fixture":"synthetic"}',
            '{"fixture":"synthetic","fixture":"synthetic"}',
            '{"fixture":"synthetic","fi\u0078ture":"synthetic"}',
            '{"fixture":"synthetic","channel":"phone","channel":"email"}',
        ] as $wire) {
            $denied = $this->rawPost('issue', $wire)->assertOk();
            self::assertSame('REVIEW_ONLY_DENIED', $denied->json('status'));
            self::assertFalse($denied->json('can_send'));
            self::assertFalse($denied->json('can_register'));
            self::assertNull($this->fixture->otpForTestOnly('email'));
            self::assertNull($this->fixture->otpForTestOnly('phone'));
            self::assertSame('no-store, private',
                $denied->headers->get('Cache-Control'));
        }

        self::assertSame(array_fill(0, 4, 'sandbox_rejected_untrusted_payload'),
            $this->fixture->traceForTestOnly());

        // Ordinary JSON spacing is not an ambiguous extra key.
        $accepted = $this->rawPost('issue',
            ' { "fixture" : "synthetic", "channel" : "phone" } ')->assertOk();
        self::assertIsString($this->fixture->otpForTestOnly('phone'));
        self::assertSame('sandbox_code_created',
            $this->fixture->traceForTestOnly()[4]);
        self::assertFalse($accepted->json('can_register'));
    }

    public function test_duplicate_candidate_cannot_consume_an_otp_or_claim_trial(): void
    {
        $issued = $this->validPost('issue')->assertOk();
        $code = $this->fixture->otpForTestOnly('email');
        self::assertIsString($code);

        $ambiguous = $this->rawPost('verify',
            '{"fixture":"synthetic","code":"000000","code":"'.$code.'"}')
            ->assertOk();
        self::assertSame('sandbox_rejected_untrusted_payload',
            $this->fixture->traceForTestOnly()[1]);
        self::assertSame('REVIEW_ONLY_DENIED', $ambiguous->json('status'));
        self::assertSame('demo_only_no_action',
            $ambiguous->json('public_status_code'));
        self::assertFalse($ambiguous->json('can_authenticate'));

        $matched = $this->validPost('verify', ['code' => $code])->assertOk();
        self::assertSame('sandbox_match_consumed_in_memory',
            $this->fixture->traceForTestOnly()[2]);
        self::assertSame($ambiguous->json(), $matched->json());

        $replay = $this->validPost('verify', ['code' => $code])->assertOk();
        self::assertSame('sandbox_replay_denied',
            $this->fixture->traceForTestOnly()[3]);
        self::assertSame($matched->json(), $replay->json());

        $trialAmbiguous = $this->rawPost('trial',
            '{"fixture":"ignored","fixture":"synthetic"}')->assertOk();
        self::assertSame('sandbox_rejected_untrusted_payload',
            $this->fixture->traceForTestOnly()[4]);
        self::assertSame(0,
            $this->fixture->diagnosticsForTestOnly()['trial_markers_in_memory']);

        $trial = $this->validPost('trial')->assertOk();
        self::assertSame('sandbox_first_trial_marker',
            $this->fixture->traceForTestOnly()[5]);
        self::assertSame(1,
            $this->fixture->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertSame($trialAmbiguous->json(), $trial->json());

        foreach ([$issued, $ambiguous, $matched, $replay, $trialAmbiguous, $trial] as $response) {
            $body = (string) $response->getContent();
            self::assertStringNotContainsString($code, $body);
            self::assertStringNotContainsString('sandbox_', $body);
            self::assertStringNotContainsString('"cnpj"', $body);
            self::assertFalse($response->json('can_register'));
            self::assertFalse($response->json('can_persist'));
            self::assertNull($response->headers->get('Set-Cookie'));
        }
    }

    public function test_wire_rejection_does_not_publish_a_business_route(): void
    {
        $this->postJson('/signup', ['fixture' => 'synthetic'])->assertNotFound();
        $this->get('/__ci/f3-256/issue')->assertStatus(405);

        foreach (['routes/web.php', 'bootstrap/app.php'] as $path) {
            $source = file_get_contents(dirname(__DIR__, 2).'/'.$path);
            self::assertIsString($source);
            self::assertStringNotContainsString('__ci/f3-256', $source);
            self::assertStringNotContainsString('SignupTrialHttpDuplicateKeyBoundaryTest',
                $source);
        }
    }
}
