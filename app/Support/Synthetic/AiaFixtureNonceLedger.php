<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Nonce ledger scoped to this PHP object only; NOT a production replay store.
 * Never evicts an entry: saturation must fail closed, not restore replay.
 */
final class AiaFixtureNonceLedger
{
    /** @var array<string,true> */
    private array $consumed = [];
    private bool $available = true;

    public function __construct(private readonly int $capacity = 64)
    {
        if ($capacity < 1 || $capacity > 512) {
            throw new InvalidArgumentException('Invalid synthetic replay capacity.');
        }
    }

    public function claim(string $nonce): bool
    {
        if (!$this->available || isset($this->consumed[$nonce])
            || count($this->consumed) >= $this->capacity) {
            return false;
        }
        $this->consumed[$nonce] = true;
        return true;
    }

    public function setAvailable(bool $available): void
    {
        // An outage cannot forget already consumed fixture nonces.
        $this->available = $available;
    }

    public function countConsumed(): int
    {
        return count($this->consumed);
    }
}
