<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Categoria descritiva da pontuação: não concede acesso, automação ou benefício.
 */
enum LeadTemperature: string
{
    case Cold = 'frio';
    case Warm = 'morno';
    case Hot = 'quente';
}
