<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;

/**
 * F246: synthetic CRM-owned public-key lookup through a SELECT-only PostgreSQL
 * role and FORCE RLS. This class is CI-only and never grants CRM lead access.
 * Credentials/configuration are supplied by the disposable test harness.
 */
final readonly class AgentsPrivateRestrictedKeyringLab
{
    private const TABLE = 'public.aia_lab_service_keys_f246';
    private const ROLE = 'crm_aia_keyreader_f246_ci';

    public function __construct(private Connection $db)
    {
    }

    /** @return ?array{tenant_ref:string,trusted_keys:array<string,array{public_key:string,enabled:bool}>} */
    public function resolve(string $keyId, int $now): ?array
    {
        if (getenv('AIA_F246_RLS_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || $this->db->getDriverName() !== 'pgsql'
            || $this->db->getDatabaseName() !== 'imagine_crm_ci'
            || $this->db->getConfig('host') !== '127.0.0.1'
            || $this->db->getConfig('username') !== self::ROLE
            || $this->db->transactionLevel() !== 0
            || $now < 0 || $now > 4102444800
            || strlen($keyId) > 90
            || preg_match('/\ASYNTHETIC_[A-Z0-9_]{3,80}\z/D', $keyId) !== 1) {
            return null;
        }

        try {
            $identity = $this->db->selectOne(
                "SELECT current_database() AS database_name,
                    current_user AS username,
                    row_security_active('public.aia_lab_service_keys_f246'::regclass)
                    AS rls_active"
            );
            if (!$identity || $identity->database_name !== 'imagine_crm_ci'
                || $identity->username !== self::ROLE
                || !in_array($identity->rls_active, [true, 't', 1, '1'], true)) {
                return null;
            }

            // A verified service key id is only a lookup selector. The tenant
            // and key material are read from CRM-owned RLS-filtered rows.
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

            if (!$row || !is_string($row->tenant_ref)
                || strlen($row->tenant_ref) > 90
                || preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D', $row->tenant_ref) !== 1
                || !is_string($row->public_key_base64)
                || strlen($row->public_key_base64) !== 44
                || (int)$row->revision < 1) {
                return null;
            }
            $public = base64_decode($row->public_key_base64, true);
            if (!is_string($public)
                || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                || !hash_equals(base64_encode($public), $row->public_key_base64)) {
                return null;
            }
            return [
                'tenant_ref' => $row->tenant_ref,
                'trusted_keys' => [
                    $keyId => ['public_key' => $public, 'enabled' => true],
                ],
            ];
        } catch (\Throwable) {
            return null; // Database outage/role drift/RLS change always deny.
        }
    }
}
