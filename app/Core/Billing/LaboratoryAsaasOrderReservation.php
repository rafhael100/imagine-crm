<?php

declare(strict_types=1);

namespace App\Core\Billing;

use App\Core\Plans\CommercialPlanReferenceCatalog;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * BILL-ASAAS-INT-03: one-shot, durable order reservation in disposable PG16 CI.
 *
 * The INSERT-only principal cannot inspect another organization's orders.
 * Existing/conflicting references are always denied for automatic HTTP;
 * no SELECT or unsafe "check then POST" path is permitted.
 *
 * Not a production migration, route, checkout or payment authorization.
 */
final readonly class LaboratoryAsaasOrderReservation
{
    public function __construct(private Connection $database) {}

    /**
     * @return array{status_code:'new_reservation'|'existing_review_required',
     *     may_attempt_sandbox_once:bool,informational_only:true,
     *     can_checkout:false,can_charge:false,can_activate:false}
     */
    public function reserve(
        string $organizationRef,
        string $orderRef,
        string $customerId,
        string $planCode,
        string $dueOn,
    ): array {
        $this->guard();
        self::validateIdentity($organizationRef, $orderRef, $customerId);
        self::validateDate($dueOn);
        $quote = CommercialPlanReferenceCatalog::preview($planCode, 1);
        if ($quote['can_charge'] !== false
            || $quote['price_homologated'] !== false
            || $quote['can_checkout'] !== false) {
            throw new InvalidArgumentException('Plano nao permitido neste laboratorio.');
        }

        try {
            // Physical PK guarantees only one winner across concurrent sessions.
            // Nothing is read from or disclosed about a losing/conflicting row.
            $count = $this->database->affectingStatement(
                'INSERT INTO crm_lab_asaas_order_reservations
                    (order_ref, organization_ref, provider_customer_id, plan_code, amount_cents, due_on)
                 VALUES (?::uuid, ?::uuid, ?, ?, ?, ?::date)
                 ON CONFLICT DO NOTHING',
                [$orderRef, $organizationRef, $customerId, $planCode,
                    $quote['monthly_cents'], $dueOn],
            );
            if (!in_array($count, [0, 1], true)) {
                throw new RuntimeException('Resultado inesperado de reserva de pedido.');
            }
            $new = $count === 1;

            return [
                'status_code' => $new ? 'new_reservation' : 'existing_review_required',
                'may_attempt_sandbox_once' => $new,
                'informational_only' => true,
                'can_checkout' => false,
                'can_charge' => false,
                'can_activate' => false,
            ];
        } catch (Throwable) {
            throw new RuntimeException('Reserva de pedido Asaas indisponivel.');
        }
    }

    /**
     * Never call the provider while an uncommitted reservation is invisible
     * to a second process. The gateway must be called ONLY after this check.
     */
    public function assertAutocommitBoundary(): void
    {
        $this->guard();
        if ($this->database->transactionLevel() !== 0) {
            throw new RuntimeException('Pagamento sandbox proibido durante transacao.');
        }
    }

    public static function validateIdentity(string $org, string $order, string $customer): void
    {
        $uuid = '/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD';
        if (preg_match($uuid, $org) !== 1
            || preg_match($uuid, $order) !== 1
            || strcasecmp($org, $order) === 0
            || preg_match('/\Acus_[A-Za-z0-9]{5,64}\z/D', $customer) !== 1) {
            throw new InvalidArgumentException('Pedido de laboratorio invalido.');
        }
    }

    public static function validateDate(string $value): void
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $value) !== 1) {
            throw new InvalidArgumentException('Data de pedido de laboratorio invalida.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Data de pedido de laboratorio invalida.');
        }
    }

    private function guard(): void
    {
        $pg = config('database.connections.pgsql');
        if (!app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_ORDER_LAB') !== 'synthetic-only'
            || $this->database->getDriverName() !== 'pgsql'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            throw new RuntimeException('Reserva de pedido limitada a CI descartavel.');
        }

        try {
            $role = $this->database->selectOne('SELECT current_user AS role_name');
        } catch (Throwable) {
            throw new RuntimeException('Papel de pedido indisponivel.');
        }
        if (($role->role_name ?? null) !== 'crm_billing_order_ci') {
            throw new RuntimeException('Papel de pedido nao autorizado.');
        }
    }
}
