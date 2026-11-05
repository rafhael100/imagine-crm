<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use LogicException;

/** A pre-existing nonempty GUC is refused; this exception does not claim it was cleaned. */
final class ContaminatedTenantConnection extends LogicException
{
    public function __construct() { parent::__construct('Unsafe tenant connection.'); }
}
