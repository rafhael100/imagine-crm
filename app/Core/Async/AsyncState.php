<?php

declare(strict_types=1);

namespace App\Core\Async;

/** Laboratory vocabulary, not states of an active job or business fact. */
enum AsyncState: string
{
    case Received = 'received';
    case FormatValidated = 'format_validated';
    case Denied = 'denied';
    case Inconclusive = 'inconclusive';
    case ReconciliationRequired = 'reconciliation_required';
    // Reserved concepts deliberately unreachable in this candidate.
    case Admitted = 'admitted';
    case Forwarded = 'forwarded';
    case OfficiallyConfirmed = 'officially_confirmed';
}
