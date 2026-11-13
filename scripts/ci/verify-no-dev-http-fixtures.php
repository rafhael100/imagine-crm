<?php

declare(strict_types=1);

/**
 * F3-255: CI-only proof that Composer --no-dev does not load test fixtures.
 * Never a VPS action, production release, customer-data test or credential.
 */
if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1) {
    fwrite(STDERR, "F3-255: somente CI sem argumentos.\n");
    exit(2);
}
require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    if (!class_exists(\App\Core\Identity\OfflineSignupOtpChallenge::class)) {
        throw new RuntimeException('Core autoload missing.');
    }
    foreach (spl_autoload_functions() ?: [] as $loader) {
        if ($loader instanceof \Composer\Autoload\ClassLoader
            && array_key_exists('Tests\\', $loader->getPrefixesPsr4())) {
            throw new RuntimeException('Tests namespace in no-dev loader.');
        }
    }
    foreach ([
        'Tests\\Support\\SignupTrialHttpFixture',
        'Tests\\Support\\SignupOtpLifecycleSandbox',
        'Tests\\Support\\TrialEnrollmentLifecycleSandbox',
        'Tests\\Feature\\SignupTrialHttpFixtureFlowTest',
        'Tests\\Feature\\SignupTrialHttpFixtureResendTest',
        'Tests\\Unit\\SignupTrialHttpLabWorkflowContractTest',
    ] as $name) {
        if (class_exists($name, true) || interface_exists($name, true)
            || trait_exists($name, true)) {
            throw new RuntimeException('Forbidden test fixture autoload.');
        }
    }
    fwrite(STDOUT, "F3-255 PASS: App Core available; no Tests autoload under Composer --no-dev.\n");
} catch (Throwable) {
    fwrite(STDERR, "F3-255 NO-GO: autoload boundary failed.\n");
    exit(1);
}
