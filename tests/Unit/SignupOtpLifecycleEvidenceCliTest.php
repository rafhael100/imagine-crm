<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class SignupOtpLifecycleEvidenceCliTest extends TestCase
{
    private static function script(): string
    {
        return dirname(__DIR__, 2).'/scripts/ci/emit-signup-otp-lifecycle-evidence.php';
    }

    public function test_script_exercises_real_core_and_emits_reproducible_sanitized_evidence(): void
    {
        $first = new Process([PHP_BINARY, self::script()]);
        $first->setTimeout(30);
        $first->run();

        self::assertTrue($first->isSuccessful(), $first->getErrorOutput());
        self::assertSame('', $first->getErrorOutput());
        $output = $first->getOutput();
        self::assertSame(1, substr_count($output, "\n"));
        self::assertLessThanOrEqual(4096, strlen($output));
        $report = json_decode(trim($output), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('imagine.crm.signup_otp_lifecycle_sandbox', $report['protocol']);
        self::assertSame(1, $report['schema_version']);
        self::assertSame('REVIEW_ONLY_DENIED', $report['status']);
        self::assertSame(6, $report['core_otp_digits']);
        self::assertSame(900, $report['core_expiration_seconds']);
        self::assertSame('sandbox_code_created', $report['first_issue_status']);
        self::assertSame('sandbox_resend_cooldown', $report['cooldown_status']);
        self::assertSame('sandbox_code_created', $report['resend_status']);
        self::assertTrue($report['different_old_code_rejected_if_applicable']);
        self::assertTrue($report['new_code_verified_in_memory']);
        self::assertSame('sandbox_replay_denied', $report['replay_status']);
        self::assertSame('sandbox_no_challenge', $report['cross_contact_status']);
        self::assertSame('sandbox_no_challenge', $report['cross_channel_status']);
        self::assertTrue($report['five_bad_attempts_rejected']);
        self::assertSame('sandbox_attempt_limit', $report['sixth_attempt_status']);
        self::assertSame('sandbox_expired', $report['expiry_boundary_status']);
        self::assertTrue($report['twenty_synthetic_issues_created']);
        self::assertSame('sandbox_origin_budget_exhausted', $report['twenty_first_origin_issue_status']);
        self::assertSame('sandbox_no_challenge', $report['fresh_process_status']);
        self::assertSame(14, $report['illustrative_trial_days']);
        self::assertSame('within_window', $report['trial_phase']);
        self::assertSame('structurally_valid_only', $report['cnpj_format_status']);
        foreach ([
            'atomic_replay_prevention_verified', 'durable_otp_budget_verified',
            'trusted_latest_challenge_verified', 'contact_ownership_verified',
            'transport_provider_verified', 'one_trial_per_company_verified',
            'tenant_membership_verified', 'independent_security_review_verified',
            'can_send', 'can_register', 'can_authenticate', 'can_grant_access',
            'can_persist',
        ] as $deny) {
            self::assertFalse($report[$deny], $deny);
        }
        foreach ([
            'delivery_code', 'nonce', 'digest', 'key', 'origin_binding',
            'contact_binding', 'email', 'phone', 'cnpj', '012345', '@',
            '+5511', '12ABC34501DE35', 'signup.fixture', 'sandbox20',
        ] as $sensitive) {
            // A public diagnostic report must not mention identifiers or
            // untrusted raw data. The key "cnpj_format_status" describes
            // structural validity; allow that fixed harmless field.
            if ($sensitive === 'cnpj') {
                self::assertStringNotContainsString('"cnpj":', $output);
                continue;
            }
            self::assertStringNotContainsString($sensitive, $output);
        }

        $second = new Process([PHP_BINARY, self::script()]);
        $second->run();
        self::assertTrue($second->isSuccessful(), $second->getErrorOutput());
        self::assertSame($output, $second->getOutput());
    }

    public function test_script_rejects_all_arguments_without_echoing_them(): void
    {
        foreach (['--code=123456', '--email=real@example.com', '--help'] as $arg) {
            $run = new Process([PHP_BINARY, self::script(), $arg]);
            $run->run();
            self::assertSame(2, $run->getExitCode());
            self::assertSame('', $run->getOutput());
            self::assertSame(
                "F3-247 aceita somente a execucao local sem argumentos.\n",
                $run->getErrorOutput(),
            );
        }
    }

    public function test_source_never_accepts_environment_or_network_customer_data(): void
    {
        $source = file_get_contents(self::script());
        self::assertIsString($source);
        foreach ([
            'Mail::', 'Http::', 'DB::', 'Redis::', 'Cache::',
            'curl_', 'file_put_contents(', 'fopen(', 'file_get_contents(',
            '$_GET', '$_POST', '$_FILES', '$_SERVER', 'getenv(', 'env(',
            'shell_exec(', 'passthru(', 'https://', 'http://',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
