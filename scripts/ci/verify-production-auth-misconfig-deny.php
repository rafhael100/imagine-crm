<?php

declare(strict_types=1);

/**
 * F3-262: simulate a dangerously enabled laboratory IAM env flag while
 * booting the production-profile Laravel application IN AN EPHEMERAL CI
 * PROCESS ONLY. Never connects to a customer system or provisions identity.
 *
 * This proof must fail closed on config, identity, membership and authorization
 * boundaries even when an operator supplies CRM_AUTH_EXPERIMENTAL=true.
 */
if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1
    || getenv('APP_ENV') !== 'production'
    || getenv('APP_DEBUG') !== 'false'
    || getenv('CRM_AUTH_EXPERIMENTAL') !== 'true'
    || getenv('DB_CONNECTION') !== 'sqlite'
    || getenv('DB_DATABASE') !== ':memory:'
    || getenv('MAIL_MAILER') !== 'array'
    || getenv('DATABASE_URL') !== false
    || getenv('DB_URL') !== false) {
    fwrite(STDERR, "F3-262 NO-GO: CI-only simulated configuration required.\n");
    exit(2);
}

try {
    require dirname(__DIR__, 2).'/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // This must be true. Otherwise the check could pass without exercising
    // the potentially dangerous configuration that we intend to deny.
    if (config('identity.experimental') !== true || !$app->environment('production')) {
        throw new RuntimeException('Misconfiguration fixture did not boot.');
    }

    // No dedicated CRM guard/provider may be bootstrapped in this profile.
    if (config('auth.guards.crm_identity') !== null
        || config('auth.providers.crm_global_identities') !== null) {
        throw new RuntimeException('Experimental CRM auth guard exposed.');
    }

    // Business authorizer remains DENY even for a constructed context.
    $authorizer = $app->make(\App\Core\Authorization\Authorizer::class);
    if (!$authorizer instanceof \App\Core\Authorization\DenyByDefaultAuthorizer) {
        throw new RuntimeException('Unexpected CRM authorization policy.');
    }
    $request = new \App\Core\Authorization\AccessRequest(
        new \App\Core\Tenancy\TenantContext(
            '11111111-1111-4111-8111-111111111111',
            '33333333-3333-4333-8333-333333333333',
            '44444444-4444-4444-8444-444444444444',
        ),
        'lead',
        'read',
    );
    if ($authorizer->decide($request)->allowed) {
        throw new RuntimeException('Synthetic business access accidentally allowed.');
    }

    // No guard/session is a trusted principal, even with the feature flag.
    $source = $app->make(\App\Core\Identity\GuardPrincipalSource::class);
    try {
        $source->current();
        throw new RuntimeException('Unverified principal was resolved.');
    } catch (\Illuminate\Auth\AuthenticationException) {
        // Required: no authenticated principal.
    }

    // Both experimental services MUST fail their explicit environment gates.
    foreach ([
        \App\Core\Identity\ExperimentalAuthenticator::class,
        \App\Core\Identity\LaboratoryProvisioner::class,
    ] as $service) {
        try {
            $app->make($service);
            throw new RuntimeException('Experimental IAM service available in production profile.');
        } catch (\LogicException $exception) {
            // Validate a specific gate; never accept a random constructor
            // failure as evidence of correct environment containment.
            if (!in_array($exception->getMessage(), [
                'Experimental server-side authentication only.',
                'Isolated laboratory provisioner only.',
            ], true)) {
                throw new RuntimeException('Unexpected IAM service rejection.');
            }
        }
    }

    fwrite(STDOUT, "F3-262 PASS: hostile IAM flag cannot expose principal, guard, provisioner or business ALLOW; NO-GO.\n");
} catch (\Throwable) {
    // No stack trace, request body or environment variable dump to CI logs.
    fwrite(STDERR, "F3-262 NO-GO: simulated production IAM containment failed.\n");
    exit(1);
}
