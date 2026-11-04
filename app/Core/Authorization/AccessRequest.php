<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;

/**
 * Dados de avaliacao, NAO uma autorizacao concedida.
 * Recurso/acao/campo precisam ser mapeados aos contratos CORE02.
 */
final readonly class AccessRequest
{
    public function __construct(
        public TenantContext $context,
        public string $resource,
        public string $action,
        public ?string $field = null,
    ) {
        if (trim($resource) === '' || trim($action) === '') {
            throw new InvalidArgumentException('Resource and action are required.');
        }
    }
}
