<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Classificação determinística e pura de uma pontuação já apurada.
 *
 * Não calcula pontuação, não atribui pesos, não grava registros,
 * não identifica empresas e nunca concede permissões ou direitos.
 * O contexto/tenant só poderá ser associado por camada autorizada.
 */
final readonly class LeadScoringBands
{
    public function __construct(
        public int $coldMax = 39,
        public int $warmMax = 69,
    ) {
        if ($coldMax < 0 || $warmMax <= $coldMax || $warmMax >= 100) {
            throw new InvalidArgumentException('Faixas de pontuacao invalidas.');
        }
    }

    public function classify(int $score): LeadTemperature
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException('Pontuacao fora do intervalo permitido.');
        }

        if ($score <= $this->coldMax) {
            return LeadTemperature::Cold;
        }
        if ($score <= $this->warmMax) {
            return LeadTemperature::Warm;
        }

        return LeadTemperature::Hot;
    }
}
