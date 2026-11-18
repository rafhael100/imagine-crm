<?php
declare(strict_types=1);
// Cluster initialization only. Application preparation/migrations still require the unchanged #914 guard.
require '/var/www/html/tools/p20/DisposablePreparation.php';
use PortalTestTools\P20\DisposablePreparation as Gate;
$done = false;
register_shutdown_function(static function () use (&$done): void { if (!$done) exit(1); });
try {
    Gate::require(getenv('CI') === 'true' && getenv('APP_ENV') === 'testing');
    Gate::require(getenv('DB_HOST') === '127.0.0.1' && getenv('DB_PORT') === '55432');
    Gate::require(getenv('DB_DATABASE') === Gate::DATABASE && getenv('DB_USERNAME') === 'postgres');
    $local = Gate::localEvidence('/tmp/p20-cluster','/usr/lib/postgresql/18/bin/pg_controldata',55432);
    $db = new PDO('pgsql:host=127.0.0.1;port=55432;dbname=postgres;sslmode=disable','postgres',getenv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $sql = $db->query("SELECT current_database() AS db,host(inet_server_addr()) AS addr,inet_server_port() AS port,
        current_setting('data_directory') AS dir,floor(extract(epoch FROM pg_postmaster_start_time()))::bigint AS started,
        system_identifier::text FROM pg_control_system()")->fetch(PDO::FETCH_ASSOC);
    Gate::require($sql['db'] === 'postgres' && $sql['addr'] === '127.0.0.1' && (int)$sql['port'] === 55432);
    Gate::require($sql['dir'] === $local['directory'] && (int)$sql['started'] === $local['started'] && $sql['system_identifier'] === $local['system_identifier']);
    Gate::require(Gate::localEvidence('/tmp/p20-cluster','/usr/lib/postgresql/18/bin/pg_controldata',55432) === $local);
    // Same connection; fixed database name, no external target and no reconnect.
    $db->exec('CREATE DATABASE portal_automation_p20_ci');
    $done = true;
    echo "P20_LOCAL_DATABASE_CREATED\n";
} catch (Throwable) { fwrite(STDERR,"P20_LOCAL_DATABASE_CREATION_DENIED\n"); exit(1); }
