<?php

declare(strict_types=1);

/**
 * F3-250: fully synthetic, process-only OTP-to-trial sequence.
 * No account, tenant, actual quota, payment, mailer or PostgreSQL is touched.
 */
use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Core\Plans\TrialWindowAdvisory;
use Tests\Support\SignupOtpLifecycleSandbox;
use Tests\Support\TrialEnrollmentLifecycleSandbox;

if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1) {
    fwrite(STDERR, "F3-250 nao recebe parametros ou dados de clientes.\n");
    exit(2);
}
require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $t0 = 1700000000;
    $contactKey = 'synthetic-contact-binding-key-32-bytes-minimum';
    $originKey = 'synthetic-origin-binding-key-32-bytes-minimum';
    $otpKey = 'synthetic-otp-cryptographic-key-32-bytes-minimum';
    $cnpjKey = 'synthetic-cnpj-trial-hmac-key-32-bytes-minimum';

    $email = (new OfflineSignupContactHmacBinding())->derive(
        'demo.signup@example.test', 'email', $contactKey,
    );
    $origin = hash_hmac('sha256', 'demo-origin-never-an-ip', $originKey);
    $otpLab = new SignupOtpLifecycleSandbox($otpKey);
    $issued = $otpLab->issue($email, $origin, 'email', $t0);
    $verified = $otpLab->verify(
        $email, $origin, 'email', $issued['delivery_code'], $t0 + 1,
    );
    $replay = $otpLab->verify(
        $email, $origin, 'email', $issued['delivery_code'], $t0 + 2,
    );

    // This next action is a SECOND INDEPENDENT SANDBOX. The simulated OTP
    // success above is not a credential or authorization to reserve a trial.
    $trialLab = new TrialEnrollmentLifecycleSandbox($cnpjKey);
    $marker = $trialLab->observeFirstUse('12.ABC.345/01DE-35', $t0 + 3);
    $sameCompany = $trialLab->observeFirstUse('12abc34501de35', $t0 + 4);
    $during = $trialLab->describe('12.ABC.345/01DE-35', $t0 + 5);
    $after = $trialLab->describe(
        '12.ABC.345/01DE-35', $t0 + 3 + TrialWindowAdvisory::WINDOW_DAYS * 86400,
    );
    $anotherCompany = $trialLab->observeFirstUse(
        '11.222.333/0001-81', $t0 + 6,
    );

    $limits = [];
    foreach ($during['quota_meters'] as $card) {
        $limits[$card['meter']] = $card['illustrative_limit'];
    }
    ksort($limits, SORT_STRING);
    $report = [
        'protocol' => 'imagine.crm.synthetic_otp_trial_functional_lab',
        'schema_version' => 1,
        'status' => 'REVIEW_ONLY_DENIED',
        'otp_digits' => OfflineSignupOtpChallenge::CODE_DIGITS,
        'otp_validity_seconds' => OfflineSignupOtpChallenge::VALIDITY_SECONDS,
        'otp_issue_status' => $issued['status'],
        'otp_match_status' => $verified['status'],
        'otp_replay_status' => $replay['status'],
        'trial_first_marker_status' => $marker['status'],
        'same_company_case_insensitive_duplicate_status' => $sameCompany['status'],
        'other_company_status' => $anotherCompany['status'],
        'trial_days_illustrative' => TrialWindowAdvisory::WINDOW_DAYS,
        'trial_during_phase' => $during['trial_phase'],
        'trial_after_exact_boundary_phase' => $after['trial_phase'],
        'trial_days_remaining_after_boundary' => $after['remaining_days_rounded_up'],
        'trial_quota_illustrative_limits' => $limits,
        'otp_in_memory_is_not_identity' => true,
        'one_trial_per_cnpj_not_durably_verified' => true,
        'transport_verified' => false,
        'registered_company_verified' => false,
        'company_owner_verified' => false,
        'tenant_authorization_verified' => false,
        'atomic_trial_reservation_verified' => false,
        'atomic_otp_consume_verified' => false,
        'consent_lgpd_verified' => false,
        'can_send' => false,
        'can_create_company' => false,
        'can_reserve_trial' => false,
        'can_register' => false,
        'can_authenticate' => false,
        'can_allocate_quota' => false,
        'can_charge' => false,
        'can_persist' => false,
    ];

    if ($report['otp_issue_status'] !== 'sandbox_code_created'
        || $report['otp_match_status'] !== 'sandbox_match_consumed_in_memory'
        || $report['otp_replay_status'] !== 'sandbox_replay_denied'
        || $report['trial_first_marker_status'] !== 'sandbox_first_trial_marker'
        || $report['same_company_case_insensitive_duplicate_status'] !== 'sandbox_duplicate_trial_claim'
        || $report['other_company_status'] !== 'sandbox_first_trial_marker'
        || $report['trial_during_phase'] !== 'within_window'
        || $report['trial_after_exact_boundary_phase'] !== 'after_window'
        || $report['trial_days_remaining_after_boundary'] !== 0
        || count($limits) !== 7
        || $report['otp_in_memory_is_not_identity'] !== true
        || $report['can_register'] !== false) {
        throw new LogicException('Functional lab evidence inconsistent.');
    }

    $json = json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    if (strlen($json) > 4096) {
        throw new LogicException('Functional lab report too large.');
    }
    fwrite(STDOUT, $json."\n");
} catch (Throwable) {
    fwrite(STDERR, "F3-250 laboratorio de cadastro e trial invalido.\n");
    exit(1);
}
