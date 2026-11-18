<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-02: PostgreSQL physical at-least-once deduplication experiment.
 *
 * Not deployable in the current release: the only allowed target is a
 * disposable GitHub CI PostgreSQL service with an INSERT-only role.
 *
 * Stores exclusively HMAC event/resource identifiers and keyed body HMAC-SHA256,
 * NEVER payloads, customer contacts, names, amounts, card or subscription
 * rights. Does NOT settle, charge, invoice, grant IAM or activate a tenant.
 */
final readonly class AsaasWebhookInboxCandidate
{
    public function __construct(private Connection $database) {}

    /**
     * @return array{
     *   status_code:'synthetic_inbox_accepted'|'synthetic_inbox_duplicate',
     *   persisted_before_ack: true,
     *   informational_only:true,
     *   can_acknowledge_delivery: false,
     *   can_activate: false,
     *   can_charge: false,
     *   can_provision: false
     * }
     */
    public function record(AsaasWebhookEnvelope $envelope, int $observedAt): array
    {
        $this->guard();

        if ($observedAt < 1 || $observedAt > PHP_INT_MAX - 1) {
            throw new InvalidArgumentException('Registro de webhook invalido.');
        }

        try {
            // The role has only column-level INSERT, not SELECT/UPDATE/DELETE.
            // PostgreSQL's unique index arbitrates concurrent delivery.
            $affected = $this->database->affectingStatement(
                "INSERT INTO crm_lab_asaas_events
                  (event_key, resource_key, event_kind, body_digest, observed_at)
                 VALUES (?, ?, ?, ?, ?)
                 ON CONFLICT DO NOTHING",
                [
                    $envelope->eventKey,
                    $envelope->resourceKey,
                    $envelope->eventKind,
                    $envelope->bodyDigest,
                    $observedAt,
                ],
            );
        } catch (Throwable) {
            // SQL exceptions may contain pseudonymous bindings; fail closed
            // without exception chaining, HTTP acknowledgement or logs.
            throw new RuntimeException('Persistencia do webhook indisponivel.');
        }

        if ($affected !== 0 && $affected !== 1) {
            throw new RuntimeException('Resultado de persistencia do webhook invalido.');
        }

        return [
            'status_code' => $affected === 1
                ? 'synthetic_inbox_accepted' : 'synthetic_inbox_duplicate',
            'persisted_before_ack' => true,
            'informational_only' => true,
            'can_acknowledge_delivery' => false, // No published webhook endpoint.
            'can_activate' => false,
            'can_charge' => false,
            'can_provision' => false,
        ];
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');

        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_WEBHOOK_LAB') !== 'synthetic-only'
            || $this->database->getDriverName() !== 'pgsql'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new RuntimeException('Inbox de cobranca limitado ao laboratorio CI.');
        }

        try {
            $role = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Identidade de banco de cobranca indisponivel.');
        }

        if (($role->role_name ?? null) !== 'crm_billing_ci') {
            throw new RuntimeException('Papel de cobranca do laboratorio nao autorizado.');
        }
    }
}
