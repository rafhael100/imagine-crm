<?php
declare(strict_types=1);
require __DIR__.'/ServiceTargetGuard.php';
ServiceTargetGuard::entry(static function () use ($argv): array {
    ServiceTargetGuard::require(count($argv) === 3 && in_array($argv[1], ['check','prepare'], true));
    $expected = json_decode(file_get_contents('/p20/expected.json'), true, 32, JSON_THROW_ON_ERROR);
    $product = $expected['product'];
    $p = ServiceTargetGuard::PROFILES[$product] ?? null;
    ServiceTargetGuard::require(is_array($p));
    if ($product === 'crm') {
        require '/var/www/html/vendor/autoload.php';
        $app = require '/var/www/html/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    }
    $role = $argv[2];
    $snapshot = static fn(): array => [
        'libpq_overrides'=>array_filter(array_combine(['PGHOSTADDR','PGSERVICE','PGSERVICEFILE','PGOPTIONS'],array_map('getenv',['PGHOSTADDR','PGSERVICE','PGSERVICEFILE','PGOPTIONS'])),static fn($v): bool => $v !== false && $v !== ''),
        'dsn'=>getenv($product === 'crm' ? 'CRM_AUTO_S2S_DSN' : 'AGENTS_PRIVATE_DSN'),
        'profile'=>getenv($product === 'crm' ? 'CRM_AUTO_S2S_PROFILE' : 'AGENTS_PRIVATE_S2S_PROFILE'),
        'environment'=>$product === 'crm' ? $app->environment() : null,
        'role'=>$role,
        'password_file'=>$role === $p['owner'] ? '/p20/bootstrap.password' :
            ($role === 'crm_auto_sender' ? '/certs/sender.password' : getenv($product === 'crm' ? 'CRM_AUTO_S2S_DB_PASSWORD_FILE' : 'AGENTS_PRIVATE_DB_PASSWORD_FILE')),
    ];
    $before = $snapshot();
    $db = null;
    $proof = ServiceTargetGuard::check($before, $expected,
        static fn(string $host): array => gethostbynamel($host) ?: [],
        static function (array $config, bool $owner) use (&$db): array {
            $password = trim(file_get_contents($config['password_file']));
            ServiceTargetGuard::require($password !== '');
            $db = new PDO($config['dsn'], $config['role'], $password, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
            $extra = $owner ? ",current_setting('data_directory') AS directory,(SELECT system_identifier::text FROM pg_control_system()) AS system_identifier" : '';
            return $db->query("SELECT current_database() AS database,host(inet_server_addr()) AS address,inet_server_port() AS port,
                floor(extract(epoch FROM pg_postmaster_start_time()))::bigint::text AS started,
                (SELECT oid::text FROM pg_database WHERE datname=current_database()) AS database_oid,
                current_user AS role,(SELECT ssl FROM pg_stat_ssl WHERE pid=pg_backend_pid()) AS ssl,
                (SELECT rolsuper OR rolbypassrls OR rolcreaterole OR rolcreatedb OR rolreplication FROM pg_roles WHERE rolname=current_user) AS unsafe".$extra)->fetch(PDO::FETCH_ASSOC);
        });
    ServiceTargetGuard::require($snapshot() === $before && $db instanceof PDO);
    if ($argv[1] === 'prepare') {
        ServiceTargetGuard::require($role === $p['owner']);
        // The same verified PDO connection performs the fixture SQL; no reconnect or second bootstrap.
        $sql = stream_get_contents(STDIN);
        ServiceTargetGuard::require(is_string($sql) && $sql !== '');
        $statement = $db->query($sql);
        if ($statement->columnCount() > 0) {
            foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $value) echo (is_bool($value) ? ($value ? 't' : 'f') : $value)."\n";
        }
    }
    return $proof;
});
