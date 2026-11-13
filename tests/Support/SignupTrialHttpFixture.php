<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineSignupOtpPublicFeedbackPreview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * F3-252: test-only Laravel HTTP adapter. No Route::post call lives here.
 *
 * Fixture endpoints are registered ONLY by one PHPUnit Feature test, never
 * from routes/, app/, service providers, bootstrappers or a deployable file.
 * Every response is a NO-GO demo message; internal results stay in memory.
 */
final class SignupTrialHttpFixture
{
    private const START = 1700000000;

    private readonly SignupOtpLifecycleSandbox $otp;
    private readonly TrialEnrollmentLifecycleSandbox $trial;
    private readonly OfflineSignupOtpPublicFeedbackPreview $neutral;

    /** @var array{email:string,phone:string} Opaque bindings, test-only */
    private readonly array $contacts;
    private readonly string $origin;

    /** @var array<string,string> Ephemeral codes ONLY readable by PHPUnit */
    private array $testCodes = [];
    private int $tick = 0;

    /** @var list<string> Internal only, never serialized to HTTP */
    private array $trace = [];

    public function __construct()
    {
        $this->otp = new SignupOtpLifecycleSandbox(
            'f3-252-fixture-only-otp-key-cryptographic-32bytes',
        );
        $this->trial = new TrialEnrollmentLifecycleSandbox(
            'f3-252-fixture-only-trial-hmac-key-32bytes',
        );
        $this->neutral = new OfflineSignupOtpPublicFeedbackPreview();
        $contactBinding = new OfflineSignupContactHmacBinding();
        $this->contacts = [
            'email' => $contactBinding->derive(
                'only-synthetic-http-lab@example.test',
                'email',
                'f3-252-fixture-only-contact-hmac-key-32bytes',
            ),
            'phone' => $contactBinding->derive(
                '+5511999988888',
                'phone',
                'f3-252-fixture-only-contact-hmac-key-32bytes',
            ),
        ];
        $this->origin = hash_hmac(
            'sha256', 'fixture-loopback-not-a-real-ip',
            'f3-252-fixture-only-origin-hmac-key-32bytes',
        );
    }

    public function handle(Request $request, string $operation): JsonResponse
    {
        // These are ONLY synthetic request contracts, never a login route.
        // Fail silently/safely for unknown real-looking payloads, regardless
        // of whether an OTP candidate might have coincidentally matched.
        if (!in_array($operation, ['issue', 'verify', 'trial'], true)) {
            return self::reply(self::deny('demo_only_no_action'), 404);
        }

        $phase = $operation === 'verify' ? 'verify' : 'issue';
        $neutral = $this->neutral->preview(
            $phase, $phase === 'issue'
                ? 'sample_within_illustrative_budget'
                : 'candidate_mismatch',
        );

        $payload = self::syntheticJson($request, $operation);
        if ($payload === null) {
            $this->trace[] = 'sandbox_rejected_untrusted_payload';
            return self::reply(self::payload($neutral, $operation));
        }

        $channel = $payload['channel'] ?? 'email';
        if ($operation === 'issue') {
            $issued = $this->otp->issue(
                $this->contacts[$channel], $this->origin, $channel,
                self::START + $this->tick++,
            );
            $this->trace[] = $issued['status'];
            if ($issued['status'] === 'sandbox_code_created') {
                $this->testCodes[$channel] = $issued['delivery_code'];
            }
        } elseif ($operation === 'verify') {
            $state = $this->otp->verify(
                $this->contacts[$channel], $this->origin, $channel,
                $payload['code'], self::START + $this->tick++,
            );
            $this->trace[] = $state['status'];
        } else {
            // The phase is intentionally NOT gated by the previous OTP
            // response. Fake matching is never authorization or eligibility.
            $marker = $this->trial->observeFirstUse(
                '12.ABC.345/01DE-35', self::START + 3,
            );
            $this->trace[] = $marker['status'];
        }

        return self::reply(self::payload($neutral, $operation));
    }

    /**
     * Only PHPUnit accesses this method. Never place it in an HTTP response.
     */
    public function otpForTestOnly(string $channel = 'email'): ?string
    {
        return $this->testCodes[$channel] ?? null;
    }

    /**
     * Simulate a future instant within a single PHPUnit request sequence.
     * No production time control, HTTP route, durable clock or persistence.
     */
    public function advanceClockForTestOnly(int $seconds): void
    {
        if ($seconds < 1 || $seconds > 900 || $this->tick > 900) {
            throw new \InvalidArgumentException('Relogio ficticio de OTP invalido.');
        }
        $this->tick += $seconds;
    }

    /** @return list<string> */
    public function traceForTestOnly(): array
    {
        return $this->trace;
    }

    /** @return array<string,int|bool> */
    public function diagnosticsForTestOnly(): array
    {
        return [
            'trace_events' => count($this->trace),
            'trial_markers_in_memory' =>
                $this->trial->diagnostics()['synthetic_company_markers_in_memory'],
            'otp_only_in_one_process' => true,
            'durable_uniqueness_verified' => false,
            'actual_identity_verified' => false,
            'can_register' => false,
            'can_authenticate' => false,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function syntheticJson(Request $request, string $operation): ?array
    {
        if (!$request->isJson()) {
            return null;
        }
        $raw = $request->getContent();
        if (!is_string($raw) || strlen($raw) > 128) {
            return null;
        }
        $input = json_decode($raw, true);
        if (!is_array($input) || array_is_list($input)
            || ($input['fixture'] ?? null) !== 'synthetic') {
            return null;
        }

        // No phone numbers, email addresses, arbitrary IDs or extra fields.
        // "phone" means a fully invented E.164 contact in this PHP fixture,
        // NOT a WhatsApp transport approval, a real recipient or delivery.
        $keys = array_keys($input);
        sort($keys, SORT_STRING);
        $allowed = ['fixture'];
        if ($operation === 'verify') {
            $allowed[] = 'code';
        }
        if ($operation !== 'trial' && array_key_exists('channel', $input)) {
            $allowed[] = 'channel';
        }
        sort($allowed, SORT_STRING);
        // A present null channel must never collapse to the email default.
        // Only an *absent* key may use the backward-compatible default.
        $channel = array_key_exists('channel', $input)
            ? $input['channel'] : 'email';
        if ($keys !== $allowed || !in_array($channel, ['email', 'phone'], true)) {
            return null;
        }
        if ($operation === 'verify'
            && (!is_string($input['code'] ?? null)
                || preg_match('/\A[0-9]{6}\z/D', $input['code']) !== 1)) {
            return null;
        }

        // All permitted wire keys and values are fixed ASCII tokens without
        // a colon. Every ':' in a valid object therefore separates one key
        // from one value. json_decode() otherwise silently accepts duplicate
        // keys and keeps only the last occurrence (even when keys are escaped).
        // Reject that ambiguous wire form before mutating the sandbox state.
        // This rule belongs ONLY to the restricted PHPUnit fixture, not to
        // an approved production JSON parser.
        if (substr_count($raw, ':') !== count($input)) {
            return null;
        }

        return $input;
    }

    /**
     * Response does NOT echo supplied input, OTP, challenge, HMAC, email,
     * CNPJ, origin, hidden failure mode or internal state.
     *
     * @param array<string,mixed> $neutral
     * @return array<string,mixed>
     */
    private static function payload(array $neutral, string $operation): array
    {
        return [
            'protocol' => 'imagine.crm.fixture_http_only.v1',
            'status' => 'REVIEW_ONLY_DENIED',
            'operation' => $operation,
            'public_status_code' => $neutral['public_status_code'],
            'public_message' => $neutral['public_message'],
            'contact_existence_known' => false,
            'otp_identity_verified' => false,
            'trial_eligibility_verified' => false,
            'tenant_membership_verified' => false,
            'atomic_otp_consume_verified' => false,
            'durable_trial_uniqueness_verified' => false,
            'transport_verified' => false,
            'can_send' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_create_tenant' => false,
            'can_reserve_trial' => false,
            'can_allocate_quota' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    /** @param array<string,mixed> $payload */
    private static function reply(array $payload, int $status = 200): JsonResponse
    {
        return new JsonResponse($payload, $status, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    /** @return array<string,mixed> */
    private static function deny(string $code): array
    {
        return [
            'protocol' => 'imagine.crm.fixture_http_only.v1',
            'status' => 'REVIEW_ONLY_DENIED',
            'public_status_code' => $code,
            'can_register' => false,
            'can_authenticate' => false,
        ];
    }
}
