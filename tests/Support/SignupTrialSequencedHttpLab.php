<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Identity\OfflineSignupContactHmacBinding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * F3-258: sequential signup/trial experiment through Laravel HTTP in PHPUnit.
 *
 * ALL state lives in this one test fixture object. A local OTP observation
 * is NOT authenticated identity, verified contact ownership, permission to
 * register, durable trial uniqueness or an authorization decision.
 * No route is registered here; only a PHPUnit feature test may do that.
 */
final class SignupTrialSequencedHttpLab
{
    private const SYNTHETIC_CNPJ = '12.ABC.345/01DE-35';
    private const START = 1700000000;

    private readonly SignupOtpLifecycleSandbox $otp;
    private readonly TrialEnrollmentLifecycleSandbox $trial;

    /** @var array{email:string,phone:string} */
    private readonly array $contacts;

    private readonly string $origin;

    /** @var array{email:bool,phone:bool} */
    private array $localOtpMatched = ['email' => false, 'phone' => false];

    /** @var array<string,string> Codes only visible to PHPUnit, never HTTP */
    private array $codes = [];

    /** @var list<string> Internal-only test audit markers */
    private array $events = [];

    private int $clock = self::START;

    public function __construct()
    {
        $this->otp = new SignupOtpLifecycleSandbox(
            'f3-258-fixture-only-otp-key-cryptographic-32bytes',
        );
        $this->trial = new TrialEnrollmentLifecycleSandbox(
            'f3-258-fixture-only-trial-hmac-key-32bytes',
        );

        $binding = new OfflineSignupContactHmacBinding();
        $this->contacts = [
            'email' => $binding->derive(
                'only-synthetic-sequence@example.test',
                'email',
                'f3-258-fixture-only-contact-hmac-key-32bytes',
            ),
            'phone' => $binding->derive(
                '+5511999988888',
                'phone',
                'f3-258-fixture-only-contact-hmac-key-32bytes',
            ),
        ];
        $this->origin = hash_hmac(
            'sha256',
            'f3-258-constant-synthetic-origin',
            'f3-258-fixture-only-origin-hmac-key-32bytes',
        );
    }

    public function handle(Request $request, string $operation): JsonResponse
    {
        if (!in_array($operation, ['issue', 'verify', 'trial'], true)) {
            return self::reply($operation, 404);
        }

        $input = self::readSynthetic($request, $operation);
        if ($input === null) {
            $this->events[] = 'sandbox_rejected_untrusted_payload';
            return self::reply($operation);
        }

        $channel = $input['channel'];

        if ($operation === 'issue') {
            // A new local issue request invalidates any previous local proof,
            // including when the anti-abuse sandbox rejects the new issuance.
            $this->localOtpMatched[$channel] = false;
            $issued = $this->otp->issue(
                $this->contacts[$channel],
                $this->origin,
                $channel,
                $this->clock++,
            );
            $this->events[] = $issued['status'];
            if ($issued['status'] === 'sandbox_code_created') {
                $this->codes[$channel] = $issued['delivery_code'];
            }
        } elseif ($operation === 'verify') {
            $verified = $this->otp->verify(
                $this->contacts[$channel],
                $this->origin,
                $channel,
                $input['code'],
                $this->clock++,
            );
            $this->events[] = $verified['status'];
            // This flag is a local scenario transition, NOT an authority.
            $this->localOtpMatched[$channel] =
                $verified['status'] === 'sandbox_match_consumed_in_memory';
        } elseif (!$this->localOtpMatched[$channel]) {
            $this->events[] = 'sandbox_trial_requires_local_otp';
        } else {
            // Consume the local proof before observing uniqueness: even a
            // second or failed trial cannot reuse a previous OTP match.
            $this->localOtpMatched[$channel] = false;
            $observation = $this->trial->observeFirstUse(
                self::SYNTHETIC_CNPJ,
                $this->clock++,
            );
            $this->events[] = $observation['status'];
        }

        return self::reply($operation);
    }

    public function codeForTestOnly(string $channel): ?string
    {
        return $this->codes[$channel] ?? null;
    }

    public function advanceClockForTestOnly(int $seconds): void
    {
        if ($seconds < 1 || $seconds > 900) {
            throw new InvalidArgumentException('Relogio ficticio invalido.');
        }
        $this->clock += $seconds;
    }

    /** @return list<string> */
    public function eventsForTestOnly(): array
    {
        return $this->events;
    }

    /** @return array<string,int|bool> */
    public function diagnosticsForTestOnly(): array
    {
        return [
            'trial_markers_in_memory' =>
                $this->trial->diagnostics()['synthetic_company_markers_in_memory'],
            'local_email_match' => $this->localOtpMatched['email'],
            'local_phone_match' => $this->localOtpMatched['phone'],
            'durable_trial_uniqueness_verified' => false,
            'verified_identity_or_contact_ownership' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_send' => false,
        ];
    }

    /**
     * This is a deliberately tiny, fictional JSON wire protocol.
     * The exact allowed keys and values contain no literal colon. Counting
     * separators rejects duplicate JSON keys (including Unicode-escaped
     * spellings) that json_decode() would otherwise silently collapse.
     * This MUST NOT be reused as a general-purpose production JSON parser.
     *
     * @return array{fixture:string,channel:string,code?:string}|null
     */
    private static function readSynthetic(Request $request, string $operation): ?array
    {
        if (!$request->isJson()) {
            return null;
        }
        $raw = $request->getContent();
        if (!is_string($raw) || strlen($raw) > 128) {
            return null;
        }
        $input = json_decode($raw, true);
        if (!is_array($input) || array_is_list($input)) {
            return null;
        }

        $expected = $operation === 'verify'
            ? ['channel', 'code', 'fixture']
            : ['channel', 'fixture'];
        $keys = array_keys($input);
        sort($keys, SORT_STRING);

        if ($keys !== $expected
            || $input['fixture'] !== 'synthetic'
            || !in_array($input['channel'], ['email', 'phone'], true)
            || ($operation === 'verify' &&
                (!is_string($input['code'])
                    || preg_match('/\A[0-9]{6}\z/D', $input['code']) !== 1))
            || substr_count($raw, ':') !== count($input)) {
            return null;
        }

        return $input;
    }

    private static function reply(string $operation, int $status = 200): JsonResponse
    {
        // Valid, invalid and matched requests expose the same neutral result
        // for each operation: no account enumeration or fictional grant.
        return new JsonResponse([
            'protocol' => 'imagine.crm.sequenced_http_lab_only.v1',
            'operation' => $operation,
            'status' => 'REVIEW_ONLY_DENIED',
            'public_status_code' => 'demo_only_no_action',
            'public_message' => 'Demonstração Imagine CRM: nenhuma mensagem foi enviada, conta criada ou trial ativado.',
            'informational_only' => true,
            'contact_ownership_verified' => false,
            'identity_verified' => false,
            'tenant_membership_verified' => false,
            'durable_otp_consumption_verified' => false,
            'durable_trial_uniqueness_verified' => false,
            'can_send' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_create_tenant' => false,
            'can_reserve_trial' => false,
            'can_allocate_quota' => false,
            'can_charge' => false,
            'can_persist' => false,
        ], $status, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }
}
