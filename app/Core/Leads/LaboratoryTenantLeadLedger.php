<?php

declare(strict_types=1);

namespace App\Core\Leads;

use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\PostgresActiveMembershipStore;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * CRM-LEADS-PG01: CI-only tenant-isolated, persistent lead/Kanban prototype.
 *
 * Real membership check and FORCE RLS happen in one PostgreSQL transaction.
 * PrincipalReference IS NOT an authentication proof; this class is never
 * registered as an endpoint, worker or app service. All records are synthetic,
 * titles are fixed-format pseudonymous tokens, never customer contact data.
 *
 * No operational access until GuardPrincipalSource, IAM, business Authorizer,
 * LGPD, app runtime role, and SEG/DBA gates have independently passed.
 */
final readonly class LaboratoryTenantLeadLedger
{
    private const TRANSITIONS = [
        'new' => ['qualified', 'lost'],
        'qualified' => ['proposal', 'lost'],
        'proposal' => ['won', 'lost'],
        'won' => [],
        'lost' => [],
    ];

    public function __construct(private Connection $db) {}

    /**
     * CRM-LEADS-PG03: CI-only idempotent import of a synthetic Sites event.
     *
     * Submission/binding UUIDs are laboratory claims, NOT verified webhook identity.
     * One tenant-scoped event can create at most one lead and one audit event.
     * A replay with differing attributes returns the same generic denial as
     * an attempted remap; it never creates a second lead or reveals PII.
     *
     * @return array{status_code:'synthetic_captured'|'synthetic_replayed'|'synthetic_conflict_denied',
     *   can_publish:false,can_contact:false,can_activate:false}
     */
    public function captureSynthetic(
        PrincipalReference $principal,
        string $organization,
        string $leadId,
        string $syntheticTitle,
        string $sourceCode,
        SyntheticIdempotencyClaim $claim,
        ?string $signedNonce = null,
    ): array {
        if (!Str::isUuid($leadId)
            || preg_match('/\\A[A-Z0-9][A-Z0-9_-]{2,63}\\z/D', $syntheticTitle) !== 1
            || !in_array($sourceCode, ['sites', 'landing', 'onepage'], true)
            || !Str::isUuid($organization)
            || $claim->identity->organizationReference !== strtolower($organization)
            || ($signedNonce !== null && (!Str::isUuid($signedNonce)
                || strtolower($signedNonce) === '00000000-0000-0000-0000-000000000000'))) {
            throw new InvalidArgumentException('Captura de lead sintetico invalida.');
        }
        // A stable, claimed submission identity is not proof of origin.
        // The source and every binding reference are persisted immutable.
        $eventSha256 = $claim->syntheticLedgerFingerprint();

        return $this->within($principal, $organization, function (MembershipSnapshot $membership)
            use ($leadId, $syntheticTitle, $sourceCode, $eventSha256, $claim, $signedNonce): array {
            try {
                // The unique event key is claimed BEFORE inserting a lead.
                // A deferred composite FK guarantees no orphan can commit.
                $claimed = $this->db->affectingStatement(
                    'INSERT INTO public.crm_lab_lead_capture_keys
                        (organization_id, source_code, event_sha256, lead_id,
                         binding_reference, submission_reference, site_reference,
                         form_reference, destination_reference, signed_nonce)
                     VALUES (?::uuid, ?, ?, ?::uuid, ?::uuid, ?::uuid,
                             ?::uuid, ?::uuid, ?::uuid, ?::uuid)
                     ON CONFLICT DO NOTHING',
                    [$membership->tenantId, $sourceCode, $eventSha256, strtolower($leadId),
                        $claim->identity->bindingReference,
                        $claim->identity->submissionReference,
                        $claim->siteReference,
                        $claim->formReference,
                        $claim->destinationReference, $signedNonce !== null ? strtolower($signedNonce) : null],
                );
                if ($claimed === 1) {
                    $this->db->table('public.crm_lab_leads')->insert([
                        'id' => strtolower($leadId),
                        'organization_id' => $membership->tenantId,
                        'synthetic_title' => $syntheticTitle,
                    ]);
                    $this->appendEvent($leadId, $membership->tenantId, 1,
                        'created', null, 'new');
                    return self::captureAnswer('synthetic_captured');
                }
                if ($claimed !== 0) {
                    throw new RuntimeException('Captura CI retornou contagem inesperada.');
                }

                // Only this tenant's exact synthetic event is visible.
                $record = $this->db->table('public.crm_lab_lead_capture_keys')
                    ->where('organization_id', $membership->tenantId)
                    ->where('source_code', $sourceCode)
                    ->where('event_sha256', $eventSha256)
                    ->first(['lead_id', 'binding_reference', 'submission_reference',
                        'site_reference', 'form_reference', 'destination_reference', 'signed_nonce']);
                if ($record
                    && strtolower((string) $record->lead_id) === strtolower($leadId)
                    && (string) $record->binding_reference === $claim->identity->bindingReference
                    && (string) $record->submission_reference === $claim->identity->submissionReference
                    && (string) $record->site_reference === $claim->siteReference
                    && (string) $record->form_reference === $claim->formReference
                    && (string) $record->destination_reference === $claim->destinationReference
                    && ($signedNonce === null
                        ? $record->signed_nonce === null
                        : (string) $record->signed_nonce === strtolower($signedNonce))) {
                    $title = $this->db->table('public.crm_lab_leads')
                        ->where('organization_id', $membership->tenantId)
                        ->where('id', strtolower($leadId))
                        ->value('synthetic_title');
                    if ($title === $syntheticTitle) {
                        return self::captureAnswer('synthetic_replayed');
                    }
                }

                return self::captureAnswer('synthetic_conflict_denied');
            } catch (Throwable) {
                // Never surface SQL params, tenant IDs or source event hashes.
                throw new RuntimeException('Captura sintetica de lead indisponivel.');
            }
        });
    }

    private static function captureAnswer(string $status): array
    {
        return [
            'status_code' => $status,
            'can_publish' => false,
            'can_contact' => false,
            'can_activate' => false,
        ];
    }

    /** @return array{status_code:'synthetic_created',can_publish:false,can_contact:false,can_activate:false} */
    public function createSynthetic(
        PrincipalReference $principal,
        string $organization,
        string $leadId,
        string $syntheticTitle,
    ): array {
        if (!Str::isUuid($leadId)
            || preg_match('/\A[A-Z0-9][A-Z0-9_-]{2,63}\z/D', $syntheticTitle) !== 1) {
            throw new InvalidArgumentException('Identificadores sinteticos de lead invalidos.');
        }

        return $this->within($principal, $organization, function (MembershipSnapshot $membership)
            use ($leadId, $syntheticTitle): array {
            try {
                $this->db->table('public.crm_lab_leads')->insert([
                    'id' => strtolower($leadId),
                    'organization_id' => $membership->tenantId,
                    'synthetic_title' => $syntheticTitle,
                ]);
                $this->appendEvent($leadId, $membership->tenantId, 1,
                    'created', null, 'new');
                return [
                    'status_code' => 'synthetic_created',
                    'can_publish' => false,
                    'can_contact' => false,
                    'can_activate' => false,
                ];
            } catch (Throwable) {
                throw new RuntimeException('Persistencia sintetica de lead indisponivel.');
            }
        });
    }

    /**
     * A stale write, hidden tenant record or absent lead has the SAME result.
     * Transition is intentionally limited to a fixed state machine.
     *
     * @return array{status_code:'synthetic_stage_changed'|'synthetic_not_changed',can_publish:false,can_contact:false,can_activate:false}
     */
    public function moveSyntheticStage(
        PrincipalReference $principal,
        string $organization,
        string $leadId,
        string $nextStage,
        int $expectedVersion,
    ): array {
        if (!Str::isUuid($leadId) || !array_key_exists($nextStage, self::TRANSITIONS)
            || $expectedVersion < 1) {
            throw new InvalidArgumentException('Movimento sintetico de Kanban invalido.');
        }

        return $this->within($principal, $organization, function (MembershipSnapshot $membership)
            use ($leadId, $nextStage, $expectedVersion): array {
            try {
                $record = $this->db->table('public.crm_lab_leads')
                    ->where('id', strtolower($leadId))
                    ->where('organization_id', $membership->tenantId)
                    ->lockForUpdate()->first();

                if (!$record || $record->archived || (int) $record->version !== $expectedVersion) {
                    return self::moveAnswer('synthetic_not_changed');
                }
                if (!in_array($nextStage, self::TRANSITIONS[$record->stage] ?? [], true)) {
                    return self::moveAnswer('synthetic_not_changed');
                }

                $updated = $this->db->table('public.crm_lab_leads')
                    ->where('id', strtolower($leadId))
                    ->where('organization_id', $membership->tenantId)
                    ->where('version', $expectedVersion)
                    ->where('archived', false)
                    ->update(['stage' => $nextStage, 'version' => $expectedVersion + 1]);
                if ($updated === 1) {
                    $this->appendEvent($leadId, $membership->tenantId,
                        $expectedVersion + 1, 'stage_moved', (string) $record->stage, $nextStage);
                }
                return self::moveAnswer($updated === 1
                    ? 'synthetic_stage_changed' : 'synthetic_not_changed');
            } catch (Throwable) {
                throw new RuntimeException('Transicao sintetica de lead indisponivel.');
            }
        });
    }

    /** @return array{status_code:'synthetic_archived'|'synthetic_not_changed',can_publish:false,can_contact:false,can_activate:false} */
    public function archiveSynthetic(
        PrincipalReference $principal,
        string $organization,
        string $leadId,
        int $expectedVersion,
    ): array {
        if (!Str::isUuid($leadId) || $expectedVersion < 1) {
            throw new InvalidArgumentException('Arquivo sintetico de lead invalido.');
        }

        return $this->within($principal, $organization, function (MembershipSnapshot $membership)
            use ($leadId, $expectedVersion): array {
            try {
                $record = $this->db->table('public.crm_lab_leads')
                    ->where('id', strtolower($leadId))
                    ->where('organization_id', $membership->tenantId)
                    ->lockForUpdate()->first();
                if (!$record || $record->archived || (int) $record->version !== $expectedVersion) {
                    return self::archiveAnswer('synthetic_not_changed');
                }
                $updated = $this->db->table('public.crm_lab_leads')
                    ->where('id', strtolower($leadId))
                    ->where('organization_id', $membership->tenantId)
                    ->where('version', $expectedVersion)
                    ->where('archived', false)
                    ->update([
                        'archived' => true,
                        'version' => $expectedVersion + 1,
                    ]);
                if ($updated === 1) {
                    $this->appendEvent($leadId, $membership->tenantId,
                        $expectedVersion + 1, 'archived', (string) $record->stage,
                        (string) $record->stage);
                }
                return self::archiveAnswer($updated === 1
                    ? 'synthetic_archived' : 'synthetic_not_changed');
            } catch (Throwable) {
                throw new RuntimeException('Arquivo sintetico de lead indisponivel.');
            }
        });
    }

    /**
     * @return list<array{id:string,synthetic_title:string,stage:string,version:int}>
     */
    public function listSynthetic(PrincipalReference $principal, string $organization): array
    {
        return $this->within($principal, $organization, function (MembershipSnapshot $membership): array {
            try {
                $rows = $this->db->table('public.crm_lab_leads')
                    ->where('organization_id', $membership->tenantId)
                    ->where('archived', false)
                    ->orderBy('synthetic_title')->get([
                        'id', 'synthetic_title', 'stage', 'version',
                    ]);
                return $rows->map(static fn ($row) => [
                    'id' => (string) $row->id,
                    'synthetic_title' => (string) $row->synthetic_title,
                    'stage' => (string) $row->stage,
                    'version' => (int) $row->version,
                ])->all();
            } catch (Throwable) {
                throw new RuntimeException('Consulta sintetica de leads indisponivel.');
            }
        });
    }

    private function within(PrincipalReference $principal, string $organization, callable $effect): mixed
    {
        $this->guard();
        if (!Str::isUuid($organization) || !Str::isUuid($principal->principalId)) {
            throw new InvalidArgumentException('Referencia de organizacao invalida.');
        }

        // Membership is read FOR UPDATE and tenant GUC is transaction-local.
        // The membership itself does not grant a business action; this is lab.
        return (new PostgresActiveMembershipStore($this->db))
            ->within($principal, strtolower($organization), $effect);
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('CI') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_LEADS_LAB') !== 'synthetic-only'
            || $this->db->getDriverName() !== 'pgsql'
            || $this->db->transactionLevel() !== 0
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new LogicException('Leads sintéticos exigem PostgreSQL descartavel no CI.');
        }
        try {
            $role = $this->db->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new LogicException('Papel de leads indisponivel.');
        }
        if (($role->role_name ?? null) !== 'crm_app_ci') {
            throw new LogicException('Papel de leads nao autorizado.');
        }
    }

    /**
     * Event append is inside the locked membership transaction. A failed
     * write aborts BOTH the lead mutation and its audit event.
     * The application role cannot SELECT, UPDATE or DELETE event rows.
     */
    private function appendEvent(
        string $leadId, string $organization, int $version, string $eventType,
        ?string $fromStage, string $toStage,
    ): void {
        $this->db->table('public.crm_lab_lead_events')->insert([
            'event_id' => (string) Str::uuid(),
            'lead_id' => strtolower($leadId),
            'organization_id' => $organization,
            'version' => $version,
            'event_type' => $eventType,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
        ]);
    }

    private static function moveAnswer(string $status): array
    {
        return [
            'status_code' => $status,
            'can_publish' => false,
            'can_contact' => false,
            'can_activate' => false,
        ];
    }

    private static function archiveAnswer(string $status): array
    {
        return self::moveAnswer($status);
    }
}
