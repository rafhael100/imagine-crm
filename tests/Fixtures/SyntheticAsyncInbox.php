<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncAuthorityRequest;

/** Volatile test fixture ONLY. No binding, migration, durability claim or execution. */
final class SyntheticAsyncInbox
{
    private array $seen = [];

    public function observe(AsyncAuthorityRequest $request): string
    {
        $key = $request->tenantId.'|'.$request->idempotencyKey;
        $fingerprint = hash('sha256', serialize($request));
        if (isset($this->seen[$key])) { return $this->seen[$key] === $fingerprint ? 'duplicate' : 'conflict'; }
        $this->seen[$key] = $fingerprint;
        return 'first_seen'; // Not accepted, admitted, persisted or delivered.
    }
}
