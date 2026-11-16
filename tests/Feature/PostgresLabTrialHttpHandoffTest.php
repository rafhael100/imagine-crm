<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Plans\LaboratoryHistoricalTrialAliases;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Support\SignupTrialSequencedHttpLab;
use Tests\TestCase;

/**
 * F3-TRIAL-HTTP-PG01: the actual Laravel test kernel delivers synthetic
 * OTP/trial requests while a restricted PostgreSQL role enforces uniqueness.
 *
 * One fixed fictional company, no contact/CNPJ from requests, no public routes
 * in sources, no OTP transport, no real account or trial activation.
 *
 * This is intentionally a PHPUnit-only adapter, NOT application production
 * code. No relation to web.php, service providers or deploy commands.
 */
final class PostgresLabTrialHttpHandoffTest extends TestCase
{
    private const CNPJ = '12.ABC.345/01DE-35';
    private const KEY = 'crm-f3-trial-lab-synthetic-dedicated-hmac-key-32-bytes';
    private const START = 1700000000;

    private Connection $admin;
    private Connection $restricted;
    private SignupTrialSequencedHttpLab $localLab;

    /** @var list<array<string,mixed>> Test-only PG snapshots, never JSON. */
    private array $pgObservations = [];

    protected function setUp(): void
    {
        parent::setUp();

        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_TRIAL_LEDGER_LAB') !== 'synthetic-only'
            || getenv('TRIAL_TEST_USERNAME') !== 'crm_trial_claim_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('Only disposable GitHub PostgreSQL lab with test-only HTTP route.');
        }

        $this->admin = DB::connection();
        $this->clean();

        $settings = $pg;
        $settings['url'] = null;
        $settings['username'] = getenv('TRIAL_TEST_USERNAME');
        $settings['password'] = getenv('TRIAL_TEST_PASSWORD');
        config(['database.connections.trial_http_claim_ci' => $settings]);
        DB::purge('trial_http_claim_ci');
        $this->restricted = DB::connection('trial_http_claim_ci');

        $this->localLab = new SignupTrialSequencedHttpLab();
        // This route exists ONLY after PHPUnit constructed this test class.
        Route::post('/__ci/f3-trial-pg/{operation}', function (
            Request $request,
            string $operation,
        ): JsonResponse {
            $before = count($this->localLab->eventsForTestOnly());
            $response = $this->localLab->handle($request, $operation);
            $events = $this->localLab->eventsForTestOnly();

            if ($operation === 'trial'
                && count($events) === $before + 1
                && $events[count($events) - 1] === 'sandbox_first_trial_marker') {
                // The test kernel saw a local synthetic OTP sequence.
                // This is NOT real identity/ownership or authorizing OTP.
                $this->pgObservations[] = (new LaboratoryHistoricalTrialAliases(
                    $this->restricted,
                    [['version' => 1, 'secret' => self::KEY]],
                ))->observe(self::CNPJ, self::START + 4);
            }

            // NEVER augment the neutral public response with DB status,
            // fingerprint, proof, identity, tenant, account or raw input.
            return $response;
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->restricted)) {
            while ($this->restricted->transactionLevel() > 0) {
                $this->restricted->rollBack();
            }
            $this->clean();
            DB::purge('trial_http_claim_ci');
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $hmac = (new OfflineCnpjTrialHmacBinding())->derive(self::CNPJ, self::KEY);
        $this->admin->table('crm_lab_trial_claims')->where('cnpj_hmac', $hmac)->delete();
    }

    private function send(string $operation, string $channel = 'email', ?string $code = null): TestResponse
    {
        $input = ['fixture' => 'synthetic', 'channel' => $channel];
        if ($code !== null) {
            $input['code'] = $code;
        }

        return $this->postJson('/__ci/f3-trial-pg/'.$operation, $input);
    }

    private function assertNeutral(TestResponse $response, ?string $code = null): void
    {
        $response->assertOk();
        $this->assertSame('REVIEW_ONLY_DENIED', $response->json('status'));
        $this->assertSame('demo_only_no_action', $response->json('public_status_code'));

        foreach ([
            'contact_ownership_verified', 'identity_verified',
            'tenant_membership_verified', 'durable_otp_consumption_verified',
            'durable_trial_uniqueness_verified', 'can_send', 'can_register',
            'can_authenticate', 'can_create_tenant', 'can_reserve_trial',
            'can_allocate_quota', 'can_charge', 'can_persist',
        ] as $flag) {
            $this->assertFalse($response->json($flag), $flag);
        }

        $wire = (string) $response->getContent();
        foreach (array_filter([
            self::CNPJ, str_replace(['.', '/', '-'], '', self::CNPJ),
            $code, 'cnpj_hmac', 'trial_key', 'sandbox_', 'secret',
            'access_token', 'tenant_id', 'delivery_code',
        ]) as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $wire);
        }
        $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertNull($response->headers->get('Set-Cookie'));
    }

    private function successfulLocalSequence(string $channel = 'email'): array
    {
        $issue = $this->send('issue', $channel);
        $code = $this->localLab->codeForTestOnly($channel);
        $this->assertIsString($code);
        $this->assertMatchesRegularExpression('/\A[0-9]{6}\z/D', $code);
        $verify = $this->send('verify', $channel, $code);
        return [$code, $issue, $verify];
    }

    public function test_two_independent_http_fixture_sessions_cannot_claim_twice_in_postgresql(): void
    {
        [$code, $issue, $verify] = $this->successfulLocalSequence();
        $first = $this->send('trial');

        $this->assertCount(1, $this->pgObservations);
        $this->assertSame('synthetic_first_marker', $this->pgObservations[0]['status_code']);

        // A new PHPUnit in-process scenario starts with no local OTP
        // or company marker, but the SAME physical PostgreSQL database.
        $this->localLab = new SignupTrialSequencedHttpLab();
        [$secondCode, $otherIssue, $otherVerify] = $this->successfulLocalSequence('phone');
        $second = $this->send('trial', 'phone');

        $this->assertCount(2, $this->pgObservations);
        $this->assertSame('synthetic_duplicate_marker', $this->pgObservations[1]['status_code']);
        $this->assertSame(1, $this->admin->table('crm_lab_trial_claims')->count());

        $this->assertSame($first->json(), $second->json());
        foreach ([$issue, $verify, $first, $otherIssue, $otherVerify, $second] as $response) {
            $this->assertNeutral($response, $code);
            $this->assertNeutral($response, $secondCode);
        }
    }

    public function test_wrong_or_missing_otp_never_attempts_a_pg_claim(): void
    {
        $trialBefore = $this->send('trial');
        [$code, $issue, $verify] = $this->successfulLocalSequence();

        // Issuing again deliberately clears the fixture's local matched flag.
        $newIssue = $this->send('issue');
        $trialAfterReset = $this->send('trial');
        $malformed = $this->postJson('/__ci/f3-trial-pg/verify', [
            'fixture' => 'synthetic', 'channel' => 'email', 'code' => '12345',
        ]);
        $trialAfterInvalid = $this->send('trial');

        foreach ([$trialBefore, $issue, $verify, $newIssue,
            $trialAfterReset, $malformed, $trialAfterInvalid] as $res) {
            $this->assertNeutral($res, $code);
        }

        $this->assertCount(0, $this->pgObservations);
        $this->assertSame(0, $this->admin->table('crm_lab_trial_claims')->count());
        $this->assertFalse($this->localLab->diagnosticsForTestOnly()['can_register']);
    }

    public function test_untrusted_document_and_contact_claims_never_enter_the_trial_db(): void
    {
        foreach ([
            ['fixture' => 'synthetic', 'channel' => 'email', 'cnpj' => self::CNPJ],
            ['fixture' => 'synthetic', 'channel' => 'email', 'email' => 'contact@example.org'],
            ['fixture' => 'synthetic', 'channel' => 'email', 'role' => 'admin'],
            ['fixture' => 'real', 'channel' => 'email'],
        ] as $payload) {
            $res = $this->postJson('/__ci/f3-trial-pg/issue', $payload);
            $this->assertNeutral($res);
        }
        $this->assertCount(0, $this->pgObservations);
        $this->assertSame(0, $this->admin->table('crm_lab_trial_claims')->count());
    }

    public function test_all_business_signup_and_login_routes_remain_absent(): void
    {
        $this->get('/')->assertNotFound();
        $this->get('/crm/login')->assertNotFound();
        $this->get('/api/signup')->assertNotFound();
        $this->postJson('/api/trial', ['fixture' => 'synthetic'])->assertNotFound();
        $this->get('/__ci/f3-trial-pg/issue')->assertStatus(405);
        $this->postJson('/__ci/f3-trial-pg/unknown', ['fixture' => 'synthetic'])->assertNotFound();
        foreach (['routes/web.php', 'bootstrap/app.php'] as $file) {
            $source = file_get_contents(dirname(__DIR__, 2).'/'.$file);
            $this->assertIsString($source);
            $this->assertStringNotContainsString('__ci/f3-trial-pg', $source);
            $this->assertStringNotContainsString('PostgresLabTrialHttpHandoffTest', $source);
        }
    }
}
