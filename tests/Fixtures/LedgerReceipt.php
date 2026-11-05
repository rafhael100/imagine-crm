<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncDenial;
use App\Core\Async\AsyncDiagnostic;
use App\Core\Async\ReconciliationKind;
use App\Core\Async\ReconciliationTrace;

/** Receipt of a laboratory write, NEVER acceptance or business confirmation. */
final readonly class LedgerReceipt
{
    public ReconciliationTrace $reconciliation;

    public function __construct(public string $outcome, public string $attemptId,
        public AsyncDenial $denial, public AsyncDiagnostic $diagnostic)
    {
        $this->reconciliation = ReconciliationTrace::pending(match ($outcome) {
            'duplicate' => ReconciliationKind::DuplicateObserved,
            'conflict' => ReconciliationKind::ConflictObserved,
            default => ReconciliationKind::EvidenceIncomplete,
        }, $denial->observation);
    }
}
