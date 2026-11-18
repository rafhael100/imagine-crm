<?php
declare(strict_types=1);
use PortalTestTools\P20\DisposablePreparation as Gate;
require '/var/www/html/tools/p20/DisposablePreparation.php';
Gate::entry(static function (): array {
    require '/var/www/html/vendor/autoload.php';
    $app=require '/var/www/html/bootstrap/app.php';
    (new Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables)->bootstrap($app);
    (new Illuminate\Foundation\Bootstrap\LoadConfiguration)->bootstrap($app);
    $snapshot=static fn(): array => ['environment'=>$app->environment(),'default'=>$app['config']->get('database.default'),
        'connection'=>$app['config']->get('database.connections.pgsql'),'ci'=>getenv('CI'),'p20'=>getenv('P20_AUTHORITY_PG_CI')];
    $before=$snapshot(); Gate::configuration($before,55432);
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Gate::require($snapshot()===$before);
    $db=null; $pdo=null;
    return Gate::run($snapshot,
        static fn(): array => Gate::localEvidence('/tmp/p20-cluster','/usr/lib/postgresql/18/bin/pg_controldata',55432),
        static function () use ($app,$snapshot,&$db,&$pdo): array {
            $db=$app['db']->connection('pgsql');
            $effective=$snapshot();$effective['connection']=array_diff_key($db->getConfig(),['name'=>true]);
            Gate::require(Gate::configuration($effective,55432)===Gate::configuration($snapshot(),55432));
            $db->setReconnector(static function (): never { throw new RuntimeException('P20_RECONNECT_FORBIDDEN'); });
            $pdo=$db->getPdo();
            return (array)$db->selectOne("SELECT current_database() AS database,inet_server_addr()::text AS address,inet_server_port() AS port,
                current_setting('data_directory') AS directory,floor(extract(epoch FROM pg_postmaster_start_time()))::bigint AS started,
                system_identifier::text FROM pg_control_system()",[],false);
        },
        static function (array $proof) use ($app,&$db,&$pdo): int {
            Gate::require($app['db']->connection('pgsql')===$db && $db->getPdo()===$pdo);
            // Exact existing CI fixture body; execute with the already verified $db, without its second bootstrap.
            $fixture=file_get_contents('/var/www/html/tests/Fixtures/p20/authority-role.php');
            Gate::require(hash('sha256',$fixture)==='a15f9978c7e4084c7529a69c43bda7eeef5d04254bfdd78480e96454764c5a62');
            $marker='$db=Illuminate\\Support\\Facades\\DB::connection();';
            Gate::require(substr_count($fixture,$marker)===1);
            eval(substr($fixture,strpos($fixture,$marker)+strlen($marker)));
            return 0;
        },55432);
});
