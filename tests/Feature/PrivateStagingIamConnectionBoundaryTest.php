<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\DatabaseSessionAuthority;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Connection;
use LogicException;
use Mockery;
use Tests\TestCase;

/** Negative connection-substitution checks; no real staging database is created. */
final class PrivateStagingIamConnectionBoundaryTest extends TestCase
{
    public function test_configuration_cannot_disguise_another_physical_database_or_role(): void
    {
        $this->app->detectEnvironment(fn () => 'staging');
        config(['identity.experimental' => true,
            'identity.private_staging_profile' => 'private-homologation-synthetic-v1',
            'identity.abuse_store' => 'redis', 'session.driver' => 'redis',
            'app.debug' => false, 'app.url' => 'https://crm-dev.imaginecomunica.com.br',
            'database.default' => 'pgsql']);
        $connection = ['driver' => 'pgsql', 'database' => 'imagine_crm_private_homologation',
            'host' => 'postgres', 'port' => 5432, 'url' => null];
        config(['database.connections.pgsql' => $connection + ['username' => 'crm_homologation_app', 'password' => str_repeat('A', 32)],
            'database.connections.crm_iam' => $connection + ['username' => 'crm_homologation_iam', 'password' => str_repeat('B', 32)]]);

        foreach ([['imagine_crm_ci', 'crm_homologation_iam'],
            ['imagine_crm_private_homologation', 'crm_iam_ci'],
            ['customer_database', 'postgres']] as [$database, $role]) {
            $db = Mockery::mock(Connection::class);
            $db->shouldReceive('transactionLevel')->once()->andReturn(0);
            $db->shouldReceive('getDriverName')->once()->andReturn('pgsql');
            $db->shouldReceive('selectOne')->once()
                ->with('SELECT current_database() AS database_name, current_user AS role_name')
                ->andReturn((object) ['database_name' => $database, 'role_name' => $role]);
            $db->shouldNotReceive('transaction');
            $session = Mockery::mock(Session::class);
            $called = false;
            try {
                (new DatabaseSessionAuthority($db, $session))->transaction(function () use (&$called): void {
                    $called = true;
                });
                self::fail('Mismatched physical connection was admitted.');
            } catch (LogicException $error) {
                self::assertSame('Private staging IAM connection mismatch.', $error->getMessage());
            }
            self::assertFalse($called);
        }
    }
}
