<?php

declare(strict_types=1);

namespace App\Core\Async;

enum ReconciliationKind: string
{
    case EvidenceIncomplete = 'evidence_incomplete';
    case DuplicateObserved = 'duplicate_observed';
    case ConflictObserved = 'conflict_observed';
    case ReplayObserved = 'replay_observed';
}
