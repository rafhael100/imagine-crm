<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;

/**
 * TEST-ONLY adapter for the throwaway PostgreSQL nonce claim experiment.
 *
 * Not a service authenticator, permission source or production nonce store.
 * It always denies unless the exact GitHub CI laboratory identity matches.
 * No service-container binding, migration, route or injected real credential.
 */
final class AiaPgNonceCandidateLedger
{
    private const TABLE = 'public.aia_lab_nonce_atomic_probe';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function claim(
        string $nonce,
        string $tenantRef,
        int $claimedAt,
    ): bool {
        // The gate also runs before any DB query and deliberately cannot
        // be configured for a different environment or actual customer DB.
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || $this->connection->getDriverName() !== 'pgsql'
            || $this->connection->getDatabaseName() !== 'imagine_crm_ci'
            || $this->connection->getConfig('host') !== '127.0.0.1'
            || $this->connection->getConfig('username') !== 'postgres'
            || $claimedAt < 0 || $claimedAt > 4102444800
            || strlen($nonce) > 90 || strlen($tenantRef) > 90
            || preg_match('/\ASYNTHETIC_[A-Z0-9_]{4,80}\z/D', $nonce) !== 1
            || preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D', $tenantRef) !== 1) {
            return false;
        }

        try {
            $identity = $this->connection->selectOne(
                'SELECT current_database() AS dbname, current_user AS username',
            );
            if ($identity === null || $identity->dbname !== 'imagine_crm_ci'
                || $identity->username !== 'postgres') {
                return false;
            }

            // A plain, one-statement, unique-key insert. Any backend error
            // must DENY instead of assuming the nonce is unused.
            return $this->connection->affectingStatement(
                'INSERT INTO '.self::TABLE.' (
                    issuer,audience,nonce,tenant_ref,claimed_at
                 ) VALUES (?,?,?,?,?)
                 ON CONFLICT (issuer,audience,nonce) DO NOTHING',
                [
                    'SYNTHETIC_AGENT_SERVICE', 'SYNTHETIC_CRM_API',
                    $nonce, $tenantRef, $claimedAt,
                ],
            ) === 1;
        } catch (\Throwable) {
            return false;
        }
    }
}
