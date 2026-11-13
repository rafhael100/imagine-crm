<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupOtpChallenge;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineSignupOtpChallengeTest extends TestCase
{
    private const KEY = 'fixture-secret-with-32-plus-bytes-long-for-tests-only';
    private const BINDING = '9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b9b';

    public function test_issue_creates_crypto_random_six_digit_challenge_without_granting_access(): void
    {
        $engine = new OfflineSignupOtpChallenge();
        $issued = $engine->issue(self::KEY, self::BINDING, 'email', 1700000000);
        self::assertMatchesRegularExpression('/\A[0-9]{6}\z/D', $issued['delivery_code']);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $issued['challenge']['digest']);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{32}\z/D', $issued['challenge']['nonce']);
        self::assertSame(1700000900, $issued['challenge']['expires_at']);
        self::assertCount(4, $issued['challenge']);
        self::assertArrayNotHasKey('delivery_code', $issued['challenge']);
        self::assertFalse($issued['transport_configured']);
        self::assertFalse($issued['can_send']);
        self::assertFalse($issued['can_register']);
        self::assertFalse($issued['can_authenticate']);
        $second = $engine->issue(self::KEY, self::BINDING, 'email', 1700000000);
        self::assertNotSame($issued['challenge']['nonce'], $second['challenge']['nonce']);
        self::assertNotSame($issued['challenge']['digest'], $second['challenge']['digest']);
    }

    public function test_match_is_only_a_candidate_observation_and_expires_at_exact_boundary(): void
    {
        $engine = new OfflineSignupOtpChallenge();
        $issue = $engine->issue(self::KEY, self::BINDING, 'phone', 1000);
        $record = $issue['challenge'];
        $code = $issue['delivery_code'];

        $atStart = $engine->inspect($record, $code, self::KEY, self::BINDING, 'phone', 1000, 0, false);
        self::assertSame('candidate_matches_only', $atStart['status_code']);
        self::assertTrue($atStart['candidate_matches']);
        self::assertSame(900, $atStart['remaining_seconds']);
        foreach (['can_register', 'can_authenticate', 'can_grant_access', 'can_consume_challenge','can_send'] as $flag) {
            self::assertFalse($atStart[$flag], $flag);
        }

        $last = $engine->inspect($record, $code, self::KEY, self::BINDING, 'phone', 1899, 0, false);
        self::assertTrue($last['candidate_matches']);
        self::assertSame(1, $last['remaining_seconds']);
        $expires = $engine->inspect($record, $code, self::KEY, self::BINDING, 'phone', 1900, 0, false);
        self::assertSame('expired', $expires['status_code']);
        self::assertFalse($expires['candidate_matches']);
        self::assertSame(0, $expires['remaining_seconds']);
    }

    public function test_wrong_code_binding_channel_key_and_time_never_pass(): void
    {
        $engine = new OfflineSignupOtpChallenge();
        $issue = $engine->issue(self::KEY, self::BINDING, 'email', 4000);
        $record = $issue['challenge'];
        $code = $issue['delivery_code'];
        $wrongCode = $code === '000000' ? '000001' : '000000';
        foreach ([
            [$wrongCode, self::KEY, self::BINDING, 'email', 4010, 'candidate_mismatch'],
            [$code, str_repeat('z', 32), self::BINDING, 'email', 4010, 'candidate_mismatch'],
            [$code, self::KEY, str_repeat('a', 64), 'email', 4010, 'candidate_mismatch'],
            [$code, self::KEY, self::BINDING, 'phone', 4010, 'candidate_mismatch'],
            [$code, self::KEY, self::BINDING, 'email', 3999, 'before_issue'],
        ] as [$candidate, $key, $binding, $channel, $time, $expected]) {
            $view = $engine->inspect($record, $candidate, $key, $binding, $channel, $time, 1, false);
            self::assertSame($expected, $view['status_code']);
            self::assertFalse($view['candidate_matches']);
        }
    }

    public function test_exhausted_attempts_and_consumed_claim_are_denied_even_if_code_matches(): void
    {
        $engine = new OfflineSignupOtpChallenge();
        $issue = $engine->issue(self::KEY, self::BINDING, 'email', 2000);
        $record = $issue['challenge'];
        $code = $issue['delivery_code'];

        $budget = $engine->inspect($record, $code, self::KEY, self::BINDING, 'email', 2100, 5, false);
        self::assertSame('attempt_budget_exhausted_claim', $budget['status_code']);
        self::assertFalse($budget['candidate_matches']);

        $consumed = $engine->inspect($record, $code, self::KEY, self::BINDING, 'email', 2100, 0, true);
        self::assertSame('already_consumed_claim', $consumed['status_code']);
        self::assertFalse($consumed['candidate_matches']);
        self::assertFalse($consumed['atomic_replay_prevention_verified']);
    }

    public function test_record_deadline_and_nonce_cannot_be_tampered_without_fail_closed(): void
    {
        $engine = new OfflineSignupOtpChallenge();
        $issue = $engine->issue(self::KEY, self::BINDING, 'email', 1234);
        $record = $issue['challenge'];
        $code = $issue['delivery_code'];
        $mutated = $record;
        $mutated['expires_at']++;
        self::assertInvalid($engine, $mutated, $code);
        $mutated = $record;
        $mutated['issued_at']--;
        $mutated['expires_at']--; // Keep the 900s window; detect MAC tampering.
        self::assertSame(
            'candidate_mismatch',
            $engine->inspect($mutated, $code, self::KEY, self::BINDING, 'email', 1250, 0, false)['status_code'],
        );
        $mutated = $record;
        $mutated['nonce'] = str_repeat('0', 32);
        self::assertSame(
            'candidate_mismatch',
            $engine->inspect($mutated, $code, self::KEY, self::BINDING, 'email', 1250, 0, false)['status_code'],
        );
    }

    private static function assertInvalid(OfflineSignupOtpChallenge $engine, array $record, string $code): void
    {
        try {
            $engine->inspect($record, $code, self::KEY, self::BINDING, 'email', 1250, 0, false);
            self::fail('Tampered verifier unexpectedly accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Desafio OTP local invalido.', $e->getMessage());
        }
    }

    public function test_invalid_parameters_never_echo_secrets_or_input_code(): void
    {
        $engine = new OfflineSignupOtpChallenge();
        foreach ([
            ['short-key', self::BINDING, 'email', 1],
            [self::KEY, 'person@example.com', 'email', 1],
            [self::KEY, self::BINDING, 'whatsapp', 1],
            [self::KEY, self::BINDING, 'email', -1],
            [self::KEY, self::BINDING, 'email', PHP_INT_MAX],
        ] as $args) {
            try {
                $engine->issue(...$args);
                self::fail('Invalid OTP issue should fail.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Desafio OTP local invalido.', $e->getMessage());
                self::assertStringNotContainsString('person@example.com', $e->getMessage());
            }
        }

        $issued = $engine->issue(self::KEY, self::BINDING, 'email', 5000);
        foreach ([['00000', 0, false], ['00000a', 0, false], ['999999', -1, false], ['999999', 6, false], ['123456', 0, 1]] as [$code,$attempts,$consumed]) {
            try {
                $engine->inspect($issued['challenge'], $code, self::KEY, self::BINDING, 'email', 5001, $attempts, $consumed);
                self::fail('Invalid OTP inspection should fail.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Desafio OTP local invalido.', $e->getMessage());
            }
        }
    }
}
