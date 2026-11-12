<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Session-provider mapping only; no credential enrollment or login is enabled. */
final class GlobalIdentity extends Authenticatable
{
    protected $table = 'crm_global_identities';

    protected $connection = 'crm_iam';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['*'];

    /**
     * The same test-only model runs on SQLite and PostgreSQL. On PostgreSQL,
     * never let a caller-controlled search_path/pg_temp shadow global IDs.
     */
    public function getTable(): string
    {
        return IamRegistryTable::name($this->getConnection(), 'crm_global_identities');
    }
}
