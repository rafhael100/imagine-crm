<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-244: public-safe, offline-only observations for OTP screens.
 *
 * No user lookup, eligibility check, message transport, rate-limit,
 * code verification, account activation, external response or telemetry.
 *
 * Internal outcomes must not reveal which contacts exist in the CRM, whether
 * a code matched, or which anti-abuse scope was exhausted.
 */
final class OfflineSignupOtpPublicFeedbackPreview
{
    private const ISSUE_REASONS = [
        'sample_within_illustrative_budget',
        'sample_contact_budget_exhausted',
        'sample_origin_budget_exhausted',
        'sample_cooldown_active',
    ];

    private const VERIFY_REASONS = [
        'candidate_matches_only',
        'candidate_mismatch',
        'before_issue',
        'expired',
        'already_consumed_claim',
        'attempt_budget_exhausted_claim',
    ];

    /**
     * @param mixed $phase Known local UI action: issue or verify
     * @param mixed $internalReason Untrusted internal-only status
     * @return array<string,bool|string> Uniform result for each phase
     */
    public function preview(
        mixed $phase,
        #[\SensitiveParameter] mixed $internalReason,
    ): array {
        if (!is_string($phase) || !is_string($internalReason)
            || !in_array($phase, ['issue', 'verify'], true)
            || !in_array($internalReason, $phase === 'issue'
                ? self::ISSUE_REASONS : self::VERIFY_REASONS, true)) {
            throw new InvalidArgumentException('Mensagem local OTP invalida.');
        }

        $message = $phase === 'issue'
            ? 'Demonstração Imagine CRM: nenhum código foi enviado e nenhuma conta foi criada.'
            : 'Demonstração Imagine CRM: nenhum código foi validado e nenhum acesso foi ativado.';

        return [
            'phase' => $phase,
            'public_status_code' => 'demo_only_no_action',
            'public_message' => $message,
            'internal_reason_hidden' => true,
            'contact_existence_known' => false,
            'identity_verified' => false,
            'informational_only' => true,
            'can_send' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_grant_access' => false,
            'can_persist' => false,
        ];
    }
}
