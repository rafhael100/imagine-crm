<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class CompositeSignupTrialFunctionalLabCliTest extends TestCase
{
    private static function script(): string
    {
        return dirname(__DIR__, 2).'/scripts/ci/emit-composite-signup-trial-lab.php';
    }

    public function test_real_core_otp_and_trial_lab_run_as_one_sanitized_deterministic_sequence(): void
    {
        $first = new Process([PHP_BINARY, self::script()]);
        $first->setTimeout(25);
        $first->run();
        self::assertTrue($first->isSuccessful(), $first->getErrorOutput());
        self::assertSame('', $first->getErrorOutput());
        self::assertSame(1, substr_count($first->getOutput(), "\n"));
        $text = trim($first->getOutput());
        self::assertLessThanOrEqual(4096, strlen($text));
        $report = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('imagine.crm.synthetic_otp_trial_functional_lab', $report['protocol']);
        self::assertSame(1, $report['schema_version']);
        self::assertSame('REVIEW_ONLY_DENIED', $report['status']);
        self::assertSame(6, $report['otp_digits']);
        self::assertSame(900, $report['otp_validity_seconds']);
        self::assertSame('sandbox_code_created', $report['otp_issue_status']);
        self::assertSame('sandbox_match_consumed_in_memory', $report['otp_match_status']);
        self::assertSame('sandbox_replay_denied', $report['otp_replay_status']);
        self::assertSame('sandbox_first_trial_marker', $report['trial_first_marker_status']);
        self::assertSame('sandbox_duplicate_trial_claim', $report['same_company_case_insensitive_duplicate_status']);
        self::assertSame('sandbox_first_trial_marker', $report['other_company_status']);
        self::assertSame('within_window', $report['trial_during_phase']);
        self::assertSame('after_window', $report['trial_after_exact_boundary_phase']);
        self::assertSame(0, $report['trial_days_remaining_after_boundary']);
        self::assertSame(14, $report['trial_days_illustrative']);
        self::assertTrue($report['otp_in_memory_is_not_identity']);
        self::assertTrue($report['one_trial_per_cnpj_not_durably_verified']);
        self::assertSame([
            'ai_bonus_credits' => 10,
            'ai_initial_credits' => 10,
            'attachments_bytes' => 20971520,
            'automations' => 2,
            'leads' => 20,
            'pipelines' => 2,
            'users' => 2,
        ], $report['trial_quota_illustrative_limits']);
        foreach ([
            'transport_verified', 'registered_company_verified',
            'company_owner_verified', 'tenant_authorization_verified',
            'atomic_trial_reservation_verified', 'atomic_otp_consume_verified',
            'consent_lgpd_verified', 'can_send', 'can_create_company',
            'can_reserve_trial', 'can_register', 'can_authenticate',
            'can_allocate_quota', 'can_charge', 'can_persist',
        ] as $flag) {
            self::assertFalse($report[$flag], $flag);
        }
        foreach ([
            '@', '+5511', '12ABC34501DE35', '11222333000181',
            '"delivery_code"', '"nonce"', '"digest"', '"origin_binding"',
            '"contact_binding"', '"cnpj"', '"key"', '"password"',
            'demo.signup', 'demo-origin-never-an-ip',
        ] as $secret) {
            self::assertStringNotContainsString($secret, $text);
        }
        $second = new Process([PHP_BINARY, self::script()]);
        $second->run();
        self::assertTrue($second->isSuccessful(), $second->getErrorOutput());
        self::assertSame($text, trim($second->getOutput()));
    }

    public function test_composite_script_rejects_all_arguments(): void
    {
        foreach (['--code=123456', '--cnpj=PRIVATE', '--help'] as $arg) {
            $run = new Process([PHP_BINARY, self::script(), $arg]);
            $run->run();
            self::assertSame(2, $run->getExitCode());
            self::assertSame('', $run->getOutput());
            self::assertSame(
                "F3-250 nao recebe parametros ou dados de clientes.\n",
                $run->getErrorOutput(),
            );
        }
    }

    public function test_composite_script_has_no_transport_persistence_env_or_customer_inputs(): void
    {
        $src = file_get_contents(self::script());
        self::assertIsString($src);
        foreach ([
            'Mail::', 'Http::', 'DB::', 'Redis::', 'Cache::',
            'file_put_contents(', 'file_get_contents(', 'fopen(',
            'curl_', 'https://', 'http://',
            '$_GET', '$_POST', '$_FILES', '$_SERVER',
            'env(', 'getenv(', 'shell_exec(', 'passthru(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $src);
        }
    }
}
