<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Mock storage phases; neither DeliveredClaimed nor ExpiredClaimed is evidence.
 */
enum SyntheticIdempotencyPhase: string
{
    case Open = 'open';
    case Reserved = 'reserved';
    case OutcomeUnknown = 'outcome_unknown';
    case DeliveredClaimed = 'delivered_claimed';
    case ExpiredClaimed = 'expired_claimed';
}
