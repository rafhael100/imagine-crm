<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-238 — offline-only candidate WhatsApp utility-message draft.
 *
 * No message provider, WhatsApp API, template approval, recipient, HTTP,
 * tenant, consent, phone ownership, retries or persistence exists here.
 * The untrusted code is returned IN MEMORY ONLY for test fixture rendering.
 */
final class OfflineSignupOtpWhatsAppDraft
{
    private const BODY =
        'Imagine CRM: seu codigo de verificacao e {{1}}. '.
        'Valido por 15 minutos. Nao compartilhe este codigo. '.
        'Se voce nao solicitou, ignore a mensagem.';

    /**
     * @param mixed $code Only an ephemeral six-digit test fixture
     * @return array<string,mixed> Internal rendering, NEVER for logs or HTTP
     */
    public function compose(#[\SensitiveParameter] mixed $code): array
    {
        if (!is_string($code) || preg_match('/\A[0-9]{6}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Codigo de mensagem OTP invalido.');
        }

        $minutes = intdiv(OfflineSignupOtpChallenge::VALIDITY_SECONDS, 60);
        $template = str_replace('15 minutos', $minutes.' minutos', self::BODY);

        return [
            'channel' => 'whatsapp',
            'language' => 'pt_BR',
            'utility_template_candidate' => $template,
            'synthetic_parameter_code' => $code,
            'synthetic_preview' => str_replace('{{1}}', $code, $template),
            'code_digits' => OfflineSignupOtpChallenge::CODE_DIGITS,
            'validity_seconds' => OfflineSignupOtpChallenge::VALIDITY_SECONDS,
            'provider_configured' => false,
            'template_submitted_to_provider' => false,
            'template_approved_by_provider' => false,
            'recipient_binding_verified' => false,
            'consent_and_lgpd_verified' => false,
            'antifraud_and_rate_limit_verified' => false,
            'informational_only' => true,
            'can_send' => false,
            'can_queue' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_persist' => false,
        ];
    }
}
