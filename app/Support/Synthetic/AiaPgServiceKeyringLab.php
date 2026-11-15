<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;

/**
 * F232b: fetch PUBLIC Ed25519 trust anchors from the CRM-owned disposable
 * PostgreSQL fixture instead of accepting a key passed by the caller.
 *
 * This is not an operational key registry, credential vault or authorization.
 * It is gated to the existing exact GitHub PostgreSQL16 CI test identity.
 */
final readonly class AiaPgServiceKeyringLab
{
    private const TABLE = 'public.aia_lab_ed25519_service_keys';
    private const ISSUER = 'SYNTHETIC_AGENT_SERVICE';
    private const AUDIENCE = 'SYNTHETIC_CRM_API';

    public function __construct(private Connection $connection)
    {
    }

    /**
     * Key-id and tenant are lookup selectors, never evidence of authority.
     * The key bytes come EXCLUSIVELY from server-side PG rows.
     *
     * @return array<string,array{public_key:string,enabled:bool}>
     */
    public function trustedPublicKey(string $keyId, string $expectedTenant): array
    {
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
            || !self::validSynthetic($keyId, 3)
            || !self::validSynthetic($expectedTenant, 1)) {
            return [];
        }

        try {
            $who = $this->connection->selectOne(
                'SELECT current_database() AS dbname, current_user AS username'
            );
            if (!$who || $who->dbname !== 'imagine_crm_ci'
                || $who->username !== 'postgres') {
                return [];
            }

            $row = $this->connection->table(self::TABLE)
                ->where('issuer', self::ISSUER)
                ->where('audience', self::AUDIENCE)
                ->where('tenant_ref', $expectedTenant)
                ->where('key_id', $keyId)
                ->first(['public_key_base64','enabled','revoked_at','revision']);

            if (!$row || !$row->enabled || $row->revoked_at !== null
                || (int) $row->revision < 1
                || !is_string($row->public_key_base64)
                || strlen($row->public_key_base64) !== 44) {
                return [];
            }
            $bytes = base64_decode($row->public_key_base64, true);
            if (!is_string($bytes)
                || strlen($bytes) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                || base64_encode($bytes) !== $row->public_key_base64) {
                return [];
            }

            return [$keyId => ['public_key' => $bytes, 'enabled' => true]];
        } catch (\Throwable) {
            return []; // revoked, absent or unreachable authority: deny
        }
    }

    private static function validSynthetic(string $ref, int $minLength): bool
    {
        return strlen($ref) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{'.$minLength.',80}\z/D', $ref) === 1;
    }
}
