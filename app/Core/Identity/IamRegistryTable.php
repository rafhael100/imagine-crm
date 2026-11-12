<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Database\Connection;
use LogicException;

/**
 * Lab IAM registry identifier allowlist. Public-schema qualification on
 * PostgreSQL prevents search_path and pg_temp from replacing trusted tables.
 *
 * SQLite is intentionally supported ONLY in the synthetic testing adapter.
 * This helper supplies identifiers, never a permission, SQL input, or tenant.
 */
final class IamRegistryTable
{
    /** @var array<string,true> */
    private const TABLES = [
        'crm_global_identities' => true,
        'crm_auth_credentials' => true,
        'crm_auth_sessions' => true,
        'crm_auth_recovery_codes' => true,
        'crm_auth_operations' => true,
        'crm_auth_mandates' => true,
        'crm_iam_audit_events' => true,
    ];

    public static function name(Connection $connection, string $table): string
    {
        if (!isset(self::TABLES[$table])) {
            throw new LogicException('Unknown IAM registry table.');
        }

        if ($connection->getDriverName() === 'pgsql') {
            return 'public.'.$table;
        }

        if ($connection->getDriverName() === 'sqlite' && app()->environment('testing')) {
            return $table;
        }

        throw new LogicException('Unsupported IAM registry connection.');
    }
}
