<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/** Durable intent -> SQL -> persisted browser session -> audit/finalize. Not XA. */
final readonly class IamOperationRunner
{
    public function __construct(
        private DatabaseSessionAuthority $registry,
        private IamOperationContext $context,
        private Session $session,
        private SessionGuard $guard,
        private IamAudit $audit,
        private IamAbuseGuard $abuse,
        private TrustedPeerOrigin $origins,
    ) {}

    public function id(): string
    {
        return $this->context->id ?? throw new LogicException('IAM operation required.');
    }

    public function changing(): void { $this->context->changed = true; }

    public function run(string $action, Closure $operation, ?string $loginKey = null): mixed
    {
        if ($this->context->id !== null || !in_array($action, IamAudit::ACTIONS, true)) {
            throw new LogicException('Nested IAM operation forbidden.');
        }
        $id = (string) Str::uuid();
        $subject = null;
        $origin = null;
        $admitted = false;
        $intent = false;
        $identityKey = $loginKey ?? 'anonymous';
        $this->context->id = $id;
        $this->context->changed = false;
        try {
            $this->registry->transaction(function () use ($id, $action, $loginKey, &$subject): void {
                $db = $this->registry->db;
                $hashes = [];
                foreach (['crm.auth.token', 'crm.auth.pending'] as $key) {
                    $raw = $this->session->get($key);
                    if (is_string($raw) && preg_match('/\A[a-f0-9]{64}\z/D', $raw)) { $hashes[] = hash('sha256', $raw); }
                }
                $rows = $db->table('crm_auth_sessions')->whereIn('token_hash', $hashes)
                    ->where('binding', hash('sha256', $this->session->getId()))->get();
                $subjects = $rows->pluck('identity_id')->unique()->sort()->values()->all();
                $db->table('crm_auth_credentials')->whereIn('identity_id', $subjects)->orderBy('identity_id')->lockForUpdate()->get();
                $subject = $loginKey !== null
                    ? $db->table('crm_auth_credentials')->where('login_key', $loginKey)->value('identity_id')
                    : ($subjects[0] ?? null);
                $db->table('crm_auth_operations')->insert([
                    'id' => $id, 'action' => $action, 'subject_id' => $subject,
                    'state' => 'prepared', 'created_at' => now()->getTimestamp(),
                ]);
                foreach ($rows as $hint) {
                    $row = $db->table('crm_auth_sessions')->where('id', $hint->id)->lockForUpdate()->first();
                    // Never overwrite another unresolved hold; that would erase a failure barrier.
                    if ($row->security_hold !== null) { throw new AuthenticationException('CRM authentication required.'); }
                    $db->table('crm_auth_sessions')->where('id', $row->id)->update(['security_hold' => $id]);
                }
                $this->audit->append($db, $id, $action, 'attempted', null, $subject);
            });
            $intent = true;
            $origin = $this->origins->key();
            $identityKey = $loginKey ?? $subject ?? 'anonymous';
            // Logout never needs cache availability to revoke a known session.
            if ($action !== 'logout') { $this->abuse->admit($action, $identityKey, $origin); $admitted = true; }
            $result = $operation();
            $this->session->save(); // Explicit failure boundary, before registry can become usable.
            if (!$this->session instanceof \Illuminate\Session\Store) { throw new LogicException('Verifiable server session required.'); }
            $stored = $this->session->getHandler()->read($this->session->getId());
            if (!is_string($stored) || $stored === '') { throw new LogicException('Server session persistence not confirmed.'); }
            $this->registry->transaction(function () use ($id, $action, $subject, $origin): void {
                $db = $this->registry->db;
                $actor = in_array($action, ['password', 'logout'], true) ? null : $subject;
                $this->audit->append($db, $id, $action, 'succeeded', $actor, $subject, $origin);
                $db->table('crm_auth_sessions')->where('security_hold', $id)->update(['security_hold' => null]);
                $db->table('crm_auth_operations')->where('id', $id)->update(['state' => 'completed', 'actor_id' => $actor]);
            });
            return $result;
        } catch (Throwable) {
            $preserve = !$this->context->changed;
            $compensated = false;
            if ($intent) {
                try {
                    $this->registry->transaction(function () use ($id, $action, $subject, $origin, $preserve): void {
                        $db = $this->registry->db;
                        $db->table('crm_auth_sessions')->where(function ($q) use ($id): void {
                            $q->where('operation_id', $id)->orWhere('security_hold', $id);
                        })->update($preserve ? ['security_hold' => null] : ['state' => 'revoked', 'security_hold' => null]);
                        $this->audit->append($db, $id, $action, 'denied', null, $subject, $origin);
                        $this->audit->append($db, $id, $action, 'compensated', null, $subject, $origin);
                        $db->table('crm_auth_operations')->where('id', $id)->update(['state' => 'compensated']);
                    });
                    $compensated = true;
                } catch (Throwable) {
                    // Intent/holds survive. Reconciliation may only revoke, never activate.
                }
            }
            if (!$preserve || !$compensated) {
                // Even when storage is broken, no cached guard supplies authority.
                $this->guard->forgetUser();
                try { $this->session->invalidate(); $this->session->regenerateToken(); $this->session->save(); } catch (Throwable) {}
            }
            if ($admitted) { try { $this->abuse->failed($action, $identityKey, $origin); } catch (Throwable) {} }
            throw new AuthenticationException('CRM authentication required.');
        } finally {
            $this->context->id = null;
            $this->context->changed = false;
        }
    }
}
