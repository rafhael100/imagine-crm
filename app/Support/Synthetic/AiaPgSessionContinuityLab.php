<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/**
 * F238: test-only continuity check for EXACT originating human IAM session.
 *
 * Authenticates no HTTP request and exports no token; only re-reads
 * the CRM-owned UUID/session-state and credential version under row locks.
 * No real service route, business authorization, secret or production ACL.
 */
final readonly class AiaPgSessionContinuityLab
{
    public function __construct(private Connection $iam)
    {
    }

    /**
     * The callback is invoked ONLY while IAM credential/session read locks
     * remain held and after all current validity checks. Never ALLOW.
     */
    public function withinActiveSession(
        string $identityId,
        string $sessionId,
        int $credentialVersion,
        int $now,
        Closure $operation,
    ): bool {
        if (getenv('AIA_F238_SESSION_LAB') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('IAM_TEST_USERNAME') !== 'crm_iam_ci'
            || $this->iam->getDriverName() !== 'pgsql'
            || $this->iam->getDatabaseName() !== 'imagine_crm_ci'
            || $this->iam->getConfig('host') !== '127.0.0.1'
            || $this->iam->getConfig('username') !== 'crm_iam_ci'
            || $this->iam->transactionLevel() !== 0
            || !Str::isUuid($identityId) || !Str::isUuid($sessionId)
            || $credentialVersion < 1
            || $now < 0 || $now > 4102444800) {
            return false;
        }

        try {
            $db = $this->iam;
            $role = $db->selectOne(
                "SELECT current_database() AS dbname,
                        current_user AS username,
                        current_setting('app.tenant_id', true) AS inherited_tenant,
                        current_setting('row_security') AS rls,
                        r.rolsuper, r.rolbypassrls,
                        pg_has_role(current_user,c.relowner,'MEMBER') AS owns_auth
                 FROM pg_roles r CROSS JOIN pg_class c
                 WHERE r.rolname=current_user
                   AND c.oid='public.crm_auth_sessions'::regclass"
            );
            if (!$role || $role->dbname !== 'imagine_crm_ci'
                || $role->username !== 'crm_iam_ci'
                || $role->rls !== 'on'
                || $role->rolsuper || $role->rolbypassrls || $role->owns_auth
                || ($role->inherited_tenant !== null
                    && $role->inherited_tenant !== '')) {
                return false;
            }

            return $db->transaction(function () use (
                $db, $identityId, $sessionId, $credentialVersion,
                $now, $operation
            ): bool {
                // Lock order matches Core: credential FIRST, then session.
                // The real IAM role has only narrowly scoped privileges.
                $credential = $db->table('public.crm_auth_credentials')
                    ->where('identity_id', strtolower($identityId))
                    ->sharedLock()->first([
                        'identity_id','status','verified_at','version',
                        'factor_confirmed_at',
                    ]);
                $session = $db->table('public.crm_auth_sessions')
                    ->where('id', strtolower($sessionId))
                    ->where('identity_id', strtolower($identityId))
                    ->sharedLock()->first([
                        'id','identity_id','state','credential_version',
                        'created_at','expires_at','absolute_expires_at',
                        'mfa_verified_at','operation_id','security_hold',
                    ]);
                if (!$credential || !$session
                    || $credential->status !== 'active'
                    || (int) $credential->verified_at <= 0
                    || (int) $credential->factor_confirmed_at <= 0
                    || (int) $credential->version !== $credentialVersion
                    || $session->state !== 'active'
                    || (int) $session->credential_version !== $credentialVersion
                    || !$session->mfa_verified_at
                    || (int) $session->created_at > $now
                    || (int) $session->expires_at <= $now
                    || (int) $session->absolute_expires_at <= $now
                    || $session->security_hold !== null) {
                    return false;
                }

                if ($session->operation_id !== null) {
                    $completed = $db->table('public.crm_auth_operations')
                        ->where('id', $session->operation_id)
                        ->where('state', 'completed')
                        ->exists();
                    if (!$completed) {
                        return false;
                    }
                }

                // No session token, cookie, browser context, user details or
                // IAM DB row escapes this laboratory callback.
                return $operation() === true;
            }, 1); // one attempt; no retries after protected operation
        } catch (\Throwable) {
            // Revocation, unavailable IAM, contamination or lock failures
            // cannot be converted into a positive service grant.
            return false;
        }
    }
}
