<?php

declare(strict_types=1);

// SEC-CI-02: read-only, fail-closed guard for synthetic GitHub-hosted CI DB.
// This is NOT a production database, deployment, or network safety barrier.
// The workflow MUST run --verify before EVERY tenant/auth/async migration.

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

function denyEphemeral(string $reason): never
{
    fwrite(STDERR, 'SEC-CI-02 DENIED: '.$reason.PHP_EOL);
    exit(42);
}

function readEphemeral(array $command): string
{
    $process = new Process($command, null, null, null, 15);
    $process->run();

    if (!$process->isSuccessful()) {
        denyEphemeral('disposable PostgreSQL service verification failed');
    }

    return trim($process->getOutput());
}

$mode = $argv[1] ?? '--verify';
if ($argc > 2 || !in_array($mode, ['--verify', '--static-only'], true)) {
    denyEphemeral('unrecognized preflight option');
}

// Check process and target independently from the application's env/config.
// A syntactically valid name is not proof of an ephemeral server.
$expected = [
    'CI' => 'true',
    'GITHUB_ACTIONS' => 'true',
    'GITHUB_REPOSITORY' => 'rafhael100/imagine-crm',
    'RUNNER_ENVIRONMENT' => 'github-hosted',
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '5432',
    'DB_DATABASE' => 'imagine_crm_ci',
    'DB_USERNAME' => 'postgres',
];

foreach ($expected as $name => $value) {
    if (getenv($name) !== $value) {
        denyEphemeral('unexpected CI environment or target: '.$name);
    }
}

$event = getenv('GITHUB_EVENT_NAME');
$ref = getenv('GITHUB_REF');
$base = getenv('GITHUB_BASE_REF');

// The existing CI workflow runs on *both* pushes into the single fixed DEV
// branch and pull requests targeting main or that DEV branch. Restrict both
// paths rather than allowing arbitrary events or breaking push regressions.
$validPush = $event === 'push'
    && $ref === 'refs/heads/dev/fast-track-crm-2026-11-04'
    && ($base === false || $base === '');
$validPr = $event === 'pull_request'
    && is_string($ref)
    && preg_match('~\Arefs/pull/[1-9][0-9]*/merge\z~D', $ref) === 1
    && in_array($base, ['main', 'dev/fast-track-crm-2026-11-04'], true);

if (!$validPush && !$validPr && !$validF232Push && !$validF232Pr) {
    denyEphemeral('unsupported event, branch or pull request target');
}

// Disallow driver-level and libpq alternative connection paths. Retain only
// F232: one extra CI-only branch and stacked PR, not a generic
// permission to migrate on review branches. Keep exact head and base names.
$validF232Push = $event === 'push'
    && $ref === 'refs/heads/review/crm-aia-f232-ed25519-pg16-atomic-2026-11-14'
    && ($base === false || $base === '');
$validF232Pr = $event === 'pull_request'
    && is_string($ref)
    && preg_match('~\\Arefs/pull/[1-9][0-9]*/merge\\z~D', $ref) === 1
    && $base === 'review/crm-aia-f231-ed25519-deny-ci-2026-11-14'
    && getenv('GITHUB_HEAD_REF') === 'review/crm-aia-f232-ed25519-pg16-atomic-2026-11-14';
if ($validF232Pr) {
    // A fork with the same branch name must not satisfy this exception.
    $eventPath = getenv('GITHUB_EVENT_PATH');
    $payload = is_string($eventPath) && strlen($eventPath) > 0
        ? json_decode((string) file_get_contents($eventPath), true)
        : null;
    $validF232Pr = is_array($payload)
        && ($payload['pull_request']['head']['repo']['full_name'] ?? null)
            === 'rafhael100/imagine-crm'
        && ($payload['pull_request']['head']['ref'] ?? null)
            === 'review/crm-aia-f232-ed25519-pg16-atomic-2026-11-14';
}
// the literal disposable service settings owned by the job.
foreach ([
    'DB_URL', 'DATABASE_URL', 'PGHOST', 'PGHOSTADDR', 'PGPORT', 'PGDATABASE',
    'PGUSER', 'PGSERVICE', 'PGSERVICEFILE', 'PGOPTIONS',
    'PGSSLMODE', 'PGPASSFILE',
] as $name) {
    if (getenv($name) !== false && getenv($name) !== '') {
        denyEphemeral('alternate PostgreSQL connection setting forbidden: '.$name);
    }
}

$password = getenv('DB_PASSWORD');
if (!is_string($password)
    || $password === ''
    || !hash_equals($password, (string) getenv('PGPASSWORD'))) {
    denyEphemeral('disposable database credentials missing or mismatched');
}

if ($mode === '--static-only') {
    // Unit-testing the static policy only: NO container nor DB identity read.
    echo 'SEC-CI-02 static checks passed; database NOT verified.'.PHP_EOL;
    exit(0);
}

// Require exactly one running postgres:16 container without host bind mounts.
// A hosted runner and fixed localhost DB alone do not prove that the DB is
// an ephemeral service; compare server identity as seen by docker vs psql.
$ids = array_values(array_filter(explode("\n", readEphemeral([
    'docker', 'ps', '--filter', 'ancestor=postgres:16', '--format', '{{.ID}}',
]))));
if (count($ids) !== 1 || preg_match('/\A[a-f0-9]{12,64}\z/D', $ids[0]) !== 1) {
    denyEphemeral('expected exactly one PostgreSQL 16 CI container');
}

$inspection = readEphemeral([
    'docker', 'inspect', '--format',
    '{{.State.Running}}|{{.Config.Image}}|{{range .Mounts}}{{if eq .Type "bind"}}bind;{{end}}{{end}}|{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}',
    $ids[0],
]);
$parts = explode('|', $inspection);

if (count($parts) !== 4
    || $parts[0] !== 'true'
    || $parts[1] !== 'postgres:16'
    || $parts[2] !== ''
    || filter_var($parts[3], FILTER_VALIDATE_IP) === false) {
    denyEphemeral('unexpected, stopped or persistently mounted CI PostgreSQL');
}

// Read-only cluster identity query, executed inside the inspected service
// AND through the localhost port that application migrations will use.
$query = "SELECT current_database() || '|' || current_user || '|' || system_identifier::text FROM pg_control_system()";
$inside = readEphemeral([
    'docker', 'exec', $ids[0], 'psql', '-X', '-A', '-t',
    '-v', 'ON_ERROR_STOP=1', '-U', 'postgres', '-d', 'imagine_crm_ci',
    '-c', $query,
]);
$outside = readEphemeral([
    'psql', '-X', '-A', '-t', '-v', 'ON_ERROR_STOP=1',
    '-h', '127.0.0.1', '-p', '5432', '-U', 'postgres', '-d', 'imagine_crm_ci',
    '-c', $query,
]);
if (preg_match('/\Aimagine_crm_ci\|postgres\|[0-9]{15,22}\z/D', $inside) !== 1
    || !hash_equals($inside, $outside)) {
    denyEphemeral('PostgreSQL cluster identity does not match the inspected disposable service');
}

echo 'SEC-CI-02 confirmed matching disposable GitHub PostgreSQL service (read-only).'.PHP_EOL;
