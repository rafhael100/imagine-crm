<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * Identificador de principal, NAO uma comprovacao de autenticacao.
 * Fonte autenticada/validacao: CORE04 e contratos IAM ainda pendentes.
 */
final readonly class PrincipalReference
{
    public function __construct(public string $principalId)
    {
        if (trim($principalId) === '') {
            throw new InvalidArgumentException('Principal id must not be blank.');
        }
    }
}
