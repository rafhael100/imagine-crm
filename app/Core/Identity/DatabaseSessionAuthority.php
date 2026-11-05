<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Connection;
use LogicException;
use stdClass;

/** Candidate global IAM registry. Business connections must never resolve it. */
final readonly class DatabaseSessionAuthority implements SessionAuthority
{
    public function __construct(public Connection $db, private Session $session) {}

    public function transaction(Closure $operation): mixed
    {
        if (!config('identity.experimental') || !app()->environment('local', 'testing')
            || $this->db->transactionLevel() !== 0) {
            throw new LogicException('Isolated IAM transaction required.');
        }
        if ($this->db->getDriverName() === 'pgsql') {
            $role = $this->db->selectOne("SELECT r.rolsuper, r.rolbypassrls,
                pg_has_role(current_user, c.relowner, 'MEMBER') AS owns_table
                FROM pg_roles r CROSS JOIN pg_class c
                WHERE r.rolname = current_user AND c.oid = 'crm_auth_credentials'::regclass");
            if (!$role || $role->rolsuper || $role->rolbypassrls || $role->owns_table) {
                throw new LogicException('Unsafe IAM runtime role.');
            }
        } elseif ($this->db->getDriverName() !== 'sqlite' || !app()->environment('testing')) {
            throw new LogicException('PostgreSQL required for IAM runtime.');
        }

        return $this->db->transaction($operation, 1);
    }

    public function assertCurrent(string $identityId): void
    {
        $this->current($identityId);
    }

    public function current(string $identityId, bool $recent = false): stdClass
    {
        return $this->transaction(function () use ($identityId, $recent): stdClass {
            $credential = $this->db->table('crm_auth_credentials')->where('identity_id', $identityId)->lockForUpdate()->first();
            $row = $this->sessionRow('crm.auth.token');
            $now = now()->getTimestamp();
            if (!$credential || !$this->validCredential($credential) || !$row
                || $row->identity_id !== $identityId || $row->state !== 'active'
                || (int) $row->credential_version !== (int) $credential->version
                || !hash_equals($row->binding, hash('sha256', $this->session->getId()))
                || (int) $row->expires_at <= $now || (int) $row->absolute_expires_at <= $now
                || !$row->mfa_verified_at || (int) $row->created_at > $now
                || ($recent && (int) $row->mfa_verified_at + config('identity.step_up_seconds') <= $now)) {
                throw new AuthenticationException('CRM authentication required.');
            }
            $this->db->table('crm_auth_sessions')->where('id', $row->id)->update([
                'last_seen_at' => $now,
                'expires_at' => min($now + config('identity.idle_seconds'), (int) $row->absolute_expires_at),
            ]);

            return $row;
        });
    }

    /** Internal registry lookup, always called inside an IAM transaction. */
    public function sessionRow(string $key): ?stdClass
    {
        $token = $this->session->get($key);
        if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
            return null;
        }

        return $this->db->table('crm_auth_sessions')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
    }

    public function validCredential(stdClass $credential): bool
    {
        return $credential->status === 'active' && (int) $credential->verified_at > 0
            && (int) $credential->version > 0 && (int) $credential->factor_confirmed_at > 0
            && is_string($credential->factor_ciphertext) && $credential->factor_ciphertext !== '';
    }
}
