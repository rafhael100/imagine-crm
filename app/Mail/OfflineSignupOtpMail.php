<?php

declare(strict_types=1);

namespace App\Mail;

use App\Core\Identity\OfflineSignupOtpChallenge;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use InvalidArgumentException;

/**
 * F3-236 — delivery-ready email PRESENTATION, not delivery authorization.
 *
 * No routes, mailer dispatch, queueing, user lookup, contact ownership check,
 * session/tenant binding, audit or persistent challenge handling are wired.
 *
 * Never log this Mailable or serialize it to a real queue before an approved
 * secure transport and retention design. Tests use only Mail::fake().
 */
final class OfflineSignupOtpMail extends Mailable
{
    private readonly string $otpCode;

    public function __construct(#[\SensitiveParameter] string $otpCode)
    {
        if (preg_match('/\A[0-9]{6}\z/D', $otpCode) !== 1) {
            throw new InvalidArgumentException('Codigo OTP de e-mail invalido.');
        }
        $this->otpCode = $otpCode;
    }

    public function envelope(): Envelope
    {
        // Never embed the OTP itself, a destination, CNPJ or a token in a
        // subject or message header.
        return new Envelope(subject: 'Imagine CRM | Seu codigo de verificacao');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.crm-signup-otp',
            text: 'emails.crm-signup-otp-text',
            with: [
                'otpCode' => $this->otpCode,
                'validityMinutes' => intdiv(OfflineSignupOtpChallenge::VALIDITY_SECONDS, 60),
            ],
        );
    }

    /** @return array<int,mixed> */
    public function attachments(): array
    {
        return [];
    }
}
