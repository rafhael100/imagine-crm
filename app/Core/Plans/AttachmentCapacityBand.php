<?php

declare(strict_types=1);

namespace App\Core\Plans;

/**
 * Display-only classification of a synthetic attachment storage snapshot.
 *
 * No state here grants permission to upload, changes a plan or triggers
 * a notification. "unavailable" is a finite zero quota, not unlimited.
 */
enum AttachmentCapacityBand: string
{
    case Unavailable = 'unavailable';
    case BelowNotice = 'below_notice';
    case Notice80 = 'notice_80';
    case Notice95 = 'notice_95';
    case AtLimit = 'at_limit';
    case OverLimit = 'over_limit';
}
