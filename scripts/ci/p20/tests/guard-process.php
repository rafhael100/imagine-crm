<?php
declare(strict_types=1);
require __DIR__.'/../ServiceTargetGuard.php';
ServiceTargetGuard::entry(static function () use ($argv): array {
    [$script, $case, $sentinel] = $argv;
    if ($case === 'exit-zero') exit(0);
    if ($case === 'exception') throw new RuntimeException('SYNTHETIC_SECRET_MUST_NOT_LEAK');
    if ($case === 'fatal') nonexistent_synthetic_function();
    if ($case === 'missing-proof') return [];
    $product = str_starts_with($case, 'agents') ? 'agents' : 'crm';
    $p = ServiceTargetGuard::PROFILES[$product];
    $config = ['dsn'=>$p['dsn'], 'profile'=>'synthetic-private-v1', 'environment'=>'staging',
        'role'=>$p['owner'], 'password_file'=>'/p20/bootstrap.password'];
    $expected = ['product'=>$product, 'database'=>$p['database'], 'port'=>5432, 'address'=>'172.28.0.2',
        'system_identifier'=>'1234567890', 'started'=>'1794858009', 'database_oid'=>'16384', 'directory'=>'/var/lib/postgresql/data'];
    $actual = $expected + ['role'=>$p['owner'], 'ssl'=>true, 'unsafe'=>true];
    if ($case === 'runtime-valid' || $case === 'runtime-unsafe') {
        $role = array_key_first($p['runtime']);
        $config['role'] = $actual['role'] = $role;
        $config['password_file'] = $p['runtime'][$role];
        $actual['unsafe'] = $case === 'runtime-unsafe';
    }
    $configCases = ['wrong-dsn'=>['dsn','pgsql:host=192.0.2.1;dbname=synthetic'],
        'agents-wrong-dsn'=>['dsn','pgsql:host=192.0.2.1;dbname=synthetic'],
        'wrong-profile'=>['profile','disabled'], 'production'=>['environment','production'],
        'wrong-role'=>['role','other'], 'wrong-password-file'=>['password_file','/synthetic/other']];
    if (isset($configCases[$case])) { [$k,$v]=$configCases[$case]; $config[$k]=$v; }
    if ($case === 'libpq-override') $config['libpq_overrides']=['PGHOSTADDR'=>'192.0.2.1'];
    $sqlCases = ['sql-database'=>['database','other'], 'agents-sql-database'=>['database','other'],
        'sql-address'=>['address','172.28.0.3'], 'sql-port'=>['port',5433],
        'sql-start'=>['started','1794858010'], 'sql-oid'=>['database_oid','999'],
        'sql-system-id'=>['system_identifier','999'], 'sql-directory'=>['directory','/other'],
        'sql-role'=>['role','other'], 'sql-no-tls'=>['ssl',false]];
    if (isset($sqlCases[$case])) { [$k,$v]=$sqlCases[$case]; $actual[$k]=$v; }
    $proof = ServiceTargetGuard::check($config, $expected,
        static function (string $host) use ($case): array {
            if ($case === 'dns-empty') return [];
            if ($case === 'dns-multiple') return ['172.28.0.2','172.28.0.3'];
            if ($case === 'dns-wrong') return ['192.0.2.1'];
            return ['172.28.0.2'];
        }, static function () use ($case, $actual, $sentinel): mixed {
            file_put_contents($sentinel.'.query', 'SYNTHETIC_QUERY');
            if ($case === 'sql-exception') throw new RuntimeException('SYNTHETIC_SECRET_MUST_NOT_LEAK');
            if ($case === 'sql-missing') return null;
            return $actual;
        });
    file_put_contents($sentinel, 'SYNTHETIC_WRITE_STAGE');
    return $proof;
});
