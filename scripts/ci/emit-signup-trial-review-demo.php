<?php

declare(strict_types=1);

/**
 * F3-229: real Core PHP in a fixed, fictional signup+trial lab.
 * One static JSON review to stdout. Never an operational registration route.
 */
use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Core\Identity\OfflineSignupOtpAbuseBudgetPreview;
use App\Core\Identity\OfflineSignupOtpPublicFeedbackPreview;
use App\Core\Identity\OfflineSignupOtpSupersessionPreview;
use App\Core\Identity\OfflineSignupOtpWhatsAppDraft;
use App\Mail\OfflineSignupOtpMail;
use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineCnpjChecksumInspector;
use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticTrialQuotaOverview;
use App\Core\Plans\TrialWindowAdvisory;

if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1) {
    fwrite(STDERR, "F3-229 aceita somente execucao de laboratorio sem argumentos.\n");
    exit(2);
}

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $key = 'fictional-signup-hmac-key-longer-than-thirty-two-bytes';
    $contact = new OfflineSignupContactHmacBinding();
    $contactKey = 'fictional-only-contact-hmac-binding-key-over-32-bytes';
    $emailBinding = $contact->derive('demo.one@example.com', 'email', $contactKey);
    $otherEmailBinding = $contact->derive('demo.two@example.com', 'email', $contactKey);
    $phoneBinding = $contact->derive('+5511999988888', 'phone', $contactKey);
    $startedAt = 1780000000;
    $checkedAt = $startedAt + 120;
    $challenge = new OfflineSignupOtpChallenge();

    $email = $challenge->issue($key, $emailBinding, 'email', $startedAt);
    $phone = $challenge->issue($key, $phoneBinding, 'phone', $startedAt);
    $emailView = $challenge->inspect(
        $email['challenge'], $email['delivery_code'],
        $key, $emailBinding, 'email', $checkedAt, 0, false,
    );
    $phoneView = $challenge->inspect(
        $phone['challenge'], $phone['delivery_code'],
        $key, $phoneBinding, 'phone', $checkedAt, 0, false,
    );
    $wrong = $phone['delivery_code'] === '000000' ? '000001' : '000000';
    $wrongPhoneView = $challenge->inspect(
        $phone['challenge'], $wrong, $key, $phoneBinding, 'phone', $checkedAt, 1, false,
    );
    $expiredPhoneView = $challenge->inspect(
        $phone['challenge'], $phone['delivery_code'],
        $key, $phoneBinding, 'phone', $startedAt + 900, 0, false,
    );

    $wrongEmailContactView = $challenge->inspect(
        $email['challenge'], $email['delivery_code'],
        $key, $otherEmailBinding, 'email', $checkedAt, 1, false,
    );
    $wrongEmailChannelView = $challenge->inspect(
        $email['challenge'], $email['delivery_code'],
        $key, $emailBinding, 'phone', $checkedAt, 1, false,
    );

    $trial = new TrialWindowAdvisory($startedAt, $checkedAt);
    $quotas = new QuotaSnapshotReadout([
        new QuotaUsageProjection('users', 2, 0, 0),
        new QuotaUsageProjection('leads', 20, 0, 0),
        new QuotaUsageProjection('automations', 2, 0, 0),
        new QuotaUsageProjection('pipelines', 2, 0, 0),
        new QuotaUsageProjection('attachments_bytes', 20 * 1024 * 1024, 0, 0),
        new QuotaUsageProjection('ai_initial_credits', 10, 0, 0),
        new QuotaUsageProjection('ai_bonus_credits', 10, 0, 0),
    ]);
    $overview = (new SyntheticTrialQuotaOverview($trial, $quotas))->readout();
    $meters = array_map(
        static fn (array $card): array => [
            'meter' => $card['meter'],
            'illustrative_limit' => $card['limit'],
            'illustrative_used' => $card['used'],
            'remaining' => $card['remaining'],
            'band' => $card['band']->value,
        ],
        $overview['quotas'],
    );

    // Fiscal syntax only: never query a registry or store a CNPJ.
    $cnpjCheck = new OfflineCnpjChecksumInspector();
    $numericCnpj = $cnpjCheck->inspect('11.222.333/0001-81');
    $alphanumericCnpj = $cnpjCheck->inspect('12.ABC.345/01DE-35');
    $cnpjKey = 'only-synthetic-cnpj-binding-hmac-secret-in-memory';
    $binder = new OfflineCnpjTrialHmacBinding();
    $one = $binder->derive('12.ABC.345/01DE-35', $cnpjKey);
    $same = $binder->derive('12abc34501de35', $cnpjKey);
    $other = $binder->derive('11.222.333/0001-81', $cnpjKey);

    // Only render in memory: no Laravel Mail facade, queue or provider.
    $emailModel = new OfflineSignupOtpMail($email['delivery_code']);
    $emailContent = $emailModel->content();
    $whatsModel = (new OfflineSignupOtpWhatsAppDraft())->compose($phone['delivery_code']);
    $emailMatches = hash_equals($email['delivery_code'], $emailContent->with['otpCode']);
    $whatsMatches = hash_equals($phone['delivery_code'], $whatsModel['synthetic_parameter_code']);
    $sameValidity = $emailContent->with['validityMinutes']
        === intdiv($whatsModel['validity_seconds'], 60)
        && $whatsModel['validity_seconds'] === OfflineSignupOtpChallenge::VALIDITY_SECONDS;
    $whatsNoTransport = $whatsModel['can_send'] === false
        && $whatsModel['template_approved_by_provider'] === false;

    // Budget claims are fixed synthetic snapshots, never durable enforcement.
    $abuse = new OfflineSignupOtpAbuseBudgetPreview();
    $originBinding = hash_hmac(
        'sha256', 'synthetic-origin-never-a-real-ip', $contactKey,
    );
    $fresh = ['window_start' => $startedAt, 'events' => 0, 'latest_at' => $startedAt];
    $limitedIssue = ['window_start' => $startedAt, 'events' => 3, 'latest_at' => $startedAt];
    $limitedOrigin = ['window_start' => $startedAt, 'events' => 20, 'latest_at' => $startedAt];
    $limitedVerify = [
        'window_start' => $startedAt,
        'events' => OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS,
        'latest_at' => $startedAt,
    ];
    $normalBudget = $abuse->inspect(
        'issue', $emailBinding, $originBinding, $checkedAt, $fresh, $fresh,
    );
    $contactBlocked = $abuse->inspect(
        'issue', $emailBinding, $originBinding, $checkedAt, $limitedIssue, $fresh,
    );
    $originBlocked = $abuse->inspect(
        'issue', $phoneBinding, $originBinding, $checkedAt, $fresh, $limitedOrigin,
    );
    $verifyBlocked = $abuse->inspect(
        'verify', $emailBinding, $originBinding, $checkedAt, $limitedVerify, $fresh,
    );

    // Uniform presentation for all simulated failures and successes.
    // The CLI never sends these messages to actual users or HTTP clients.
    $publicFeedback = new OfflineSignupOtpPublicFeedbackPreview();
    $issueNeutral = $publicFeedback->preview('issue', $normalBudget['reason_code']);
    $issueBlockedNeutral = $publicFeedback->preview('issue', $contactBlocked['reason_code']);
    $issueOriginNeutral = $publicFeedback->preview('issue', $originBlocked['reason_code']);
    $verifyNeutral = $publicFeedback->preview('verify', $emailView['status_code']);
    $verifyWrongNeutral = $publicFeedback->preview('verify', $wrongPhoneView['status_code']);
    $verifyExpiredNeutral = $publicFeedback->preview('verify', $expiredPhoneView['status_code']);
    $issueUniform = $issueNeutral === $issueBlockedNeutral
        && $issueBlockedNeutral === $issueOriginNeutral;
    $verifyUniform = $verifyNeutral === $verifyWrongNeutral
        && $verifyWrongNeutral === $verifyExpiredNeutral;

    // A resend creates a new independent verifier; older code must be
    // superseded by a future DURABLE per-contact latest-challenge pointer.
    // This demonstration has no such pointer and grants no rights.
    $resend = $challenge->issue($key, $emailBinding, 'email', $startedAt + 60);
    $supersession = new OfflineSignupOtpSupersessionPreview();
    $oldClaim = $supersession->inspect(
        $email['challenge'], $resend['challenge'], $checkedAt,
    );
    $newClaim = $supersession->inspect(
        $resend['challenge'], $resend['challenge'], $checkedAt,
    );

    $report = [
        'protocol' => 'imagine.crm.synthetic_signup_trial_review',
        'schema_version' => 1,
        'status' => 'REVIEW_ONLY_DENIED',
        'otp_code_digits' => OfflineSignupOtpChallenge::CODE_DIGITS,
        'otp_validity_seconds' => OfflineSignupOtpChallenge::VALIDITY_SECONDS,
        'resend_old_code_superseded_observation' => $oldClaim['reason_code'],
        'resend_new_code_latest_observation' => $newClaim['reason_code'],
        'atomic_resend_invalidation_verified' => false,
        'sample_issue_budget_observation' => $normalBudget['reason_code'],
        'sample_contact_limit_observation' => $contactBlocked['reason_code'],
        'sample_origin_limit_observation' => $originBlocked['reason_code'],
        'sample_verify_limit_observation' => $verifyBlocked['reason_code'],
        'durable_otp_abuse_enforcement_verified' => false,
        'uniform_public_issue_feedback' => $issueUniform,
        'uniform_public_verify_feedback' => $verifyUniform,
        'public_feedback_is_only_offline' => true,
        'email_template_otp_matches_in_memory' => $emailMatches,
        'whatsapp_template_otp_matches_in_memory' => $whatsMatches,
        'email_whatsapp_validity_parity' => $sameValidity,
        'whatsapp_provider_not_approved' => $whatsNoTransport,
        'email_candidate_observation' => $emailView['status_code'],
        'phone_candidate_observation' => $phoneView['status_code'],
        'wrong_phone_observation' => $wrongPhoneView['status_code'],
        'expired_phone_observation' => $expiredPhoneView['status_code'],
        'wrong_email_contact_observation' => $wrongEmailContactView['status_code'],
        'wrong_email_channel_observation' => $wrongEmailChannelView['status_code'],
        'distinct_channel_bindings_differed' => !hash_equals($emailBinding, $phoneBinding),
        'trial_phase' => $overview['trial']['phase'],
        'numeric_cnpj_check_status' => $numericCnpj['status_code'],
        'alphanumeric_cnpj_check_status' => $alphanumericCnpj['status_code'],
        'equivalent_cnpj_binding_matched' => hash_equals($one, $same),
        'distinct_cnpj_binding_differed' => !hash_equals($one, $other),
        'illustrative_trial_days' => TrialWindowAdvisory::WINDOW_DAYS,
        'trial_remaining_days_rounded_up' => $overview['trial']['remaining_days_rounded_up'],
        'illustrative_quota_meters' => $meters,
        'one_trial_per_company_verified' => false,
        'identity_and_contact_binding_verified' => false,
        'atomic_otp_consume_verified' => false,
        'tenant_and_membership_verified' => false,
        'consent_and_lgpd_verified' => false,
        'notification_transport_verified' => false,
        'plan_and_quota_authority_verified' => false,
        'authorized' => false,
        'can_register' => false,
        'can_authenticate' => false,
        'can_send_code' => false,
        'can_charge' => false,
        'can_allocate_quota' => false,
        'can_persist' => false,
    ];

    if ($emailView['candidate_matches'] !== true
        || $phoneView['candidate_matches'] !== true
        || $wrongPhoneView['candidate_matches'] !== false
        || $expiredPhoneView['candidate_matches'] !== false
        || $wrongEmailContactView['candidate_matches'] !== false
        || $wrongEmailChannelView['candidate_matches'] !== false
        || hash_equals($emailBinding, $phoneBinding)
        || $overview['trial']['phase'] !== 'within_window'
        || $numericCnpj['status_code'] !== 'structurally_valid_only'
        || $alphanumericCnpj['status_code'] !== 'structurally_valid_only'
        || !hash_equals($one, $same) || hash_equals($one, $other)
        || !$emailMatches || !$whatsMatches || !$sameValidity || !$whatsNoTransport
        || $normalBudget['reason_code'] !== 'sample_within_illustrative_budget'
        || $contactBlocked['reason_code'] !== 'sample_contact_budget_exhausted'
        || $originBlocked['reason_code'] !== 'sample_origin_budget_exhausted'
        || $verifyBlocked['reason_code'] !== 'sample_contact_budget_exhausted'
        || $oldClaim['reason_code'] !== 'earlier_challenge_superseded_claim'
        || $newClaim['reason_code'] !== 'latest_challenge_claim_only'
        || $oldClaim['can_consume'] !== false
        || $newClaim['latest_pointer_durable_verified'] !== false
        || !$issueUniform || !$verifyUniform
        || $issueNeutral['can_send'] !== false
        || $verifyNeutral['can_register'] !== false
        || $normalBudget['can_send'] !== false
        || $contactBlocked['can_register'] !== false
        || count($meters) !== 7
        || $overview['authorized'] !== false) {
        throw new \LogicException('Synthetic evidence inconsistent.');
    }

    $wire = json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    if (strlen($wire) > 4096) {
        throw new \LogicException('Synthetic report over budget.');
    }
    fwrite(STDOUT, $wire."\n");
} catch (\Throwable) {
    fwrite(STDERR, "F3-229 revisao de cadastro ficticio invalida.\n");
    exit(1);
}
