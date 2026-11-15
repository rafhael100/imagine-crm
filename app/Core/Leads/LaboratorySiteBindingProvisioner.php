<?php

declare(strict_types=1);

namespace App\Core\Leads;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use LogicException;

/**
 * F399: CI-ONLY server-owned provision/revoke of Sites -> CRM form bindings.
 *
 * No public routes, IAM, payment verification, real consent, operational role,
 * webhooks or live identities. The PostgreSQL owner is a disposable CI fixture.
 * Never inject request-controlled form/destination into the destination row.
 */
final readonly class LaboratorySiteBindingProvisioner
{
    public function __construct(private Connection $owner) {}

    /** @return array{status_code:string,can_publish:false,can_contact:false,can_activate:false} */
    public function provision(string $organization, string $order): array
    {
        $this->assertLab();
        if (!self::validInput($organization, $order)) {
            return self::result('synthetic_provision_denied');
        }

        return $this->owner->transaction(function () use ($organization, $order): array {
            $authority = $this->owner->table('public.crm_lab_site_provision_orders')
                ->where('organization_id', strtolower($organization))
                ->where('order_reference', $order)
                ->lockForUpdate()
                ->first();
            if (!$authority
                || $authority->state !== 'approved'
                || $authority->payment_state !== 'settled'
                || $authority->consent_state !== 'recorded'
                || $authority->contract_revision < 1
                || !in_array($authority->product_code, ['onepage', 'landing_page'], true)
                || ($authority->product_code === 'onepage' && $authority->source_code !== 'onepage')
                || ($authority->product_code === 'landing_page' && $authority->source_code !== 'landing')) {
                return self::result('synthetic_provision_denied');
            }

            // Only the owner-maintained authoritative registry supplies these
            // identifiers. The caller can neither set site/form/destination
            // nor create entitlements by knowing a signed lead's UUIDs.
            $existing = $this->owner->table('public.crm_lab_site_delivery_bindings')
                ->where('organization_id', strtolower($organization))
                ->where('binding_reference', $authority->binding_reference)
                ->first();
            if ($existing) {
                foreach (['site_reference', 'form_reference',
                          'destination_reference', 'source_code'] as $field) {
                    if ((string) $existing->{$field} !== (string) $authority->{$field}) {
                        return self::result('synthetic_provision_denied');
                    }
                }
                return self::result(
                    $existing->state === 'active'
                        ? 'synthetic_already_provisioned'
                        : 'synthetic_provision_denied',
                );
            }

            $this->owner->table('public.crm_lab_site_delivery_bindings')->insert([
                'organization_id' => strtolower($organization),
                'binding_reference' => $authority->binding_reference,
                'site_reference' => $authority->site_reference,
                'form_reference' => $authority->form_reference,
                'destination_reference' => $authority->destination_reference,
                'source_code' => $authority->source_code,
                'state' => 'active',
                'version' => 1,
            ]);
            $this->owner->table('public.crm_lab_site_provision_events')->insert([
                'organization_id' => strtolower($organization),
                'order_reference' => $order,
                'binding_reference' => $authority->binding_reference,
                'event_type' => 'binding_provisioned',
                'order_version' => (int) $authority->version,
            ]);
            return self::result('synthetic_provisioned');
        });
    }

    /**
     * Trigger on the authoritative order performs atomic same-transaction
     * binding revocation using the PG07 advisory-lock discipline. No second
     * application update that could leave a gap after the order is revoked.
     * @return array{status_code:string,can_publish:false,can_contact:false,can_activate:false}
     */
    public function revoke(string $organization, string $order): array
    {
        $this->assertLab();
        if (!self::validInput($organization, $order)) {
            return self::result('synthetic_revoke_denied');
        }
        return $this->owner->transaction(function () use ($organization, $order): array {
            $query = $this->owner->table('public.crm_lab_site_provision_orders')
                ->where('organization_id', strtolower($organization))
                ->where('order_reference', $order);
            $authority = (clone $query)->lockForUpdate()->first();
            if (!$authority) {
                return self::result('synthetic_revoke_denied');
            }
            if ($authority->state === 'revoked') {
                return self::result('synthetic_already_revoked');
            }
            if ($authority->state !== 'approved') {
                return self::result('synthetic_revoke_denied');
            }
            $changed = $query->where('version', $authority->version)
                ->update(['state'=>'revoked', 'version'=>((int) $authority->version)+1]);
            if ($changed !== 1) {
                throw new LogicException('CI order changed during serializable revocation.');
            }
            return self::result('synthetic_revoked');
        });
    }

    private function assertLab(): void
    {
        if (!app()->environment('testing')
            || getenv('CI') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_SITES_F399_PROVISION_CI') !== '1'
            || getenv('CRM_LEADS_LAB') !== 'synthetic-only'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || $this->owner->getDriverName() !== 'pgsql'
            || $this->owner->getDatabaseName() !== 'imagine_crm_ci'
            || $this->owner->getConfig('username') !== 'postgres') {
            throw new LogicException('CI-only provisioning not authorized; default DENY.');
        }
    }

    private static function validInput(string $organization, string $order): bool
    {
        return Str::isUuid($organization)
            && strtolower($organization) !== '00000000-0000-0000-0000-000000000000'
            && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{0,127}\z/D', $order) === 1;
    }

    private static function result(string $status): array
    {
        return [
            'status_code'=>$status,
            'can_publish'=>false,
            'can_contact'=>false,
            'can_activate'=>false,
        ];
    }
}
