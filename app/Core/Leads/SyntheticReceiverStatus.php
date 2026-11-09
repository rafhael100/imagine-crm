<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Unverified observations from mock receivers, NOT authoritative delivery states.
 */
enum SyntheticReceiverStatus: string
{
    case Unknown = 'unknown';
    case DurableClaimed = 'durable_claimed';
    case FailureClaimed = 'failure_claimed';
    case ExpiredClaimed = 'expired_claimed';
}
