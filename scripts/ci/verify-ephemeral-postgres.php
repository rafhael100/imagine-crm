<?php

declare(strict_types=1);

// CRM-SEC-CI-01: fail-closed, read-only preflight for the GitHub-hosted
// synthetic PostgreSQL CI service. NOT a production database safety barrier.
// This must run BEFORE every schema migration in the PostgreSQL CI workflow.

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

function deny(string $reason): never
{
    fwrite(STDERR, 'CRM-SEC-CI-01 DENIED: '.$reason.PHP_EOL);
    exit(42);
}

function runReadOnly(array $args): string
{
    $process = new Process($args, null, null, null, 10);
    $process->run();
    if (!$process->isSuccessful()) {
        deny('laboratory service verification failed');
    }

    return trim($process->getOutput());
}

$mode = $argv[1] ?? '--verify';
if (!in_array($mode, ['--verify', '--static-only'], true) || $argc > 2) {
    deny('unknown preflight operation');
}

// Validate process identity and DSN before opening ANY database connection.
$expected = [
    'CI' => 'true',
    'GITHUB_ACTIONS' => 'true',
    'GITHUB_REPOSITORY' => 'rafhael100/imagine-crm',
    'GITHUB_EVENT_NAME' => 'pull_request',
    'RUNNER_ENVIRONMENT' => 'github-hosted',
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '5432',
    'DB_DATABASE' => 'imagine_crm_ci',
    'CRM_TENANT_SCHEMA_LAB_DATABASE' => 'imagine_crm_ci',
    'DB_USERNAME' => 'postgres',
];

foreach ($expected as $name => $value) {
    if (getenv($name) !== $value) {
        deny('unexpected environment or database setting: '.$name);
    }
}

foreach (['DB_URL', 'DATABASE_URL', 'PGHOST', 'PGSERVICE', 'PGSERVICEFILE'] as $name) {
    if (getenv($name) !== false && getenv($name) !== '') {
        deny('alternate connection override forbidden: '.$name);
    }
}

if (getenv('DB_PASSWORD') === false || getenv('DB_PASSWORD') === '') {
    deny('laboratory database credentials are missing');
}

if ($mode === '--static-only') {
    // For synthetic negative unit tests. Never substitute for --verify in CI.
    echo "CRM-SEC-CI-01 static checks passed; database NOT verified.".PHP_EOL;
    exit(0);
}

// GitHub-hosted runners get service containers; reject absent, duplicate,
// non-running, bind-mounted or unexpected PostgreSQL containers.
$ids = array_values(array_filter(explode("\n", runReadOnly([
    'docker', 'ps', '--filter', 'ancestor=postgres:16', '--format', '{{.ID}}',
]))));
if (count($ids) !== 1 || !preg_match('/^[a-f0-9]{12,64}$/D', $ids[0])) {
    deny('expected exactly one PostgreSQL 16 service container');
}

$inspection = runReadOnly([
    'docker', 'inspect', '--format',
    '{{.State.Running}}|{{.Config.Image}}|{{range .Mounts}}{{if eq .Type "bind"}}bind;{{end}}{{end}}|{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}',
    $ids[0],
]);
$parts = explode('|', $inspection);
if (count($parts) !== 4
    || $parts[0] !== 'true'
    || $parts[1] !== 'postgres:16'
    || $parts[2] !== ''
    || !filter_var($parts[3], FILTER_VALIDATE_IP)) {
    deny('PostgreSQL service is unexpected, mounted persistently or lacks a container IP');
}

// SELECT only. Compare the PostgreSQL *cluster identity* as seen directly
// inside the inspected service container and over the loopback client port.
// NAT/proxy routing may legitimately change inet_server_addr(), so IP equality
// is not a reliable test of whether both connections reach the same instance.
$query = "SELECT current_database() || '|' || current_user || '|' || system_identifier::text FROM pg_control_system()";
$inside = runReadOnly([
    'docker', 'exec', $ids[0], 'psql', '-X', '-A', '-t',
    '-v', 'ON_ERROR_STOP=1', '-U', 'postgres', '-d', 'imagine_crm_ci',
    '-c', $query,
]);
$outside = runReadOnly([
    'psql', '-X', '-A', '-t', '-v', 'ON_ERROR_STOP=1',
    '-h', '127.0.0.1', '-p', '5432', '-U', 'postgres', '-d', 'imagine_crm_ci',
    '-c', $query,
]);
if (!preg_match('/^imagine_crm_ci\|postgres\|[0-9]{15,22}$/D', $inside)
    || !hash_equals($inside, $outside)) {
    deny('connected PostgreSQL cluster does not match the disposable CI service');
}

echo "CRM-SEC-CI-01 verified GitHub-hosted PostgreSQL service (read-only).".PHP_EOL;
