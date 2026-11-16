<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;

/**
 * F245: CRM-owned synthetic verification-key lookup in disposable PG16 CI.
 *
 * The key id in signed claims is ONLY a selector. The CRM fixture database
 * supplies the trusted public key AND tenant. This is not identity provision,
 * user membership, production IAM, RLS, or authorization to read CRM records.
 * No routes, secrets, migrations or application container bindings exist.
 */
final readonly class AgentsPrivateOwnedKeyringLab
{
    private const TABLE = 'public.aia_lab_service_keys_f245';

    public function __construct(private Connection $db)
    {
    }

    /**
     * @return ?array{tenant_ref:string,trusted_keys:array<string,array{public_key:string,enabled:bool}>}
     * A matching synthetic key is never an ALLOW decision.
     */
    public function resolve(string $keyId, int $now): ?array
    {
        if (getenv('AIA_F245_KEYRING_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || $this->db->getDriverName() !== 'pgsql'
            || $this->db->getDatabaseName() !== 'imagine_crm_ci'
            || $this->db->getConfig('host') !== '127.0.0.1'
            || $this->db->getConfig('username') !== 'postgres'
            || $this->db->transactionLevel() !== 0
            || $now < 0 || $now > 4102444800
            || !self::synthetic($keyId)) {
            return null;
        }

        try {
            $who = $this->db->selectOne(
                'SELECT current_database() AS dbname, current_user AS username'
            );
            if (!$who || $who->dbname !== 'imagine_crm_ci'
                || $who->username !== 'postgres') {
                return null;
            }
            $row = $this->db->table(self::TABLE)
                ->where('key_id', $keyId)
                ->where('issuer', 'SYNTHETIC_AGENT_SERVICE')
                ->where('audience', 'SYNTHETIC_CRM_API')
                ->where('subject', 'SYNTHETIC_AGENTS_READ_ONLY')
                ->where('enabled', true)
                ->whereNull('revoked_at')
                ->where('not_before', '<=', $now)
                ->where('expires_at', '>', $now)
                ->where('revision', '>=', 1)
                ->first(['tenant_ref', 'public_key_base64', 'revision']);
            if (!$row
                || !is_string($row->tenant_ref)
                || !self::synthetic($row->tenant_ref)
                || !is_string($row->public_key_base64)
                || strlen($row->public_key_base64) !== 44
                || (int)$row->revision < 1) {
                return null;
            }

            $key = base64_decode($row->public_key_base64, true);
            if (!is_string($key)
                || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                || !hash_equals(base64_encode($key), $row->public_key_base64)) {
                return null;
            }

            return [
                'tenant_ref' => $row->tenant_ref,
                'trusted_keys' => [
                    $keyId => ['public_key' => $key, 'enabled' => true],
                ],
            ];
        } catch (\Throwable) {
            return null; // Absent, revoked or unavailable registry denies.
        }
    }

    private static function synthetic(string $ref): bool
    {
        return strlen($ref) <= 90
            && preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D', $ref) === 1;
    }
}
