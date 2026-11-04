<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use InvalidArgumentException;

/**
 * Escopo de uma operacao ja resolvida por componentes confiaveis.
 *
 * ATENCAO: nao comprova membership, tenant ou permissao. Nenhuma rota/API
 * deve criar este contexto de um parametro fornecido pelo cliente.
 * A origem/validacao final depende de CORE01-D05/D07 e SEG-01/02.
 */
final readonly class TenantContext
{
    public function __construct(
        public string $tenantId,
        public string $principalId,
        public string $correlationId,
    ) {
        if (trim($tenantId) === '' || trim($principalId) === '' || trim($correlationId) === '') {
            throw new InvalidArgumentException('TenantContext requires non-empty identifiers.');
        }
    }
}
