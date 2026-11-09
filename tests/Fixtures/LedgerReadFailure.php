<?php

declare(strict_types=1);

namespace Tests\Fixtures;

final class LedgerReadFailure extends \RuntimeException
{
    public function __construct(public readonly LedgerGap $gap) { parent::__construct('Synthetic ledger inspection unavailable.'); }
}
