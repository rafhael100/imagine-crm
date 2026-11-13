<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Mail\OfflineSignupOtpMail;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

final class OfflineSignupOtpMailTest extends TestCase
{
    public function test_mail_has_approved_imagine_design_fixed_expiry_and_plain_text_fallback(): void
    {
        $mail = new OfflineSignupOtpMail('012345');
        self::assertSame('Imagine CRM | Seu codigo de verificacao', $mail->envelope()->subject);
        self::assertSame([], $mail->attachments());

        $content = $mail->content();
        self::assertSame('emails.crm-signup-otp', $content->view);
        self::assertSame('emails.crm-signup-otp-text', $content->text);
        self::assertSame('012345', $content->with['otpCode']);
        self::assertSame(
            intdiv(OfflineSignupOtpChallenge::VALIDITY_SECONDS, 60),
            $content->with['validityMinutes'],
        );
        self::assertSame(15, $content->with['validityMinutes']);

        $html = $mail->render();
        self::assertStringContainsString('012345', $html);
        self::assertStringContainsString('15 minutos', $html);
        self::assertStringContainsString('#f9941d', $html);
        self::assertStringContainsString('#171b19', $html);
        self::assertStringContainsString('#f7f7f8', $html);
        self::assertStringContainsString('Se você não solicitou', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('http://', $html);
        self::assertStringNotContainsString('https://', $html);
        self::assertStringNotContainsString('href=', $html);
        self::assertStringNotContainsString('src=', $html);

        $plain = view('emails.crm-signup-otp-text', $content->with)->render();
        self::assertStringContainsString('012345', $plain);
        self::assertStringContainsString('15 minutos', $plain);
        self::assertStringNotContainsString('<html', $plain);
    }

    public function test_mail_facade_fake_only_captures_fictional_delivery_without_network_transport(): void
    {
        Mail::fake();
        $mail = new OfflineSignupOtpMail('654321');
        Mail::to('fictional-recipient@example.test')->send($mail);

        Mail::assertSent(OfflineSignupOtpMail::class, 1);
        Mail::assertSent(
            OfflineSignupOtpMail::class,
            static fn (OfflineSignupOtpMail $message): bool =>
                $message->hasTo('fictional-recipient@example.test')
                && $message->envelope()->subject === 'Imagine CRM | Seu codigo de verificacao'
                && $message->attachments() === [],
        );
        Mail::assertNothingQueued();
    }

    public function test_untrusted_payloads_can_never_be_interpreted_as_six_digit_code(): void
    {
        foreach ([
            '', '12345', '1234567', '12345a', '12 3456', ' 123456',
            "123456\nBcc:evil@example.com", '<script>alert(1)</script>',
            "\u{FF11}23456",
        ] as $candidate) {
            try {
                new OfflineSignupOtpMail($candidate);
                self::fail('Malformed OTP accepted by mail template.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Codigo OTP de e-mail invalido.', $e->getMessage());
                if ($candidate !== '') {
                    self::assertStringNotContainsString($candidate, $e->getMessage());
                }
            }
        }
    }

    public function test_template_and_mail_source_cannot_trigger_external_send_without_application_wiring(): void
    {
        $base = dirname(__DIR__, 2);
        $html = file_get_contents($base.'/resources/views/emails/crm-signup-otp.blade.php');
        $plain = file_get_contents($base.'/resources/views/emails/crm-signup-otp-text.blade.php');
        $class = file_get_contents($base.'/app/Mail/OfflineSignupOtpMail.php');
        self::assertIsString($html);
        self::assertIsString($plain);
        self::assertIsString($class);
        foreach (['Mail::send', 'Mail::to(', 'queue(', 'curl_', 'fetch(', 'http://', 'https://', 'file_put_contents(', 'DB::'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $class);
            self::assertStringNotContainsString($forbidden, $html);
            self::assertStringNotContainsString($forbidden, $plain);
        }
    }
}
