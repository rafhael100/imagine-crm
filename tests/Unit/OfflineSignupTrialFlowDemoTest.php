<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class OfflineSignupTrialFlowDemoTest extends TestCase
{
    private static function script(): string
    {
        return dirname(__DIR__, 2).'/scripts/ci/emit-signup-trial-review-demo.php';
    }

    public function test_real_cli_combines_otp_and_trial_quotas_without_authorizing_anything(): void
    {
        $a = new Process([PHP_BINARY, self::script()]);
        $a->setTimeout(20);
        $a->run();
        self::assertTrue($a->isSuccessful(), $a->getErrorOutput());
        self::assertSame('', $a->getErrorOutput());
        self::assertSame(1, substr_count($a->getOutput(), "\n"));
        $json = trim($a->getOutput());
        self::assertLessThanOrEqual(4096, strlen($json));
        $report = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('imagine.crm.synthetic_signup_trial_review', $report['protocol']);
        self::assertSame(1, $report['schema_version']);
        self::assertSame('REVIEW_ONLY_DENIED', $report['status']);
        self::assertSame(6, $report['otp_code_digits']);
        self::assertSame(900, $report['otp_validity_seconds']);
        self::assertSame('earlier_challenge_superseded_claim', $report['resend_old_code_superseded_observation']);
        self::assertSame('latest_challenge_claim_only', $report['resend_new_code_latest_observation']);
        self::assertFalse($report['atomic_resend_invalidation_verified']);
        self::assertSame('sample_within_illustrative_budget', $report['sample_issue_budget_observation']);
        self::assertSame('sample_contact_budget_exhausted', $report['sample_contact_limit_observation']);
        self::assertSame('sample_origin_budget_exhausted', $report['sample_origin_limit_observation']);
        self::assertSame('sample_contact_budget_exhausted', $report['sample_verify_limit_observation']);
        self::assertFalse($report['durable_otp_abuse_enforcement_verified']);
        self::assertTrue($report['uniform_public_issue_feedback']);
        self::assertTrue($report['uniform_public_verify_feedback']);
        self::assertTrue($report['public_feedback_is_only_offline']);
        self::assertTrue($report['email_template_otp_matches_in_memory']);
        self::assertTrue($report['whatsapp_template_otp_matches_in_memory']);
        self::assertTrue($report['email_whatsapp_validity_parity']);
        self::assertTrue($report['whatsapp_provider_not_approved']);
        self::assertSame('candidate_matches_only', $report['email_candidate_observation']);
        self::assertSame('candidate_matches_only', $report['phone_candidate_observation']);
        self::assertSame('candidate_mismatch', $report['wrong_phone_observation']);
        self::assertSame('expired', $report['expired_phone_observation']);
        self::assertSame('candidate_mismatch', $report['wrong_email_contact_observation']);
        self::assertSame('candidate_mismatch', $report['wrong_email_channel_observation']);
        self::assertTrue($report['distinct_channel_bindings_differed']);
        self::assertSame('within_window', $report['trial_phase']);
        self::assertSame('structurally_valid_only', $report['numeric_cnpj_check_status']);
        self::assertSame('structurally_valid_only', $report['alphanumeric_cnpj_check_status']);
        self::assertTrue($report['equivalent_cnpj_binding_matched']);
        self::assertTrue($report['distinct_cnpj_binding_differed']);
        self::assertSame(14, $report['illustrative_trial_days']);
        self::assertSame(14, $report['trial_remaining_days_rounded_up']);

        $limits = [];
        foreach ($report['illustrative_quota_meters'] as $card) {
            self::assertSame(0, $card['illustrative_used']);
            self::assertSame('in_range', $card['band']);
            $limits[$card['meter']] = $card['illustrative_limit'];
        }
        self::assertSame([
            'users' => 2, 'leads' => 20, 'automations' => 2,
            'pipelines' => 2, 'attachments_bytes' => 20971520,
            'ai_initial_credits' => 10, 'ai_bonus_credits' => 10,
        ], $limits);
        foreach ([
            'one_trial_per_company_verified',
            'identity_and_contact_binding_verified',
            'atomic_otp_consume_verified',
            'atomic_resend_invalidation_verified',
            'durable_otp_abuse_enforcement_verified',
            'tenant_and_membership_verified',
            'consent_and_lgpd_verified',
            'notification_transport_verified',
            'plan_and_quota_authority_verified',
            'authorized', 'can_register', 'can_authenticate',
            'can_send_code', 'can_charge', 'can_allocate_quota', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag], $flag);
        }
        foreach (['delivery_code', 'digest', 'nonce', 'opaque', '"cnpj":', 'email_address', 'phone_number', 'fixture-key', '11222333000181', '12ABC34501DE35', 'trial_binding_digest', 'demo.one@example.com', 'demo.two@example.com', '+5511999988888', 'synthetic_parameter_code', 'synthetic_preview', 'utility_template_candidate', 'synthetic-origin-never-a-real-ip', 'public_message', 'public_status_code'] as $secret) {
            self::assertStringNotContainsString($secret, $json);
        }
        $b = new Process([PHP_BINARY, self::script()]);
        $b->run();
        self::assertSame($json, trim($b->getOutput()));
    }

    public function test_cli_has_no_runtime_input_and_rejects_any_argument(): void
    {
        foreach (['--cnpj=PRIVATE_VALUE', '--email=test@example.org', '--help'] as $arg) {
            $run = new Process([PHP_BINARY, self::script(), $arg]);
            $run->run();
            self::assertSame(2, $run->getExitCode());
            self::assertSame('', $run->getOutput());
            self::assertSame(
                "F3-229 aceita somente execucao de laboratorio sem argumentos.\n",
                $run->getErrorOutput(),
            );
        }
    }

    public function test_lab_script_has_no_transport_database_or_customer_file_access(): void
    {
        $content = file_get_contents(self::script());
        self::assertIsString($content);
        foreach ([
            'curl_', 'file_get_contents(', 'file_put_contents(',
            'fopen(', 'PDO(', 'DB::', 'Mail::', 'Http::',
            '$_POST', '$_GET', '$_FILES', '$_SERVER',
            'getenv(', 'shell_exec(', 'passthru(', 'http://', 'https://',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $content);
        }
    }
}
