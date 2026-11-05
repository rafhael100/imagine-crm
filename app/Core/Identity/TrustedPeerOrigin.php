<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/** Direct transport peer only. Forwarded/X-Forwarded-For never supply authority. */
final readonly class TrustedPeerOrigin
{
    public function __construct(private Request $request) {}

    public function key(): string
    {
        $peer = $this->request->server('REMOTE_ADDR');
        $key = config('app.key');
        if (!is_string($peer) || !filter_var($peer, FILTER_VALIDATE_IP) || !is_string($key) || $key === '') {
            throw new AuthenticationException('CRM authentication required.');
        }
        return hash_hmac('sha256', (string) inet_pton($peer), $key);
    }
}
