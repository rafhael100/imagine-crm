<?php

declare(strict_types=1);

namespace App\Core\Billing;

use Illuminate\Database\Connection;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-03: no-effects inbox recovery / SKIP LOCKED laboratory worker.
 *
 * This NEVER performs financial or account operations. It only proves that
 * one PostgreSQL reviewer can consume one pending marker at a time and
 * rollback can return that marker to the queue.
 *
 * Separate from the INSERT-only receiver role. CI-only and fail-closed.
 */
final readonly class AsaasWebhookReviewWorkerCandidate
{
    public function __construct(private Connection $database) {}

    /**
     * @return array{
     *  status_code:'synthetic_reviewed_no_effect'|'synthetic_nothing_to_review',
     *  informational_only:true,can_grant_access:false,can_charge:false,
     *  can_invoice:false,can_update_subscription:false,can_send:false
     * }
     */
    public function reviewNext(): array
    {
        $this->guard();

        try {
            $reviewed = $this->database->transaction(function (): bool {
                // Two workers may read the same pending *query*, but only one
                // can obtain a row lock. The other SKIP LOCKED returns empty.
                $row = $this->database->selectOne(
                    "SELECT event_key, event_kind
                       FROM crm_lab_asaas_events
                      WHERE state = 'pending_review'
                      ORDER BY observed_at ASC, event_key ASC
                      FOR UPDATE SKIP LOCKED
                      LIMIT 1",
                );
                if (!$row) {
                    return false;
                }

                $count = $this->database->affectingStatement(
                    "UPDATE crm_lab_asaas_events
                        SET state = 'reviewed_no_effect'
                      WHERE event_key = ? AND state = 'pending_review'",
                    [$row->event_key],
                );
                if ($count !== 1) {
                    throw new RuntimeException('Nenhum registro de revisao em estado esperado.');
                }

                // No external API call, no payment status inference, no
                // customer lookup, no IO. This is a physical queue probe.
                return true;
            }, 1);
        } catch (Throwable) {
            // SQL errors can contain HMAC identifiers; do not leak causes.
            throw new RuntimeException('Revisao de webhook indisponivel.');
        }

        return [
            'status_code' => $reviewed
                ? 'synthetic_reviewed_no_effect' : 'synthetic_nothing_to_review',
            'informational_only' => true,
            'can_grant_access' => false,
            'can_charge' => false,
            'can_invoice' => false,
            'can_update_subscription' => false,
            'can_send' => false,
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
            throw new RuntimeException('Revisao financeira limitada ao laboratorio CI.');
        }

        try {
            $role = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Identidade de revisao financeira indisponivel.');
        }

        if (($role->role_name ?? null) !== 'crm_billing_review_ci') {
            throw new RuntimeException('Papel de revisao do laboratorio nao autorizado.');
        }
    }
}
