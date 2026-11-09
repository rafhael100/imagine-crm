<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use InvalidArgumentException;

/**
 * Snapshot inerte de uma membership, NAO evidencia autenticada do banco.
 * Somente um repositorio/servico confiavel podera fornecer fatos reais.
 */
final readonly class MembershipSnapshot
{
    public function __construct(
        public string $tenantId,
        public string $principalId,
        public MembershipStatus $status,
        public int $version,
    ) {
        if (trim($tenantId) === '' || trim($principalId) === '') {
            throw new InvalidArgumentException('Membership tenant/principal required.');
        }

        if ($version < 1) {
            throw new InvalidArgumentException('Membership version must be positive.');
        }
    }
}
