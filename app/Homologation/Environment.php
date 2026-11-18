<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Core\Identity\IdentityRuntimeProfile;
use Illuminate\Database\Connection;
use LogicException;

/** Only the dedicated, fictional private application. Never customer runtime. */
final class Environment
{
    public static function enabled(): bool
    {
        return app()->environment('staging') && IdentityRuntimeProfile::enabled()
            && getenv('CRM_PRIVATE_JOURNEY') === 'synthetic-v1';
    }

    public static function minimalLeadReadEnabled(): bool
    {
        return self::enabled() && getenv('CRM_PRIVATE_OP01') === 'minimal-lead-v1';
    }

    public static function assertConnection(Connection $db, string $role): void
    {
        if (!self::enabled() || !in_array($role, ['app','iam','enrollment','operator'], true)
            || $db->getDriverName() !== 'pgsql' || $db->transactionLevel() !== 0) {
            throw new LogicException('Private CRM unavailable.');
        }
        $actual = $db->selectOne("SELECT current_database() AS db, current_user AS role,
            r.rolsuper,r.rolbypassrls,pg_has_role(current_user,c.relowner,'MEMBER') AS owns
            FROM pg_roles r CROSS JOIN pg_class c WHERE r.rolname=current_user
            AND c.oid='public.crm_private_installation'::regclass");
        if (!$actual || $actual->db !== 'imagine_crm_private_homologation'
            || $actual->role !== 'crm_homologation_'.$role
            || $actual->rolsuper || $actual->rolbypassrls || $actual->owns
            || $db->table('public.crm_private_installation')->value('mode') !== 'synthetic-v1') {
            throw new LogicException('Private CRM unavailable.');
        }
    }
}
