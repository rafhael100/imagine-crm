<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use stdClass;
use Throwable;

/** Internal laboratory service. No routes, registration, enrollment or external delivery. */
final readonly class ExperimentalAuthenticator
{
    // Syntactically valid bcrypt work factor for the missing-account path; never a credential.
    private const DUMMY_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function __construct(
        private DatabaseSessionAuthority $registry,
        private SessionGuard $guard,
        private Session $session,
        private Hasher $hasher,
        private Encrypter $encrypter,
        private RateLimiter $limiter,
        private Totp $totp,
        private IamOperationRunner $security,
    ) {}

    public function begin(#[\SensitiveParameter] string $login, #[\SensitiveParameter] string $password): void
    {
        $this->security->run('password', fn () => $this->beginCore($login, $password), hash('sha256', strtolower(trim($login))));
    }

    private function beginCore(#[\SensitiveParameter] string $login, #[\SensitiveParameter] string $password): void
    {
        $this->logoutCore(); // Account changes never inherit tenant or prior authentication.
        $login = strtolower(trim($login));
        $key = 'crm.auth.password.'.hash('sha256', $login);
        $this->throttle($key);
        if (!preg_match('/\A[a-z0-9_.-]{3,160}\z/D', $login) || strlen($password) > 4096) {
            $this->deny();
        }
        $this->registry->transaction(function () use ($login, $password): void {
            $c = $this->registry->db->table('crm_auth_credentials')
                ->where('login_key', hash('sha256', $login))->lockForUpdate()->first();
            $passwordOk = $this->hasher->check($password, $c->password_hash ?? self::DUMMY_HASH);
            if (!$c || !$passwordOk || !$this->registry->validCredential($c)) {
                $this->deny();
            }
            $this->insertSession($c, 'pending', now()->getTimestamp() + config('identity.absolute_seconds'), null);
        });
    }

    public function completeMfa(#[\SensitiveParameter] string $code, bool $recovery = false): void
    {
        $this->security->run($recovery ? 'recovery' : 'mfa', fn () => $this->complete('crm.auth.pending', 'pending', $code, $recovery));
    }

    public function stepUp(#[\SensitiveParameter] string $code): void
    {
        $this->security->run('step_up', fn () => $this->stepUpCore($code));
    }

    private function stepUpCore(#[\SensitiveParameter] string $code): void
    {
        $this->identity();
        $this->complete('crm.auth.token', 'active', $code, false);
    }

    private function complete(string $key, string $state, #[\SensitiveParameter] string $code, bool $recovery): void
    {
        try {
            $this->registry->transaction(function () use ($key, $state, $code, $recovery): void {
                $hint = $this->peek($key);
                if (!$hint) {
                    $this->deny();
                }
                $this->throttle('crm.auth.mfa.'.$hint->identity_id);
                // Fixed lock order: credential -> session -> factor/recovery.
                $c = $this->registry->db->table('crm_auth_credentials')->where('identity_id', $hint->identity_id)->lockForUpdate()->first();
                $row = $this->registry->sessionRow($key);
                if (!$c || !$row || !$this->validRow($row, $c, $state)) {
                    $this->deny();
                }
                $this->security->changing();
                if (!$this->consumeFactor($c, $code, $recovery)) {
                    $this->deny();
                }
                $this->registry->db->table('crm_auth_sessions')->where('id', $row->id)->update(['state' => 'revoked']);
                $this->resetBrowser();
                $identity = GlobalIdentity::on('crm_iam')->findOrFail($c->identity_id);
                $this->guard->login($identity, false); // Never remember this device.
                $this->session->regenerateToken();
                $this->insertSession($c, 'active', (int) $row->absolute_expires_at, now()->getTimestamp());
                $this->limiter->clear('crm.auth.mfa.'.$c->identity_id);
                $this->limiter->clear('crm.auth.password.'.$c->login_key);
            });
        } catch (Throwable $e) {
            // Session storage and SQL are not one atomic resource. Partial failure fails closed.
            try {
                $this->logoutCore(); // Invalid challenge/reference cannot be replayed later.
            } finally {
                $this->resetBrowser();
            }
            throw $e;
        }
    }

    private function consumeFactor(stdClass $c, #[\SensitiveParameter] string $code, bool $recovery): bool
    {
        if (strlen($code) > 128) {
            return false;
        }
        if ($recovery) {
            $rows = $this->registry->db->table('crm_auth_recovery_codes')->where('identity_id', $c->identity_id)
                ->where('credential_version', $c->version)->whereNull('consumed_at')->lockForUpdate()->get();
            foreach ($rows as $row) {
                if ($this->hasher->check($code, $row->code_hash)) {
                    return $this->registry->db->table('crm_auth_recovery_codes')->where('id', $row->id)
                        ->whereNull('consumed_at')->update(['consumed_at' => now()->getTimestamp()]) === 1;
                }
            }
            return false;
        }
        $step = $this->totp->matchStep($this->encrypter->decryptString($c->factor_ciphertext), $code, now()->getTimestamp());
        if ($step === null || ($c->last_totp_step !== null && $step <= (int) $c->last_totp_step)) {
            return false;
        }
        $this->registry->db->table('crm_auth_credentials')->where('identity_id', $c->identity_id)->update(['last_totp_step' => $step]);

        return true;
    }

    public function refresh(): void
    {
        $this->security->run('refresh', fn () => $this->refreshCore());
    }

    private function refreshCore(): void
    {
        $id = $this->identity();
        $this->registry->transaction(function () use ($id): void {
            $c = $this->registry->db->table('crm_auth_credentials')->where('identity_id', $id)->lockForUpdate()->first();
            $row = $this->registry->sessionRow('crm.auth.token');
            if (!$c || !$row || !$this->validRow($row, $c, 'active') || !$row->mfa_verified_at) {
                $this->deny();
            }
            $this->security->changing();
            $this->registry->db->table('crm_auth_sessions')->where('id', $row->id)->update(['state' => 'revoked']);
            $this->resetBrowser();
            $this->guard->login(GlobalIdentity::on('crm_iam')->findOrFail($id), false);
            $this->session->regenerateToken();
            // Rotation never renews the absolute deadline or the MFA timestamp.
            $this->insertSession($c, 'active', (int) $row->absolute_expires_at, (int) $row->mfa_verified_at);
        });
    }

    public function sessions(): array
    {
        $id = $this->identity();

        return $this->registry->db->table('crm_auth_sessions')->where('identity_id', $id)->where('state', 'active')
            ->where('expires_at', '>', now()->getTimestamp())->where('absolute_expires_at', '>', now()->getTimestamp())
            ->get(['id', 'created_at', 'last_seen_at', 'expires_at', 'absolute_expires_at'])->map(fn ($row) => (array) $row)->all();
    }

    public function revoke(string $sessionId): void
    {
        $this->security->run('revoke', fn () => $this->revokeCore($sessionId));
    }

    private function revokeCore(string $sessionId): void
    {
        $id = $this->identity(true);
        if (!Str::isUuid($sessionId)) {
            $this->deny();
        }
        $this->registry->transaction(function () use ($id, $sessionId): void {
            $this->assertLockedActor($id);
            $this->security->changing();
            // Own sessions only. Another identity's UUID never grants authority.
            $this->registry->db->table('crm_auth_sessions')->where('identity_id', $id)->where('id', $sessionId)->update(['state' => 'revoked']);
        });
    }

    public function revokeAll(): void
    {
        $this->security->run('revoke_all', fn () => $this->revokeAllCore());
    }

    private function revokeAllCore(): void
    {
        $id = $this->identity(true);
        $this->registry->transaction(function () use ($id): void {
            $this->assertLockedActor($id);
            $this->security->changing();
            $this->registry->db->table('crm_auth_sessions')->where('identity_id', $id)->update(['state' => 'revoked']);
        });
        $this->resetBrowser();
    }

    public function logout(): void
    {
        $this->security->run('logout', fn () => $this->logoutCore());
    }

    private function logoutCore(): void
    {
        $this->security->changing();
        $this->registry->transaction(function (): void {
            foreach (['crm.auth.token', 'crm.auth.pending'] as $key) {
                $row = $this->registry->sessionRow($key);
                if ($row && hash_equals($row->binding, hash('sha256', $this->session->getId()))) {
                    $this->registry->db->table('crm_auth_sessions')->where('id', $row->id)->update(['state' => 'revoked']);
                }
            }
        });
        $this->resetBrowser();
    }

    private function identity(bool $recent = false): string
    {
        $user = $this->guard->user();
        if (!$user instanceof GlobalIdentity || !Str::isUuid($user->getAuthIdentifier())) {
            $this->deny();
        }
        $id = $user->getAuthIdentifier();
        $this->registry->current($id, $recent);

        return $id;
    }

    private function assertLockedActor(string $id): void
    {
        $credential = $this->registry->db->table('crm_auth_credentials')->where('identity_id', $id)->lockForUpdate()->first();
        $current = $this->registry->sessionRow('crm.auth.token');
        if (!$credential || !$current || !$this->validRow($current, $credential, 'active')
            || !$current->mfa_verified_at
            || (int) $current->mfa_verified_at + config('identity.step_up_seconds') <= now()->getTimestamp()) {
            $this->deny();
        }
    }

    private function validRow(stdClass $row, stdClass $c, string $state): bool
    {
        $now = now()->getTimestamp();

        return $this->registry->usable($row) && $this->registry->validCredential($c) && $row->identity_id === $c->identity_id
            && $row->state === $state && (int) $row->credential_version === (int) $c->version
            && (int) $row->expires_at > $now && (int) $row->absolute_expires_at > $now
            && hash_equals($row->binding, hash('sha256', $this->session->getId()));
    }

    private function peek(string $key): ?stdClass
    {
        $token = $this->session->get($key);
        if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
            return null;
        }

        return $this->registry->db->table('crm_auth_sessions')->where('token_hash', hash('sha256', $token))->first();
    }

    private function insertSession(stdClass $c, string $state, int $absolute, ?int $mfaAt): void
    {
        $token = bin2hex(random_bytes(32));
        $now = now()->getTimestamp();
        $this->registry->db->table('crm_auth_sessions')->insert([
            'id' => (string) Str::uuid(), 'token_hash' => hash('sha256', $token),
            'operation_id' => $this->security->id(),
            'identity_id' => $c->identity_id, 'state' => $state, 'credential_version' => $c->version,
            'binding' => hash('sha256', $this->session->getId()),
            'created_at' => $now, 'last_seen_at' => $now,
            'expires_at' => min($absolute, $now + config($state === 'pending' ? 'identity.pending_seconds' : 'identity.idle_seconds')),
            'absolute_expires_at' => $absolute, 'mfa_verified_at' => $mfaAt,
        ]);
        $this->session->put($state === 'pending' ? 'crm.auth.pending' : 'crm.auth.token', $token);
    }

    private function resetBrowser(): void
    {
        $this->guard->logout();
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    private function throttle(string $key): void
    {
        if ($this->limiter->tooManyAttempts($key, config('identity.attempts'))) {
            $this->deny();
        }
        $this->limiter->hit($key, config('identity.decay_seconds'));
    }

    private function deny(): never
    {
        throw new AuthenticationException('CRM authentication required.');
    }
}
