<?php

declare(strict_types=1);

namespace App\Core\Async;

/** Contract only. Implementations/sinks for this increment exist exclusively in tests/. */
interface AsyncObserver
{
    public function record(AsyncObservation $observation): void;
}
