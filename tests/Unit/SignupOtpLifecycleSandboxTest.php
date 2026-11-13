<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineSignupOtpChallenge;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Support\SignupOtpLifecycleSandbox;

final class SignupOtpLifecycleSandboxTest extends TestCase
{
    private const OTP_KEY = 'synthetic-otp-key-longer-than-thirty-two-bytes';
    private const CONTACT_KEY = 'fictional-contact-key-with-over-thirty-two-bytes';
    private const ORIGIN_KEY = 'fictional-origin-key-with-over-thirty-two-bytes';

    private static function contact(string $address, string $channel = 'email'): string
    {
        return (new OfflineSignupContactHmacBinding())->derive(
            $address, $channel, self::CONTACT_KEY,
        );
    }

    private static function origin(string $name): string
    {
        return hash_hmac('sha256', 'demo-origin:'.$name, self::ORIGIN_KEY);
    }

    private static function wrongCode(string $real): string
    {
        return $real === '000000' ? '000001' : '000000';
    }

    public function test_issue_wrong_attempt_success_replay_and_second_instance_are_local_only(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $contact = self::contact('demo.one@example.com');
        $origin = self::origin('one');
        $issue = $sandbox->issue($contact, $origin, 'email', 1000);

        self::assertSame('sandbox_code_created', $issue['status']);
        self::assertMatchesRegularExpression('/\A[0-9]{6}\z/D', $issue['delivery_code']);
        self::assertFalse($issue['can_send']);

        $wrong = $sandbox->verify(
            $contact, $origin, 'email', self::wrongCode($issue['delivery_code']), 1001,
        );
        self::assertSame('sandbox_wrong_code', $wrong['status']);
        $right = $sandbox->verify($contact, $origin, 'email', $issue['delivery_code'], 1002);
        self::assertSame('sandbox_match_consumed_in_memory', $right['status']);
        self::assertSame('sandbox_replay_denied',
            $sandbox->verify($contact, $origin, 'email', $issue['delivery_code'], 1003)['status']);

        $freshProcess = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        self::assertSame('sandbox_no_challenge',
            $freshProcess->verify($contact, $origin, 'email', $issue['delivery_code'], 1003)['status']);
        self::assertSame(1, $sandbox->diagnostics()['active_scopes_in_memory']);
        self::assertFalse($sandbox->diagnostics()['durable_state_verified']);
        self::assertFalse($right['can_grant_access']);
        self::assertFalse($right['can_register']);
        self::assertFalse($right['can_authenticate']);

        $output = json_encode([$wrong, $right, $sandbox->diagnostics()], JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($issue['delivery_code'], $output);
        self::assertStringNotContainsString($contact, $output);
        self::assertStringNotContainsString($origin, $output);
    }

    public function test_resend_replaces_old_code_and_enforces_cooldown(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $contact = self::contact('demo.two@example.com');
        $origin = self::origin('resend');
        $first = $sandbox->issue($contact, $origin, 'email', 1000);
        self::assertSame('sandbox_resend_cooldown',
            $sandbox->issue($contact, $origin, 'email', 1059)['status']);
        $second = $sandbox->issue($contact, $origin, 'email', 1060);
        self::assertSame('sandbox_code_created', $second['status']);
        self::assertNotNull($second['delivery_code']);

        // Codes can coincidentally collide (6 digits). A new challenge nonce
        // and MAC are sufficient to invalidate the earlier verifier, but if
        // the digits collide, the value remains valid for the new challenge.
        // Do not incorrectly claim digit-level rejection on collision.
        if ($first['delivery_code'] !== $second['delivery_code']) {
            self::assertSame('sandbox_wrong_code',
                $sandbox->verify($contact, $origin, 'email', $first['delivery_code'], 1061)['status']);
        }
        self::assertSame('sandbox_match_consumed_in_memory',
            $sandbox->verify($contact, $origin, 'email', $second['delivery_code'], 1062)['status']);
    }

    public function test_scopes_separate_contacts_and_channels_without_exposing_identity(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $a = self::contact('a@example.com');
        $b = self::contact('b@example.com');
        $phone = self::contact('+5511999988888', 'phone');
        $origin = self::origin('shared');
        $issue = $sandbox->issue($a, $origin, 'email', 2000);
        self::assertSame('sandbox_no_challenge',
            $sandbox->verify($b, $origin, 'email', $issue['delivery_code'], 2001)['status']);
        self::assertSame('sandbox_no_challenge',
            $sandbox->verify($phone, $origin, 'phone', $issue['delivery_code'], 2002)['status']);
        self::assertSame('sandbox_match_consumed_in_memory',
            $sandbox->verify($a, $origin, 'email', $issue['delivery_code'], 2003)['status']);
    }

    public function test_fifth_bad_code_exhausts_attempts_and_sixth_correct_code_is_denied(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $contact = self::contact('attempts@example.org');
        $origin = self::origin('attempts');
        $issue = $sandbox->issue($contact, $origin, 'email', 3000);
        for ($i = 0; $i < OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS; $i++) {
            self::assertSame('sandbox_wrong_code',
                $sandbox->verify($contact, $origin, 'email', self::wrongCode($issue['delivery_code']), 3001 + $i)['status']);
        }
        self::assertSame('sandbox_attempt_limit',
            $sandbox->verify($contact, $origin, 'email', $issue['delivery_code'], 3006)['status']);
        self::assertFalse($sandbox->diagnostics()['can_register']);
    }

    public function test_expiration_is_exact_at_fifteen_minutes(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $contact = self::contact('expiry@example.org');
        $origin = self::origin('expiry');
        $issue = $sandbox->issue($contact, $origin, 'email', 4000);
        self::assertSame('sandbox_expired',
            $sandbox->verify($contact, $origin, 'email', $issue['delivery_code'], 4900)['status']);
    }

    public function test_three_issues_per_contact_and_twenty_per_origin_use_memory_only(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $contact = self::contact('quota@example.org');
        $origin = self::origin('quota');
        foreach ([5000, 5060, 5120] as $time) {
            self::assertSame('sandbox_code_created',
                $sandbox->issue($contact, $origin, 'email', $time)['status']);
        }
        self::assertSame('sandbox_contact_budget_exhausted',
            $sandbox->issue($contact, $origin, 'email', 5180)['status']);

        $shared = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $groupOrigin = self::origin('group');
        for ($i = 0; $i < 20; $i++) {
            $person = self::contact('person'.$i.'@example.test');
            self::assertSame('sandbox_code_created',
                $shared->issue($person, $groupOrigin, 'email', 6000 + $i)['status']);
        }
        self::assertSame('sandbox_origin_budget_exhausted',
            $shared->issue(self::contact('person20@example.test'), $groupOrigin, 'email', 6021)['status']);
        self::assertSame(20, $shared->diagnostics()['active_scopes_in_memory']);
    }

    public function test_origin_verification_budget_also_counts_attempts_for_unknown_challenges(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $contact = self::contact('missing@example.org');
        $origin = self::origin('verification-bulk');
        for ($i = 0; $i < 60; $i++) {
            self::assertSame('sandbox_no_challenge',
                $sandbox->verify($contact, $origin, 'email', '000000', 7000 + $i)['status']);
        }
        self::assertSame('sandbox_origin_verification_budget_exhausted',
            $sandbox->verify($contact, $origin, 'email', '000000', 7060)['status']);
    }

    public function test_time_reversal_plain_identifiers_and_unsupported_channels_fail_closed(): void
    {
        $sandbox = new SignupOtpLifecycleSandbox(self::OTP_KEY);
        $contact = self::contact('safety@example.org');
        $origin = self::origin('safety');
        $sandbox->issue($contact, $origin, 'email', 8000);
        foreach ([
            static fn () => $sandbox->verify($contact, $origin, 'email', '123456', 7999),
            static fn () => $sandbox->issue('safety@example.org', $origin, 'email', 8001),
            static fn () => $sandbox->issue($contact, '1.2.3.4', 'email', 8001),
            static fn () => $sandbox->issue($contact, $contact, 'email', 8001),
            static fn () => $sandbox->issue($contact, $origin, 'whatsapp', 8001),
        ] as $invalid) {
            try {
                $invalid();
                self::fail('Unsafe sandbox lifecycle input was accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Jornada OTP de laboratório invalida.', $e->getMessage());
            }
        }
    }
}
