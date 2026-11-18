<?php

declare(strict_types=1);

use App\Core\Identity\PrincipalReference;
use App\Core\Leads\LaboratorySignedSiteCapture;
use App\Core\Leads\LaboratoryTenantLeadLedger;
use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

/**
 * One real, persistent crm_app_ci connection in a separate PHP process.
 * Only the opted-in disposable PG07 test can start this worker. Its pipe
 * protocol has no SQL command and never accepts a connection destination.
 */
final class Pg07ObservedStatement extends PDOStatement
{
    public static bool $record = false;
    public static ?array $failure = null;
    public static array $completed = [];

    protected function __construct() {}

    public function execute(?array $params = null): bool
    {
        // Observe the driver, without replacing SQL, arguments or results.
        // The ledger deliberately hides the original exception from callers.
        try {
            $result = parent::execute($params);
            $kind = $this->kind();
            if (self::$record && $result && $kind !== null) {
                self::$completed[] = $kind;
            }
            return $result;
        } catch (PDOException $error) {
            if (self::$record) {
                self::$failure = [
                    'sqlstate' => $this->errorCode(),
                    'statement' => $this->kind(),
                ];
            }
            throw $error; // The same exception reaches Laravel's rollback.
        }
    }

    private function kind(): ?string
    {
        if (str_contains($this->queryString, 'pg_advisory_xact_lock')) {
            return 'advisory';
        }
        if (!str_starts_with(strtolower(ltrim($this->queryString)), 'insert into ')) {
            return null;
        }
        foreach ([
            'crm_lab_lead_capture_keys' => 'claim_insert',
            'crm_lab_leads' => 'lead_insert',
            'crm_lab_lead_events' => 'audit_insert',
        ] as $table => $kind) {
            if (preg_match('/\Ainsert\s+into\s+(?:public\.|"public"\.)"?'
                .$table.'"?(?=\s|\()/i', ltrim($this->queryString)) === 1) {
                return $kind;
            }
        }
        return null;
    }
}

function pg07ReadFrame(): array
{
    $read = [STDIN];
    $write = $except = [];
    if (stream_select($read, $write, $except, 15) !== 1) {
        throw new RuntimeException('PG07 command deadline.');
    }
    $line = fgets(STDIN, 16384);
    if (!is_string($line) || !str_ends_with($line, "\n")) {
        throw new RuntimeException('PG07 command frame.');
    }
    $frame = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($frame)) {
        throw new RuntimeException('PG07 command shape.');
    }
    return $frame;
}

function pg07WriteFrame(array $frame): void
{
    $line = json_encode($frame, JSON_THROW_ON_ERROR)."\n";
    if (fwrite(STDOUT, $line) !== strlen($line)) {
        throw new RuntimeException('PG07 output pipe.');
    }
    fflush(STDOUT);
}

try {
    $marker = $argv[1] ?? '';
    if (getenv('GITHUB_ACTIONS') !== 'true'
        || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
        || getenv('CI') !== 'true'
        || getenv('APP_ENV') !== 'testing'
        || getenv('CRM_LEADS_LAB') !== 'synthetic-only'
        || getenv('CRM_LEADS_PG07_CONCURRENCY_CI') !== '1'
        || getenv('DB_HOST') !== '127.0.0.1'
        || getenv('DB_DATABASE') !== 'imagine_crm_ci'
        || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
        || preg_match('/\Acrm_pg07_[a-f0-9]{24}\z/D', $marker) !== 1) {
        throw new LogicException('PG07 disposable CI guard.');
    }
    putenv('PGAPPNAME='.$marker); // Identity exists before connecting.
    $root = dirname(__DIR__, 3);
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $pg = config('database.connections.pgsql');
    if (!is_array($pg) || $pg['host'] !== '127.0.0.1'
        || $pg['database'] !== 'imagine_crm_ci'
        || (string) $pg['port'] !== '5432') {
        throw new LogicException('PG07 database guard.');
    }
    $pg['url'] = null;
    $pg['username'] = getenv('RLS_TEST_USERNAME');
    $pg['password'] = getenv('RLS_TEST_PASSWORD');
    config(['database.connections.pg07_worker' => $pg]);
    $db = DB::connection('pg07_worker');
    $db->selectOne("SELECT set_config('application_name', ?, false)", [$marker]);
    $db->statement("SET statement_timeout = '15s'");
    $db->statement("SET idle_in_transaction_session_timeout = '15s'");
    if ($db->getPdo()->getAttribute(PDO::ATTR_PERSISTENT)) {
        throw new LogicException('PG07 requires a nonpersistent PDO connection.');
    }
    $db->getPdo()->setAttribute(PDO::ATTR_STATEMENT_CLASS, [Pg07ObservedStatement::class, []]);

    $fixture = pg07ReadFrame();
    $timeout = $fixture['lock_timeout_ms'] ?? null;
    if (!is_int($timeout) || $timeout < 1000 || $timeout > 10000) {
        throw new LogicException('PG07 lock timeout fixture.');
    }
    $db->selectOne("SELECT set_config('lock_timeout', ?, false)", [$timeout.'ms']);
    $claim = new SyntheticIdempotencyClaim(
        new LeadSubmissionIdentity($fixture['organization'], $fixture['binding'], $fixture['submission']),
        $fixture['site'], $fixture['form'], $fixture['destination'],
    );
    $principal = new PrincipalReference($fixture['principal']);
    $ledger = new LaboratoryTenantLeadLedger($db);
    $capture = new LaboratorySignedSiteCapture($ledger, (string) getenv('CRM_LEADS_SIGNING_KEY_CI'));
    $identity = (array) $db->selectOne(
        'SELECT pid, backend_start::text, application_name, usename, datname
           FROM pg_stat_activity WHERE pid = pg_backend_pid()',
    );
    $identity['pid'] = (int) $identity['pid'];
    pg07WriteFrame(['phase' => 'ready', 'identity' => $identity]);

    $cleanState = static function () use ($db): array {
        $state = $db->selectOne("SELECT pg_backend_pid() AS pid,
            COALESCE(current_setting('app.tenant_id', true), '') AS tenant,
            (SELECT count(*) FROM pg_locks
              WHERE pid = pg_backend_pid() AND locktype = 'advisory') AS advisory_locks");
        return [
            'pid' => (int) $state->pid,
            'transaction_level' => $db->transactionLevel(),
            'pdo_in_transaction' => $db->getPdo()->inTransaction(),
            'tenant' => $state->tenant,
            'advisory_locks' => (int) $state->advisory_locks,
            'unscoped_leads' => $db->table('public.crm_lab_leads')->count('id'),
            'unscoped_claims' => $db->table('public.crm_lab_lead_capture_keys')->count('lead_id'),
        ];
    };

    for ($commands = 0; $commands < 8; ++$commands) {
        $command = pg07ReadFrame()['command'] ?? null;
        if ($command === 'stop') {
            pg07WriteFrame(['phase' => 'stopped']);
            DB::purge('pg07_worker');
            exit(0);
        }
        if ($command === 'other_tenant') {
            $rows = $ledger->listSynthetic($principal, $fixture['other_organization']);
            pg07WriteFrame([
                'phase' => 'other_tenant', 'ids' => array_column($rows, 'id'),
                'state' => $cleanState(),
            ]);
            continue;
        }
        if ($command !== 'capture') {
            throw new LogicException('PG07 closed command protocol.');
        }

        Pg07ObservedStatement::$failure = null;
        Pg07ObservedStatement::$completed = [];
        Pg07ObservedStatement::$record = true;
        try {
            $answer = $capture->captureSigned(
                $principal, $fixture['organization'], $fixture['lead'],
                $fixture['title'], 'sites', $claim, $fixture['nonce'],
                $fixture['issued'], $fixture['expires'], $fixture['now'], $fixture['signature'],
            );
            $result = ['phase' => 'attempt', 'answer' => $answer, 'exception' => null];
        } catch (Throwable $error) {
            // No exception message, SQL text, parameters or driver detail.
            $result = ['phase' => 'attempt', 'answer' => null, 'exception' => get_class($error)];
        } finally {
            Pg07ObservedStatement::$record = false;
        }
        $result['failure'] = Pg07ObservedStatement::$failure;
        $result['completed'] = Pg07ObservedStatement::$completed;
        // Inspect before disconnect, RESET, a new connection or a retry.
        $result['state'] = $cleanState();
        pg07WriteFrame($result);
    }
    throw new LogicException('PG07 command budget.');
} catch (Throwable $error) {
    pg07WriteFrame(['phase' => 'fatal', 'exception' => get_class($error)]);
    exit(1);
}
