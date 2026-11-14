<?php

declare(strict_types=1);

namespace App\Core\Billing;

use App\Core\Plans\CommercialPlanReferenceCatalog;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * BILL-ASAAS-08: an immutable informational monthly subscription draft.
 *
 * This is NOT a provider request, authorization, customer agreement, quote
 * accepted by the user or an entitlement. The commercial plan catalog is
 * expressly provisional. The object does not issue payments or invoices.
 */
final class AsaasSubscriptionDraftSpec
{
    /**
     * @return array{
     *   external_reference:string,plan_code:string,monthly_cents:int,
     *   currency:'BRL',cycle:'MONTHLY',billing_type:'PIX'|'BOLETO',
     *   trial_started_on:string,trial_ends_on:string,first_due_on:string,
     *   informational_only:true,price_homologated:false,
     *   terms_accepted:false,company_owner_verified:false,
     *   can_create_subscription:false,can_charge:false,
     *   can_activate:false,can_invoice:false
     * }
     */
    public static function preview(
        string $planCode,
        string $trialStartedOn,
        string $firstDueOn,
        string $billingType,
        string $externalReference,
    ): array {
        $uuid = '/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/iD';
        if (preg_match($uuid, $externalReference) !== 1
            || !in_array($billingType, ['PIX', 'BOLETO'], true)) {
            throw new InvalidArgumentException('Rascunho de assinatura invalido.');
        }

        $started = self::day($trialStartedOn);
        $due = self::day($firstDueOn);
        $trialEnd = $started->modify('+14 days');

        // Business time is pinned explicitly; no wall-clock, client date or
        // automatic start when payment authorization is absent.
        if ($due < $trialEnd) {
            throw new InvalidArgumentException('Primeiro vencimento antes do fim do trial.');
        }

        $reference = CommercialPlanReferenceCatalog::preview($planCode, 1);
        if (($reference['price_homologated'] ?? true) !== false
            || ($reference['can_checkout'] ?? true) !== false
            || !is_int($reference['monthly_cents'])) {
            throw new InvalidArgumentException('Preco de plano ainda nao validado.');
        }

        return [
            'external_reference' => strtolower($externalReference),
            'plan_code' => $planCode,
            'monthly_cents' => $reference['monthly_cents'],
            'currency' => 'BRL',
            'cycle' => 'MONTHLY',
            'billing_type' => $billingType,
            'trial_started_on' => $started->format('Y-m-d'),
            'trial_ends_on' => $trialEnd->format('Y-m-d'),
            'first_due_on' => $due->format('Y-m-d'),
            'informational_only' => true,
            'price_homologated' => false,
            'terms_accepted' => false,
            'company_owner_verified' => false,
            'can_create_subscription' => false,
            'can_charge' => false,
            'can_activate' => false,
            'can_invoice' => false,
        ];
    }

    private static function day(string $text): DateTimeImmutable
    {
        if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/D', $text) !== 1) {
            throw new InvalidArgumentException('Data de assinatura invalida.');
        }

        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $text, new DateTimeZone('UTC'));
        if (!$day || $day->format('Y-m-d') !== $text) {
            throw new InvalidArgumentException('Data de assinatura invalida.');
        }

        return $day;
    }
}
