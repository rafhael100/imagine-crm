<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncObservation;
use App\Core\Async\AsyncObserver;
use RuntimeException;

/** The only observation sink implementation: volatile synthetic test fixture. */
final class SyntheticAsyncObserver implements AsyncObserver
{
    public array $signals = [];
    public int $attempts = 0;
    public bool $fail = false;
    public function record(AsyncObservation $observation): void
    {
        $this->attempts++;
        if ($this->fail) { throw new RuntimeException('synthetic-token synthetic@example.invalid'); }
        $this->signals[] = $observation->signal();
    }
}
