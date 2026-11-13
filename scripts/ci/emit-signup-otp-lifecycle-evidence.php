<?php

declare(strict_types=1);

/**
 * F3-247: complete, test-only signup OTP lifecycle evidence.
 * Works only with Composer dev-autoload tests\Support harness and fixtures.
 * Never pass customer data, arguments, environment secrets or external input.
 */
use App\Core\Identity\OfflineCnpjChecksumInspector;
use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Core\Plans\TrialWindowAdvisory;
use Tests\Support\SignupOtpLifecycleSandbox;

if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1) {
    fwrite(STDERR, "F3-247 aceita somente a execucao local sem argumentos.\n");
    exit(2);
}

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $time = 1700000000;
    $contactKey = 'fictional-contact-binding-hmac-key-for-otp-only';
    $originKey = 'fictional-origin-binding-hmac-key-for-otp-only';
    $otpKey = 'fictional-otp-core-cryptographic-key-over-32-bytes';
    $contact = new OfflineSignupContactHmacBinding();
    $origin = static fn (string $label): string =>
        hash_hmac('sha256', 'synthetic-origin:'.$label, $originKey);
    $email = $contact->derive('signup.fixture@example.test', 'email', $contactKey);
    $secondEmail = $contact->derive('signup.other@example.test', 'email', $contactKey);
    $phone = $contact->derive('+5511999988888', 'phone', $contactKey);
    $originA = $origin('a');

    // Real OTP Core, state isolated in this single PHP instance only.
    $lifecycle = new SignupOtpLifecycleSandbox($otpKey);
    $original = $lifecycle->issue($email, $originA, 'email', $time);
    $cooldown = $lifecycle->issue($email, $originA, 'email', $time + 59);
    $resend = $lifecycle->issue($email, $originA, 'email', $time + 60);

    // Six-digit values can collide across two independent generations.
    // Only assert that an earlier *different* value is rejected.
    $olderDifferentRejected = true;
    if ($original['delivery_code'] !== $resend['delivery_code']) {
        $old = $lifecycle->verify(
            $email, $originA, 'email', $original['delivery_code'], $time + 61,
        );
        $olderDifferentRejected = $old['status'] === 'sandbox_wrong_code';
    }
    $matched = $lifecycle->verify(
        $email, $originA, 'email', $resend['delivery_code'], $time + 62,
    );
    $replayed = $lifecycle->verify(
        $email, $originA, 'email', $resend['delivery_code'], $time + 63,
    );
    $otherContact = $lifecycle->verify(
        $secondEmail, $originA, 'email', $resend['delivery_code'], $time + 64,
    );
    $otherChannel = $lifecycle->verify(
        $phone, $originA, 'phone', $resend['delivery_code'], $time + 65,
    );

    // Separate lab instance: retries exhausted after 5 wrong candidates.
    $attemptsLab = new SignupOtpLifecycleSandbox($otpKey);
    $attempted = $attemptsLab->issue($email, $origin('attempts'), 'email', $time);
    $badCode = $attempted['delivery_code'] === '000000' ? '000001' : '000000';
    $fiveWereRejected = true;
    for ($attempt = 1; $attempt <= OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS; $attempt++) {
        $view = $attemptsLab->verify(
            $email, $origin('attempts'), 'email', $badCode, $time + $attempt,
        );
        $fiveWereRejected = $fiveWereRejected && $view['status'] === 'sandbox_wrong_code';
    }
    $overBudget = $attemptsLab->verify(
        $email, $origin('attempts'), 'email', $attempted['delivery_code'], $time + 6,
    );

    // Exact 900-second expiry without extending the original challenge.
    $expiryLab = new SignupOtpLifecycleSandbox($otpKey);
    $expiring = $expiryLab->issue($phone, $origin('expiry'), 'phone', $time);
    $expired = $expiryLab->verify(
        $phone, $origin('expiry'), 'phone',
        $expiring['delivery_code'], $time + OfflineSignupOtpChallenge::VALIDITY_SECONDS,
    );

    // One origin with 20 unique, fictional contacts is capped in this process.
    $groupLab = new SignupOtpLifecycleSandbox($otpKey);
    $twentyIssuesCreated = true;
    for ($i = 0; $i < 20; $i++) {
        $scoped = $contact->derive('sandbox'.$i.'@example.test', 'email', $contactKey);
        $issued = $groupLab->issue($scoped, $origin('shared'), 'email', $time + $i);
        $twentyIssuesCreated = $twentyIssuesCreated
            && $issued['status'] === 'sandbox_code_created';
    }
    $overOrigin = $groupLab->issue(
        $contact->derive('sandbox20@example.test', 'email', $contactKey),
        $origin('shared'), 'email', $time + 21,
    );

    // The trial window and CNPJ checksum are display-only and NOT authority.
    $trial = new TrialWindowAdvisory($time, $time + 120);
    $cnpj = (new OfflineCnpjChecksumInspector())->inspect('12.ABC.345/01DE-35');
    $freshProcess = new SignupOtpLifecycleSandbox($otpKey);
    $freshCannotVerify = $freshProcess->verify(
        $email, $originA, 'email', $resend['delivery_code'], $time + 66,
    );

    $report = [
        'protocol' => 'imagine.crm.signup_otp_lifecycle_sandbox',
        'schema_version' => 1,
        'status' => 'REVIEW_ONLY_DENIED',
        'core_otp_digits' => OfflineSignupOtpChallenge::CODE_DIGITS,
        'core_expiration_seconds' => OfflineSignupOtpChallenge::VALIDITY_SECONDS,
        'first_issue_status' => $original['status'],
        'cooldown_status' => $cooldown['status'],
        'resend_status' => $resend['status'],
        'different_old_code_rejected_if_applicable' => $olderDifferentRejected,
        'new_code_verified_in_memory' => $matched['status'] === 'sandbox_match_consumed_in_memory',
        'replay_status' => $replayed['status'],
        'cross_contact_status' => $otherContact['status'],
        'cross_channel_status' => $otherChannel['status'],
        'five_bad_attempts_rejected' => $fiveWereRejected,
        'sixth_attempt_status' => $overBudget['status'],
        'expiry_boundary_status' => $expired['status'],
        'twenty_synthetic_issues_created' => $twentyIssuesCreated,
        'twenty_first_origin_issue_status' => $overOrigin['status'],
        'fresh_process_status' => $freshCannotVerify['status'],
        'illustrative_trial_days' => TrialWindowAdvisory::WINDOW_DAYS,
        'trial_phase' => $trial->phase()->value,
        'cnpj_format_status' => $cnpj['status_code'],
        'atomic_replay_prevention_verified' => false,
        'durable_otp_budget_verified' => false,
        'trusted_latest_challenge_verified' => false,
        'contact_ownership_verified' => false,
        'transport_provider_verified' => false,
        'one_trial_per_company_verified' => false,
        'tenant_membership_verified' => false,
        'independent_security_review_verified' => false,
        'can_send' => false,
        'can_register' => false,
        'can_authenticate' => false,
        'can_grant_access' => false,
        'can_persist' => false,
    ];

    if ($report['first_issue_status'] !== 'sandbox_code_created'
        || $report['cooldown_status'] !== 'sandbox_resend_cooldown'
        || $report['resend_status'] !== 'sandbox_code_created'
        || $report['different_old_code_rejected_if_applicable'] !== true
        || $report['new_code_verified_in_memory'] !== true
        || $report['replay_status'] !== 'sandbox_replay_denied'
        || $report['cross_contact_status'] !== 'sandbox_no_challenge'
        || $report['cross_channel_status'] !== 'sandbox_no_challenge'
        || $report['five_bad_attempts_rejected'] !== true
        || $report['sixth_attempt_status'] !== 'sandbox_attempt_limit'
        || $report['expiry_boundary_status'] !== 'sandbox_expired'
        || $report['twenty_synthetic_issues_created'] !== true
        || $report['twenty_first_origin_issue_status'] !== 'sandbox_origin_budget_exhausted'
        || $report['fresh_process_status'] !== 'sandbox_no_challenge'
        || $report['illustrative_trial_days'] !== 14
        || $report['trial_phase'] !== 'within_window'
        || $report['cnpj_format_status'] !== 'structurally_valid_only') {
        throw new LogicException('Sandbox evidence mismatch.');
    }

    // Zero secret, digest, email address, code or phone in the one-line JSON.
    $json = json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    if (strlen($json) > 4096) {
        throw new LogicException('Sandbox evidence too large.');
    }
    fwrite(STDOUT, $json."\n");
} catch (Throwable) {
    fwrite(STDERR, "F3-247 demonstracao ficticia invalida.\n");
    exit(1);
}
