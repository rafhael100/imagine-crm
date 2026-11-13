<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupOtpEmailTransportPreflight;
use PHPUnit\Framework\TestCase;

final class OfflineSignupOtpEmailTransportPreflightTest extends TestCase
{
    private static function mailer(): array
    {
        return [
            'transport' => 'smtp', 'scheme' => 'smtps',
            'host' => 'smtp.mail.example.org', 'port' => 465,
            'username' => 'synthetic-account@example.org',
            'password' => 'fictional-password-at-least-sixteen-characters',
            'timeout' => 10,
        ];
    }

    private static function sender(): array
    {
        return ['address' => 'noreply@example.org', 'name' => 'Imagine CRM'];
    }

    public function test_even_candidate_smtp_never_authorizes_a_real_message(): void
    {
        $result = (new OfflineSignupOtpEmailTransportPreflight())->inspect(self::mailer(), self::sender());
        self::assertSame('offline_candidate_only', $result['reason_code']);
        self::assertTrue($result['offline_configuration_candidate']);
        self::assertTrue($result['informational_only']);
        foreach ([
            'live_smtp_delivery_verified', 'tls_peer_verified',
            'dns_and_sender_verified', 'spf_dkim_dmarc_verified',
            'recipient_control_verified', 'rate_limit_verified',
            'lgpd_verified', 'independent_review_verified',
            'can_send', 'can_queue', 'can_log_code',
            'can_register', 'can_authenticate',
        ] as $flag) {
            self::assertFalse($result[$flag], $flag);
        }
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['example.org', 'fictional-password', 'synthetic-account', 'noreply'] as $secret) {
            self::assertStringNotContainsString($secret, $encoded);
        }
    }

    public function test_log_array_failover_and_ambiguous_transports_are_denied(): void
    {
        $engine = new OfflineSignupOtpEmailTransportPreflight();
        foreach ([
            ['transport' => 'log'],
            ['transport' => 'array'],
            ['transport' => 'sendmail'],
            ['transport' => 'failover', 'mailers' => ['smtp', 'log']],
            ['transport' => 'roundrobin', 'mailers' => ['smtp', 'log']],
            ['transport' => 'smtp', 'mailers' => ['smtp', 'log']],
            ['transport' => 'smtp', 'url' => 'smtp://untrusted.invalid'],
            ['transport' => 'smtp', 'stream' => ['verify_peer' => false]],
            ['transport' => 'smtp', 'verify_peer' => false],
            ['transport' => 'smtp', 'allow_self_signed' => true],
        ] as $changes) {
            $result = $engine->inspect(array_replace(self::mailer(), $changes), self::sender());
            self::assertSame('unsafe_or_ambiguous_transport', $result['reason_code']);
            self::assertFalse($result['can_send']);
        }
    }

    public function test_unknown_tls_and_delivery_settings_can_never_be_ignored(): void
    {
        $engine = new OfflineSignupOtpEmailTransportPreflight();
        foreach ([
            ['verify_peer_name' => false],
            ['auto_tls' => false],
            ['tls_required' => false],
            ['encryption' => null],
            ['verify_peer' => true],
            ['allow_self_signed' => false],
            ['options' => ['ssl' => ['verify_peer' => false]]],
            ['failover' => null],
            ['from' => 'attacker@example.org'],
            ['bcc' => 'attacker@example.org'],
            ['host_override' => 'attacker.example.org'],
            [0 => 'unrecognized-option'],
        ] as $additional) {
            $view = $engine->inspect(array_replace(self::mailer(), $additional), self::sender());
            self::assertSame('unsafe_or_ambiguous_transport', $view['reason_code']);
            self::assertFalse($view['offline_configuration_candidate']);
            self::assertFalse($view['can_send']);
        }
        foreach ([
            ['extra' => 'changed-header'],
            ['reply_to' => 'attacker@example.org'],
            [0 => 'unknown-sender-key'],
        ] as $additional) {
            $view = $engine->inspect(self::mailer(), array_replace(self::sender(), $additional));
            self::assertSame('invalid_sender', $view['reason_code']);
            self::assertFalse($view['can_send']);
        }
        $noHost = self::mailer();
        unset($noHost['host']);
        self::assertSame(
            'unsafe_or_ambiguous_transport',
            $engine->inspect($noHost, self::sender())['reason_code'],
        );
    }

    public function test_plaintext_tls_opportunistic_and_wrong_ports_are_denied(): void
    {
        $engine = new OfflineSignupOtpEmailTransportPreflight();
        foreach ([
            ['scheme' => 'smtp'], ['scheme' => 'tls'], ['scheme' => null],
            ['port' => 25], ['port' => 587], ['port' => 2525],
            ['port' => '465'],
        ] as $changes) {
            $result = $engine->inspect(array_replace(self::mailer(), $changes), self::sender());
            self::assertSame('tls_requirements_not_established', $result['reason_code']);
        }
    }

    public function test_host_credentials_and_timeout_fail_closed_without_leakage(): void
    {
        $engine = new OfflineSignupOtpEmailTransportPreflight();
        foreach ([
            ['host' => '127.0.0.1'], ['host' => 'localhost'],
            ['host' => 'smtp.internal'], ['host' => 'smtp.example.test'],
            ['host' => 'a..example.org'], ['host' => 'smtp.EXAMPLE.org'],
            ['host' => 'smtp.example.org:465'],
            ['host' => 'smtp.mail.example.org/Bcc:foo'],
            ['username' => ''], ['password' => 'weak'],
            ['username' => "account@example.org\r\nBcc:stolen@example.org"],
            ['username' => 'account with spaces'],
            ['password' => "malicious-password-value\r\nBcc:stolen@example.org"],
            ['timeout' => null], ['timeout' => 0], ['timeout' => '10'],
        ] as $changes) {
            $result = $engine->inspect(array_replace(self::mailer(), $changes), self::sender());
            self::assertSame('invalid_smtp_settings', $result['reason_code']);
            self::assertFalse($result['can_send']);
            self::assertStringNotContainsString('example.org', json_encode($result, JSON_THROW_ON_ERROR));
        }
    }

    public function test_invalid_sender_and_non_arrays_are_denied(): void
    {
        $engine = new OfflineSignupOtpEmailTransportPreflight();
        foreach ([
            ['address' => 'invalid'],
            ['address' => "person@example.org\r\nBcc:evil@example.org"],
            ['name' => ''],
            ['name' => "Injected\r\nHeader"],
        ] as $changes) {
            $result = $engine->inspect(self::mailer(), array_replace(self::sender(), $changes));
            self::assertSame('invalid_sender', $result['reason_code']);
        }
        foreach ([[null, self::sender()], [self::mailer(), 'untrusted'], [true, false]] as $pair) {
            $result = $engine->inspect(...$pair);
            self::assertSame('invalid_configuration', $result['reason_code']);
            self::assertFalse($result['can_send']);
        }
    }
}
