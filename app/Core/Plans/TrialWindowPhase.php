<?php

declare(strict_types=1);

namespace App\Core\Plans;

/**
 * Descriptive phases for a synthetic 14-day trial timeline.
 *
 * These are NOT account states, access rights or billing instructions.
 */
enum TrialWindowPhase: string
{
    case BeforeStart = 'before_start';
    case WithinWindow = 'within_window';
    case AfterWindow = 'after_window';
}
