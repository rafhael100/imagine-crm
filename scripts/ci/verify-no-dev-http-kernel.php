<?php

declare(strict_types=1);

/**
 * F3-261 — fail-closed HTTP kernel smoke proof on a GitHub-hosted runner.
 *
 * Only runs with Composer --no-dev, APP_ENV=production (simulated),
 * SQLite :memory: and array mailer. No listener, external request, customer
 * service, actual production connection, OTP delivery or business route.
 */
if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1
    || getenv('APP_ENV') !== 'production'
    || getenv('APP_DEBUG') !== 'false'
    || getenv('DB_CONNECTION') !== 'sqlite'
    || getenv('DB_DATABASE') !== ':memory:'
    || getenv('MAIL_MAILER') !== 'array'
    || getenv('DATABASE_URL') !== false
    || getenv('DB_URL') !== false) {
    fwrite(STDERR, "F3-261 NO-GO: isolated CI environment required.\n");
    exit(2);
}

try {
    require dirname(__DIR__, 2).'/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';

    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $checks = [
        ['GET', '/up', 200],
        ['HEAD', '/up', 200],
        ['GET', '/', 404],
        ['GET', '/signup', 404],
        ['POST', '/signup', 404],
        ['POST', '/api/trial', 404],
        ['POST', '/api/otp/verify', 404],
        ['POST', '/__ci/f3-252/issue', 404],
        ['POST', '/__ci/f3-254/issue', 404],
        ['POST', '/__ci/f3-258/trial', 404],
        ['POST', '/__ci/f3-259/trial', 404],
    ];

    $tested = 0;
    foreach ($checks as [$method, $path, $expectedStatus]) {
        $request = \Illuminate\Http\Request::create(
            $path,
            $method,
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $response = $kernel->handle($request);
        try {
            if ($response->getStatusCode() !== $expectedStatus
                || $response->headers->has('Set-Cookie')
                || $response->headers->has('Location')) {
                throw new RuntimeException('HTTP kernel boundary violated.');
            }
            ++$tested;
        } finally {
            $kernel->terminate($request, $response);
        }
    }

    if ($tested !== count($checks) || count($checks) !== 11) {
        throw new RuntimeException('Incomplete HTTP kernel probe.');
    }

    fwrite(STDOUT,
        "F3-261 PASS: no-dev production-env PHP kernel, 11 synthetic HTTP checks, signup/trial unavailable; NO-GO.\n",
    );
} catch (Throwable) {
    // Never print exception trace, request input or Laravel error body in CI.
    fwrite(STDERR, "F3-261 NO-GO: isolated HTTP kernel contract failed.\n");
    exit(1);
}
