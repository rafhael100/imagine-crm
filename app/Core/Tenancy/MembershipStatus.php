<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

/** Estados semanticos; transicoes fisicas dependem de CORE01/CORE07. */
enum MembershipStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Disabled = 'disabled';
}
