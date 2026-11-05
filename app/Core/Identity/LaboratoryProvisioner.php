<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;

/** No public route/command. Mandates are seeded only by trusted synthetic setup. */
final readonly class LaboratoryProvisioner
{
    public function __construct(
        private GuardPrincipalSource $actors,
        private DatabaseSessionAuthority $registry,
        private Hasher $hasher,
        private Encrypter $encrypter,
        private IamAudit $audit,
        private Totp $totp,
        private IamAbuseGuard $abuse,
        private TrustedPeerOrigin $origins,
    ) {}

    public function credential(string $mandateId, string $operationId, string $target, string $login,
        #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $factor,
        #[\SensitiveParameter] string $proof, int $expectedVersion = 0): array
    {
        if ($expectedVersion < 0) { $this->deny(); }
        $action = $expectedVersion === 0 ? 'provision' : 'rotate';
        return $this->authorized($mandateId, $operationId, $target, $action, function () use ($target, $login, $password, $factor, $proof, $expectedVersion): array {
            if (!preg_match('/\Alab-[a-z0-9_.-]{1,150}\z/D', $login) || strlen($password) < 12 || strlen($password) > 4096) { $this->deny(); }
            $step = $this->totp->matchStep($factor, $proof, now()->getTimestamp());
            if ($step === null) { $this->deny(); }
            $db = $this->registry->db;
            $existing = $db->table('crm_auth_credentials')->where('identity_id', $target)->lockForUpdate()->first();
            if (($expectedVersion === 0 && $existing) || ($expectedVersion > 0 && (!$existing || (int) $existing->version !== $expectedVersion))) { $this->deny(); }
            $version = $expectedVersion + 1;
            $values = [
                'login_key' => hash('sha256', $login), 'password_hash' => $this->hasher->make($password),
                'status' => 'active', 'verified_at' => now()->getTimestamp(), 'version' => $version,
                'factor_ciphertext' => $this->encrypter->encryptString($factor),
                'factor_confirmed_at' => now()->getTimestamp(), 'last_totp_step' => $step,
            ];
            if ($existing) {
                $db->table('crm_auth_credentials')->where('identity_id', $target)->update($values);
                $db->table('crm_auth_sessions')->where('identity_id', $target)->update(['state' => 'revoked']);
            } else {
                if ($db->table('crm_global_identities')->where('id', $target)->exists()) { $this->deny(); }
                $db->table('crm_global_identities')->insert(['id' => $target]);
                $db->table('crm_auth_credentials')->insert(['identity_id' => $target] + $values);
            }
            return ['version' => $version, 'recovery_codes' => $this->codes($target, $version)];
        });
    }

    public function recovery(string $mandateId, string $operationId, string $target, int $expectedVersion): array
    {
        return $this->authorized($mandateId, $operationId, $target, 'issue_recovery', function () use ($target, $expectedVersion): array {
            $db = $this->registry->db;
            $c = $db->table('crm_auth_credentials')->where('identity_id', $target)->lockForUpdate()->first();
            if (!$c || !$this->registry->validCredential($c) || (int) $c->version !== $expectedVersion) { $this->deny(); }
            $version = $expectedVersion + 1;
            $db->table('crm_auth_credentials')->where('identity_id', $target)->update(['version' => $version]);
            $db->table('crm_auth_sessions')->where('identity_id', $target)->update(['state' => 'revoked']);
            return ['version' => $version, 'recovery_codes' => $this->codes($target, $version)];
        });
    }

    public function reconcile(string $mandateId, string $requestId, string $target, string $operationId): array
    {
        return $this->authorized($mandateId, $requestId, $target, 'reconcile', function () use ($target, $operationId): array {
            $db = $this->registry->db;
            $db->table('crm_auth_credentials')->where('identity_id', $target)->lockForUpdate()->first();
            $op = $db->table('crm_auth_operations')->where('id', $operationId)->lockForUpdate()->first();
            if (!$op || $op->state === 'completed') { $this->deny(); }
            $related = $db->table('crm_auth_sessions')->where('identity_id', $target)->where(function ($q) use ($operationId): void {
                $q->where('operation_id', $operationId)->orWhere('security_hold', $operationId);
            })->exists();
            if ($op->subject_id !== $target && !$related) { $this->deny(); }
            $db->table('crm_auth_sessions')->where('identity_id', $target)->where(function ($q) use ($operationId): void {
                $q->where('operation_id', $operationId)->orWhere('security_hold', $operationId);
            })->update(['state' => 'revoked', 'security_hold' => null]);
            $db->table('crm_auth_operations')->where('id', $operationId)->update(['state' => 'compensated']);
            return ['reconciled_operation' => $operationId];
        });
    }

    private function authorized(string $mandateId, string $operationId, string $target, string $action, Closure $operation): array
    {
        if (!Str::isUuid($mandateId) || !Str::isUuid($operationId) || !Str::isUuid($target)) { $this->deny(); }
        $verifiedActor = null;
        try {
        return $this->actors->withinCurrent(function (PrincipalReference $actor) use ($mandateId, $operationId, $target, $action, $operation, &$verifiedActor): array {
            $verifiedActor = $actor->principalId;
            $this->abuse->admit($action, $actor->principalId, $this->origins->key());
            // Self-grant/recovery is not a laboratory administrative authority.
            if ($actor->principalId === $target) { $this->deny(); }
            return $this->registry->transaction(function () use ($actor, $mandateId, $operationId, $target, $action, $operation): array {
                $db = $this->registry->db;
                $mandate = $db->table('crm_auth_mandates')->where('id', $mandateId)->lockForUpdate()->first();
                $version = $db->table('crm_auth_credentials')->where('identity_id', $actor->principalId)->value('version');
                if (!$mandate || $mandate->actor_id !== $actor->principalId || $mandate->subject_id !== $target
                    || $mandate->action !== $action || $mandate->revoked_at !== null
                    || (int) $mandate->expires_at <= now()->getTimestamp() || (int) $mandate->actor_version !== (int) $version) { $this->deny(); }
                $prior = $db->table('crm_auth_operations')->where('id', $operationId)->lockForUpdate()->first();
                if ($prior) {
                    if ($prior->state !== 'completed' || $prior->actor_id !== $actor->principalId
                        || $prior->subject_id !== $target || $prior->action !== $action || $prior->mandate_id !== $mandateId) { $this->deny(); }
                    // Retries report completion; they never regenerate/reveal a secret.
                    return ['status' => 'already_completed'];
                }
                $db->table('crm_auth_operations')->insert([
                    'id' => $operationId, 'action' => $action, 'actor_id' => $actor->principalId,
                    'subject_id' => $target, 'mandate_id' => $mandateId, 'state' => 'prepared', 'created_at' => now()->getTimestamp(),
                ]);
                $this->audit->append($db, $operationId, $action, 'attempted', $actor->principalId, $target);
                $result = $operation();
                $this->audit->append($db, $operationId, $action, 'succeeded', $actor->principalId, $target);
                if (isset($result['recovery_codes'])) { $this->audit->append($db, $operationId, 'issue_recovery', 'succeeded', $actor->principalId, $target); }
                $db->table('crm_auth_operations')->where('id', $operationId)->update(['state' => 'completed']);
                return ['status' => 'completed'] + $result;
            });
        }, true); // Real current session + recent MFA, not an actor/role field from input.
        } catch (\Throwable) {
            // A denial is separate evidence after rollback, never a partial provision.
            try { $this->registry->transaction(fn () => $this->audit->append($this->registry->db, $operationId, $action, 'denied', $verifiedActor, $target)); } catch (\Throwable) {}
            throw new AuthenticationException('CRM authentication required.');
        }
    }

    private function codes(string $target, int $version): array
    {
        $codes = [];
        for ($i = 0; $i < 6; $i++) {
            $code = bin2hex(random_bytes(24));
            $this->registry->db->table('crm_auth_recovery_codes')->insert([
                'id' => (string) Str::uuid(), 'identity_id' => $target,
                'code_hash' => $this->hasher->make($code), 'credential_version' => $version,
            ]);
            $codes[] = $code;
        }
        return $codes; // Transient trusted test harness only, never a log or persisted result.
    }

    private function deny(): never { throw new AuthenticationException('CRM authentication required.'); }
}
