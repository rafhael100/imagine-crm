<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SignupOtpLifecycleEvidenceWorkflowContractTest extends TestCase
{
    public function test_workflow_is_read_only_with_short_lived_synthetic_artifact(): void
    {
        $src = file_get_contents(
            dirname(__DIR__, 2).'/.github/workflows/crm-f3-248-otp-lifecycle-evidence.yml',
        );
        self::assertIsString($src);
        foreach ([
            'on:', 'pull_request:', 'workflow_dispatch:',
            'permissions:', 'contents: read',
            'DB_DATABASE:', "':memory:'",
            'MAIL_MAILER: array', 'APP_ENV: testing',
            'php-version:', "'8.4'",
            'SignupOtpLifecycleSandboxTest.php',
            'TrialEnrollmentLifecycleSandboxTest.php',
            'SignupOtpLifecycleEvidenceCliTest.php',
            'CompositeSignupTrialFunctionalLabCliTest.php',
            'emit-signup-otp-lifecycle-evidence.php',
            'emit-composite-signup-trial-lab.php',
            'otp-trial-composite-offline.json',
            'trial_after_exact_boundary_phase',
            'jq -e ', 'status == "REVIEW_ONLY_DENIED"',
            'can_send == false', 'can_register == false',
            'can_authenticate == false', 'can_persist == false',
            'sha256sum --check manifest.sha256',
            'uses: actions/upload-artifact@v4',
            'retention-days: 7',
        ] as $required) {
            self::assertStringContainsString($required, $src);
        }
        foreach ([
            'secrets.', 'SSH_', 'kubectl', 'docker build',
            'deploy', 'curl ', 'wget ', 'git push',
            'pull_request_target:', 'schedule:', 'permissions: write',
            'MAIL_MAILER: smtp',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $src);
        }
    }
}
