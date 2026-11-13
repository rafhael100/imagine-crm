<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineSignupOtpChallenge;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineSignupContactHmacBindingTest extends TestCase
{
    private const KEY = 'fictional-contact-binding-only-key-32-bytes-plus';
    private const OTHER_KEY = 'different-fictional-contact-binding-only-key-32-plus';
    private const OTP_KEY = 'fictional-otp-key-only-for-negative-code-binding-proof';

    public function test_email_domain_case_normalizes_but_local_part_and_plus_tag_do_not(): void
    {
        $engine = new OfflineSignupContactHmacBinding();
        $a = $engine->derive('Alice+trial@Example.COM', 'email', self::KEY);
        $b = $engine->derive('Alice+trial@example.com', 'email', self::KEY);
        $c = $engine->derive('alice+trial@example.com', 'email', self::KEY);
        $d = $engine->derive('Alice@example.com', 'email', self::KEY);
        self::assertSame($a, $b);
        self::assertNotSame($a, $c);
        self::assertNotSame($a, $d);
        self::assertNotSame($a, $engine->derive('Alice+trial@example.com', 'email', self::OTHER_KEY));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $a);
        self::assertStringNotContainsString('Alice', $a);
    }

    public function test_only_exact_e164_phone_syntax_is_accepted_without_country_guessing(): void
    {
        $engine = new OfflineSignupContactHmacBinding();
        $a = $engine->derive('+5511999988888', 'phone', self::KEY);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $a);
        self::assertSame($a, $engine->derive('+5511999988888', 'phone', self::KEY));
        self::assertNotSame($a, $engine->derive('+5511999988889', 'phone', self::KEY));
        self::assertStringNotContainsString('5511999988888', $a);
    }

    public function test_otp_code_for_email_cannot_match_another_contact_binding(): void
    {
        $binder = new OfflineSignupContactHmacBinding();
        $otp = new OfflineSignupOtpChallenge();
        $alice = $binder->derive('alice@example.com', 'email', self::KEY);
        $bob = $binder->derive('bob@example.com', 'email', self::KEY);
        $issue = $otp->issue(self::OTP_KEY, $alice, 'email', 150000);
        $challenge = $issue['challenge'];
        $code = $issue['delivery_code'];

        $correct = $otp->inspect($challenge, $code, self::OTP_KEY, $alice, 'email', 150100, 0, false);
        $wrongContact = $otp->inspect($challenge, $code, self::OTP_KEY, $bob, 'email', 150100, 0, false);
        $wrongChannel = $otp->inspect($challenge, $code, self::OTP_KEY, $alice, 'phone', 150100, 0, false);

        self::assertTrue($correct['candidate_matches']);
        self::assertFalse($correct['can_register']);
        self::assertSame('candidate_mismatch', $wrongContact['status_code']);
        self::assertSame('candidate_mismatch', $wrongChannel['status_code']);
        self::assertFalse($wrongContact['can_authenticate']);
        self::assertFalse($wrongChannel['can_send']);
    }

    public function test_invalid_contact_or_key_fails_closed_without_reflecting_untrusted_values(): void
    {
        $engine = new OfflineSignupContactHmacBinding();
        foreach ([
            ['alice@@example.com', 'email', self::KEY],
            [' alice@example.com', 'email', self::KEY],
            ["alice@example.com\r\nBcc:secret@example.org", 'email', self::KEY],
            ['a..b@example.com', 'email', self::KEY],
            ['alíce@example.com', 'email', self::KEY],
            ['user@localhost', 'email', self::KEY],
            ['user@exámple.com', 'email', self::KEY],
            ['+55 (11) 99999-8888', 'phone', self::KEY],
            ['5511999988888', 'phone', self::KEY],
            ['+011234567890', 'phone', self::KEY],
            ['+5511', 'phone', self::KEY],
            ['+1234567890123456', 'phone', self::KEY],
            ['+5511999988888', 'whatsapp', self::KEY],
            ['alice@example.com', 'email', 'short'],
            [null, 'email', self::KEY],
            [str_repeat('A', 300).'@example.com', 'email', self::KEY],
        ] as $args) {
            try {
                $engine->derive(...$args);
                self::fail('Invalid contact binding unexpectedly succeeded.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Vinculo local de contato invalido.', $e->getMessage());
                self::assertStringNotContainsString('alice', $e->getMessage());
                self::assertStringNotContainsString('secret', $e->getMessage());
            }
        }
    }

    public function test_binding_is_not_transport_ownership_consent_or_user_identity(): void
    {
        $engine = new OfflineSignupContactHmacBinding();
        foreach (['deliver', 'send', 'register', 'authorize', 'verifyOwner', 'persist', 'authenticate'] as $method) {
            self::assertFalse(method_exists($engine, $method));
        }
        $digest = $engine->derive('fictional@example.com', 'email', self::KEY);
        self::assertNotSame(hash('sha256', 'fictional@example.com'), $digest);
        self::assertNotSame(hash_hmac('sha256', 'fictional@example.com', self::KEY), $digest);
    }
}
