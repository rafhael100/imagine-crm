<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use ImagineAI\MockBridge\MockCrmReadRequest;

/**
 * F236: append-only minimal evidence of synthetic service READ denial.
 * Strictly on a disposable PostgreSQL16 GitHub Actions laboratory.
 *
 * Records no raw request, headers, human identity, name, email or lead.
 * Neither audit success nor failure grants authority. This is NOT the
 * operational audit log, IAM, consent record or a production migration.
 */
final readonly class AiaPgServiceDenialAuditLab
{
    private const ROLE = 'crm_aia_audit_ci';
    private const TABLE = 'public.aia_lab_service_denial_audit';

    public function __construct(private Connection $connection)
    {
    }

    /**
     * One immutable audit attempt per server-generated event UUID.
     * Returns false on replay, unavailable DB, wrong role or bad input.
     */
    public function appendDenial(
        string $canonicalRequest,
        string $eventId,
        int $occurredAt,
    ): bool {
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('APP_ENV') !== 'testing'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_USERNAME') !== 'postgres'
            || getenv('RLS_TEST_USERNAME') !== 'crm_app_ci'
            || $this->connection->getDriverName() !== 'pgsql'
            || $this->connection->getDatabaseName() !== 'imagine_crm_ci'
            || $this->connection->getConfig('host') !== '127.0.0.1'
            || $this->connection->getConfig('username') !== self::ROLE
            || $this->connection->transactionLevel() !== 0
            || !Str::isUuid($eventId)
            || $occurredAt < 0 || $occurredAt > 4102444800) {
            return false;
        }

        try {
            // Strict original Agents decoder: all fields synthetic, known and
            // canonical. Only digests are stored, never the body itself.
            $request = MockCrmReadRequest::decodeCanonicalJson($canonicalRequest);
            $db = $this->connection;
            $who = $db->selectOne(
                "SELECT current_database() AS dbname, current_user AS username,
                        current_setting('row_security') AS rls,
                        r.rolsuper, r.rolbypassrls,
                        c.relrowsecurity, c.relforcerowsecurity,
                        pg_has_role(current_user,c.relowner,'MEMBER') AS owns_table,
                        has_table_privilege(current_user,c.oid,'INSERT') AS can_insert,
                        has_table_privilege(current_user,c.oid,'SELECT') AS can_read,
                        has_table_privilege(current_user,c.oid,'UPDATE') AS can_update,
                        has_table_privilege(current_user,c.oid,'DELETE') AS can_delete
                 FROM pg_roles r CROSS JOIN pg_class c
                 WHERE r.rolname=current_user
                   AND c.oid='public.aia_lab_service_denial_audit'::regclass"
            );
            if (!$who || $who->dbname !== 'imagine_crm_ci'
                || $who->username !== self::ROLE
                || $who->rls !== 'on'
                || $who->rolsuper || $who->rolbypassrls
                || $who->owns_table
                || !$who->relrowsecurity || !$who->relforcerowsecurity
                || !$who->can_insert || $who->can_read
                || $who->can_update || $who->can_delete) {
                return false;
            }

            $digest = hash('sha256', $canonicalRequest);
            $tenantDigest = hash(
                'sha256', "SYNTHETIC_AIA_TENANT_AUDIT_V1\n".$request['tenant_ref']
            );
            return $db->affectingStatement(
                'INSERT INTO '.self::TABLE.' (
                    event_id,issuer,event_type,request_sha256,
                    tenant_sha256,occurred_at
                 ) VALUES (?,?,?,?,?,?)
                 ON CONFLICT (event_id) DO NOTHING',
                [
                    strtolower($eventId),
                    'SYNTHETIC_AGENT_SERVICE',
                    'DENIED_OR_NOT_FOUND',
                    $digest,
                    $tenantDigest,
                    $occurredAt,
                ],
            ) === 1;
        } catch (\Throwable) {
            // Missing table, bad RLS or transient DB failure: deny event,
            // never reinterpret it as successful authorization.
            return false;
        }
    }
}
