<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use Tests\Support\SignupTrialSequencedHttpLab;
use Tests\TestCase;

/**
 * F3-259: HTTP-driven fictional trial and quotas, with observation only
 * from PHPUnit. No route for snapshot, billing, customer, plan or login.
 */
final class SignupTrialWindowQuotaHttpLabTest extends TestCase
{
    private const START = 1700000000;
    private const TRIAL_SECONDS = 14 * 86400;

    private SignupTrialSequencedHttpLab $lab;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = new SignupTrialSequencedHttpLab();
        $lab = $this->lab;

        Route::post('/__ci/f3-259/{operation}',
            static fn (Request $request, string $operation) =>
                $lab->handle($request, $operation));
    }

    private function send(string $operation, string $channel, ?string $code = null): TestResponse
    {
        $payload = ['fixture' => 'synthetic', 'channel' => $channel];
        if ($code !== null) {
            $payload['code'] = $code;
        }
        return $this->postJson('/__ci/f3-259/'.$operation, $payload);
    }

    private function localTrial(string $channel = 'email'): array
    {
        $this->send('issue', $channel)->assertOk();
        $code = $this->lab->codeForTestOnly($channel);
        self::assertIsString($code);
        $verified = $this->send('verify', $channel, $code)->assertOk();
        self::assertFalse($verified->json('can_register'));
        $trial = $this->send('trial', $channel)->assertOk();
        self::assertFalse($trial->json('can_reserve_trial'));
        self::assertNull($trial->headers->get('Set-Cookie'));
        self::assertSame('no-store, private', $trial->headers->get('Cache-Control'));

        return [$code, $trial];
    }

    public function test_http_otp_sequence_exposes_14_day_trial_and_seven_quotas_to_phpunit_only(): void
    {
        $before = $this->lab->trialReadoutAtForTestOnly(self::START);
        self::assertSame('sandbox_no_trial_marker', $before['status']);
        self::assertFalse($before['can_reserve_trial']);

        [$code, $trial] = $this->localTrial();
        self::assertSame('sandbox_first_trial_marker',
            $this->lab->eventsForTestOnly()[2]);
        $started = self::START + 2;

        $start = $this->lab->trialReadoutAtForTestOnly($started);
        self::assertSame('sandbox_trial_snapshot_only', $start['status']);
        self::assertSame('within_window', $start['trial_phase']);
        self::assertSame(14, $start['illustrative_trial_days']);
        self::assertSame(14, $start['remaining_days_rounded_up']);

        $beforeLast = $this->lab->trialReadoutAtForTestOnly(
            $started + self::TRIAL_SECONDS - 1,
        );
        self::assertSame('within_window', $beforeLast['trial_phase']);
        self::assertSame(1, $beforeLast['remaining_days_rounded_up']);

        $expired = $this->lab->trialReadoutAtForTestOnly(
            $started + self::TRIAL_SECONDS,
        );
        self::assertSame('after_window', $expired['trial_phase']);
        self::assertSame(0, $expired['remaining_days_rounded_up']);

        $limits = [];
        foreach ($expired['quota_meters'] as $quota) {
            self::assertSame(0, $quota['illustrative_used']);
            self::assertSame('in_range', $quota['band']);
            $limits[$quota['meter']] = $quota['illustrative_limit'];
        }
        self::assertSame([
            'users' => 2,
            'leads' => 20,
            'automations' => 2,
            'pipelines' => 2,
            'attachments_bytes' => 20 * 1024 * 1024,
            'ai_initial_credits' => 10,
            'ai_bonus_credits' => 10,
        ], $limits);
        foreach ([
            'registered_company_verified', 'company_owner_verified',
            'tenant_membership_verified', 'durable_uniqueness_verified',
            'consent_and_lgpd_verified', 'can_reserve_trial',
            'can_create_tenant', 'can_register', 'can_authenticate',
            'can_charge', 'can_allocate_quota', 'can_persist',
        ] as $flag) {
            self::assertFalse($expired[$flag], $flag);
        }

        $json = json_encode($expired, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('12.ABC.345/01DE-35', $json);
        self::assertStringNotContainsString('12ABC34501DE35', $json);
        self::assertStringNotContainsString('"delivery_code"', $json);
        self::assertStringNotContainsString('digest', $json);

        $wire = (string) $trial->getContent();
        self::assertStringNotContainsString('within_window', $wire);
        self::assertStringNotContainsString('sandbox_first_trial_marker', $wire);
        self::assertStringNotContainsString('quota_meters', $wire);
        self::assertStringNotContainsString($code, $wire);
    }

    public function test_expired_14_day_trial_cannot_be_restarted_with_new_local_otp(): void
    {
        [$oldCode, $first] = $this->localTrial();
        $this->lab->advanceTrialClockForTestOnly(self::TRIAL_SECONDS);
        $this->send('issue', 'email')->assertOk();
        $newCode = $this->lab->codeForTestOnly('email');
        self::assertIsString($newCode);
        $this->send('verify', 'email', $newCode)->assertOk();
        $duplicate = $this->send('trial', 'email')->assertOk();

        self::assertSame('sandbox_duplicate_trial_claim',
            $this->lab->eventsForTestOnly()[5]);
        self::assertSame(1,
            $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertFalse($this->lab->diagnosticsForTestOnly()['can_register']);
        self::assertFalse($this->lab->diagnosticsForTestOnly()['local_email_match']);
        self::assertSame($first->json(), $duplicate->json());

        $expired = $this->lab->trialReadoutAtForTestOnly(
            self::START + 2 + self::TRIAL_SECONDS,
        );
        self::assertSame('after_window', $expired['trial_phase']);
        self::assertSame(0, $expired['remaining_days_rounded_up']);
        self::assertFalse($expired['can_charge']);
        self::assertFalse($expired['can_allocate_quota']);
        self::assertStringNotContainsString($oldCode, (string) $duplicate->getContent());
        self::assertStringNotContainsString($newCode, (string) $duplicate->getContent());
    }

    public function test_two_local_channels_do_not_create_two_trials_for_one_fictional_company(): void
    {
        [$emailCode, $emailTrial] = $this->localTrial('email');
        [$phoneCode, $phoneTrial] = $this->localTrial('phone');
        self::assertSame('sandbox_duplicate_trial_claim',
            $this->lab->eventsForTestOnly()[5]);
        self::assertSame(1,
            $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        self::assertSame($emailTrial->json(), $phoneTrial->json());
        foreach ([$emailCode, $phoneCode] as $code) {
            self::assertStringNotContainsString($code, (string) $phoneTrial->getContent());
        }
    }

    public function test_invalid_trial_clock_claims_fail_without_changing_trial_or_public_routes(): void
    {
        foreach ([0, -1, 15 * 86400 + 1, PHP_INT_MAX] as $seconds) {
            try {
                $this->lab->advanceTrialClockForTestOnly($seconds);
                self::fail('Unbounded trial time jump accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Relogio ficticio de trial invalido.', $e->getMessage());
            }
        }
        try {
            $this->lab->trialReadoutAtForTestOnly(-1);
            self::fail('Invalid trial timestamp accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Trial ficticio invalido.', $e->getMessage());
        }

        self::assertSame(0, $this->lab->diagnosticsForTestOnly()['trial_markers_in_memory']);
        $this->postJson('/signup', ['fixture' => 'synthetic'])->assertNotFound();
        $this->get('/__ci/f3-259/issue')->assertStatus(405);
        foreach (['routes/web.php', 'bootstrap/app.php'] as $path) {
            $source = file_get_contents(dirname(__DIR__, 2).'/'.$path);
            self::assertIsString($source);
            self::assertStringNotContainsString('__ci/f3-259', $source);
            self::assertStringNotContainsString('SignupTrialWindowQuotaHttpLabTest', $source);
        }
    }
}
