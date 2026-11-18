<?php
declare(strict_types=1);

/** CI bootstrap guard for the existing CRM/Agents PDO DSNs, not Portal or product authorization. */
final class ServiceTargetGuard
{
    public const PROFILES = [
        'crm' => ['dsn'=>'pgsql:host=crm-db;dbname=imagine_crm_private_homologation;sslmode=verify-full;sslrootcert=/certs/ca.crt',
            'host'=>'crm-db', 'database'=>'imagine_crm_private_homologation', 'owner'=>'crm_homologation_owner',
            'runtime'=>['crm_auto_transport'=>'/certs/db.password', 'crm_auto_sender'=>'/certs/sender.password']],
        'agents' => ['dsn'=>'pgsql:host=agents-db;dbname=imagine_agents_ci;sslmode=verify-full;sslrootcert=/certs/ca.crt',
            'host'=>'agents-db', 'database'=>'imagine_agents_ci', 'owner'=>'agents_ci_owner',
            'runtime'=>['aia_private_transport'=>'/certs/agents-db.password']],
    ];
    public static function require(bool $ok): void
    {
        if (!$ok) throw new RuntimeException('SERVICE_TARGET_REJECTED');
    }
    public static function check(array $config, array $expected, Closure $resolve, Closure $query): array
    {
        $product = $expected['product'] ?? '';
        self::require(isset(self::PROFILES[$product]));
        $p = self::PROFILES[$product];
        self::require(($config['libpq_overrides'] ?? []) === []);
        self::require(($config['dsn'] ?? null) === $p['dsn']);
        self::require(($config['profile'] ?? null) === 'synthetic-private-v1');
        if ($product === 'crm') self::require(($config['environment'] ?? null) === 'staging');
        self::require(($expected['database'] ?? null) === $p['database']);
        self::require(($expected['port'] ?? null) === 5432);
        self::require(is_string($expected['address'] ?? null) && filter_var($expected['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false);
        self::require(is_string($expected['system_identifier'] ?? null) && ctype_digit($expected['system_identifier']));
        self::require(is_string($expected['started'] ?? null) && ctype_digit($expected['started']));
        self::require(is_string($expected['database_oid'] ?? null) && ctype_digit($expected['database_oid']));
        self::require(($expected['directory'] ?? null) === '/var/lib/postgresql/data');
        $role = $config['role'] ?? '';
        self::require($role === $p['owner'] || isset($p['runtime'][$role]));
        self::require(($config['password_file'] ?? null) === ($role === $p['owner'] ? '/p20/bootstrap.password' : $p['runtime'][$role]));
        // Never attempt PDO until the effective DSN and Docker-attested single address match.
        self::require($resolve($p['host']) === [$expected['address']]);
        $actual = $query($config, $role === $p['owner']);
        self::require(is_array($actual));
        foreach (['database','address','port','started','database_oid'] as $key) {
            self::require(isset($actual[$key]) && (string)$actual[$key] === (string)$expected[$key]);
        }
        self::require(($actual['role'] ?? null) === $role && ($actual['ssl'] ?? null) === true);
        if ($role === $p['owner']) {
            foreach (['directory','system_identifier'] as $key) self::require(($actual[$key] ?? null) === $expected[$key]);
        } else {
            self::require(($actual['unsafe'] ?? null) === false);
        }
        return ['schema'=>'P20_SERVICE_TARGET_V1','product'=>$product,'role'=>$role,
            'database'=>$expected['database'],'system_identifier'=>$expected['system_identifier'],
            'cluster_proof'=>$role === $p['owner'] ? 'SQL_CONTROL_SYSTEM' : 'MATCHED_PREPARATION_ADDRESS_DB_OID_START'];
    }
    public static function entry(Closure $main): never
    {
        $completed = false;
        register_shutdown_function(static function () use (&$completed): void {
            if (!$completed) { fwrite(STDERR, "P20_SERVICE_TARGET_INCOMPLETE\n"); exit(1); }
        });
        try {
            $proof = $main();
            self::require(is_array($proof) && ($proof['schema'] ?? '') === 'P20_SERVICE_TARGET_V1');
            fwrite(STDERR, json_encode($proof, JSON_THROW_ON_ERROR)."\n");
            $completed = true;
            exit(0);
        } catch (Throwable $error) {
            // SQLSTATE/location only, never SQL text, credentials or exception messages.
            $state = $error instanceof PDOException && preg_match('/^[A-Z0-9]{5}$/', (string)$error->getCode()) ? (string)$error->getCode() : 'NONE';
            fwrite(STDERR, "P20_SERVICE_TARGET_DENIED state=".$state." file=".basename($error->getFile())." line=".$error->getLine()."\n");
            exit(1);
        }
    }
}
