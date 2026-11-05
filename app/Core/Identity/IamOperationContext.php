<?php

declare(strict_types=1);

namespace App\Core\Identity;

/** Request-scoped infrastructure state; never populated from request parameters. */
final class IamOperationContext
{
    public ?string $id = null;
    public bool $changed = false;
}
