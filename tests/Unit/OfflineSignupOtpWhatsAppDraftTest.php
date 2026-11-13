<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Core\Identity\OfflineSignupOtpWhatsAppDraft;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineSignupOtpWhatsAppDraftTest extends TestCase
{
    public function test_synthetic_whatsapp_candidate_uses_fixed_six_digit_code_and_fifteen_minutes(): void
    {
        $draft = (new OfflineSignupOtpWhatsAppDraft())->compose('012345');
        self::assertSame('whatsapp', $draft['channel']);
        self::assertSame('pt_BR', $draft['language']);
        self::assertSame('012345', $draft['synthetic_parameter_code']);
        self::assertSame(OfflineSignupOtpChallenge::CODE_DIGITS, $draft['code_digits']);
        self::assertSame(6, $draft['code_digits']);
        self::assertSame(900, $draft['validity_seconds']);
        self::assertStringContainsString('15 minutos', $draft['synthetic_preview']);
        self::assertStringContainsString('012345', $draft['synthetic_preview']);
        self::assertStringContainsString('{{1}}', $draft['utility_template_candidate']);
        self::assertStringNotContainsString('012345', $draft['utility_template_candidate']);
        self::assertStringNotContainsString('http://', $draft['synthetic_preview']);
        self::assertStringNotContainsString('https://', $draft['synthetic_preview']);
        self::assertStringNotContainsString('whatsapp.com', $draft['synthetic_preview']);
        self::assertLessThanOrEqual(400, strlen($draft['synthetic_preview']));

        foreach ([
            'provider_configured', 'template_submitted_to_provider',
            'template_approved_by_provider', 'recipient_binding_verified',
            'consent_and_lgpd_verified', 'antifraud_and_rate_limit_verified',
            'can_send', 'can_queue', 'can_register', 'can_authenticate', 'can_persist',
        ] as $flag) {
            self::assertFalse($draft[$flag], $flag);
        }
        self::assertTrue($draft['informational_only']);
    }

    public function test_six_digit_zeroes_are_not_trimmed_or_converted_to_integer(): void
    {
        $draft = (new OfflineSignupOtpWhatsAppDraft())->compose('000000');
        self::assertSame('000000', $draft['synthetic_parameter_code']);
        self::assertStringContainsString('000000', $draft['synthetic_preview']);
    }

    public function test_untrusted_payload_never_reaches_a_message_preview(): void
    {
        $composer = new OfflineSignupOtpWhatsAppDraft();
        foreach ([
            null, '', 123456, '12345', '1234567', '12345x', ' 123456',
            "123456\r\nBcc:evil@example.com", '<script>alert(1)</script>',
            "\u{FF11}23456",
        ] as $candidate) {
            try {
                $composer->compose($candidate);
                self::fail('Untrusted payload unexpectedly rendered.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Codigo de mensagem OTP invalido.', $e->getMessage());
                if (is_string($candidate) && $candidate !== '') {
                    self::assertStringNotContainsString($candidate, $e->getMessage());
                }
            }
        }
    }

    public function test_template_never_exposes_provider_transport_or_account_authority(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Core/Identity/OfflineSignupOtpWhatsAppDraft.php',
        );
        self::assertIsString($source);
        foreach ([
            'Http::', 'Mail::', 'curl_', 'file_put_contents(',
            'DB::', 'env(', 'getenv(', 'https://', 'http://',
            'recipient_phone', 'access_token', 'Authorization: Bearer',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
