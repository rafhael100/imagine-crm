<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository;
use Throwable;

/** Fixed-window budgets + failure backoff. Redis is explicit; no failover store. */
final readonly class IamAbuseGuard
{
    public function __construct(private Repository $cache) {}

    public function admit(string $action, string $identityKey, string $origin): void
    {
        if (config('identity.abuse_window') < 1 || config('identity.abuse_identity_limit') < 1
            || config('identity.abuse_origin_limit') < 1 || config('identity.abuse_backoff') < 0) { $this->deny(); }
        $this->atomic(function () use ($action, $identityKey, $origin): void {
            $keys = $this->keys($action, $identityKey, $origin);
            $now = now()->getTimestamp();
            foreach ($keys as $key => $limit) {
                $value = $this->cache->get($key, ['count' => 0, 'until' => $now, 'next' => 0]);
                $this->validate($value);
                if ($value['until'] > $now && ($value['count'] >= $limit || $value['next'] > $now)) {
                    $this->deny();
                }
            }
            foreach ($keys as $key => $limit) {
                $value = $this->cache->get($key, ['count' => 0, 'until' => $now, 'next' => 0]);
                $this->validate($value);
                if ($value['until'] <= $now) { $value = ['count' => 0, 'until' => $now + config('identity.abuse_window'), 'next' => 0]; }
                $value['count']++;
                if (!$this->cache->put($key, $value, max(1, $value['until'] - $now))) { $this->deny(); }
            }
        });
    }

    public function failed(string $action, string $identityKey, string $origin): void
    {
        $this->atomic(function () use ($action, $identityKey, $origin): void {
            foreach ($this->keys($action, $identityKey, $origin) as $key => $limit) {
                $value = $this->cache->get($key);
                $this->validate($value); // Missing budget during a failure is not a free retry.
                $delay = min(60, (int) config('identity.abuse_backoff') * (2 ** min(6, $value['count'] - 1)));
                $value['next'] = now()->getTimestamp() + $delay;
                if (!$this->cache->put($key, $value, max(1, $value['until'] - now()->getTimestamp()))) { $this->deny(); }
            }
        });
    }

    private function keys(string $action, string $identity, string $origin): array
    {
        // Origin shared across all identities/actions catches password spray and recovery churn.
        return [
            'crm.iam.abuse.origin.'.$origin => (int) config('identity.abuse_origin_limit'),
            'crm.iam.abuse.identity.'.hash('sha256', $action.'.'.$identity) => (int) config('identity.abuse_identity_limit'),
        ];
    }

    private function atomic(\Closure $operation): void
    {
        try {
            $store = $this->cache->getStore();
            if (!$store instanceof RedisStore && !($store instanceof ArrayStore && app()->environment('testing'))) { $this->deny(); }
            // Only short budget updates inside this mutex; never run authentication under a lease.
            $lock = $store->lock('crm.iam.abuse.budgets', 5);
            if (!$lock->get()) { $this->deny(); }
            try { $operation(); } finally { if (!$lock->release()) { $this->deny(); } }
        } catch (Throwable) {
            $this->deny();
        }
    }

    private function validate(mixed $value): void
    {
        if (!is_array($value) || !isset($value['count'], $value['until'], $value['next'])
            || !is_int($value['count']) || !is_int($value['until']) || !is_int($value['next']) || $value['count'] < 0) { $this->deny(); }
    }

    private function deny(): never { throw new AuthenticationException('CRM authentication required.'); }
}
