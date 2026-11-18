<?php

declare(strict_types=1);

namespace App\Core\Leads;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use LogicException;

/**
 * F400 - CI-ONLY synthetic Portal-to-CRM service command gate.
 *
 * This is NOT an HTTP/mTLS handler, real IAM, PSP confirmation or customer
 * provisioner. Keys below are PUBLIC FAKE FIXTURES. Production callers must
 * never use this class as an authorization boundary.
 */
final readonly class LaboratoryPortalS2sBindingGateway
{
    private const DOMAIN = 'IMAGINE-F400-PORTAL-CRM-SITES-BINDING-CI-v1';

    // Deliberately public CI-only fixtures; NOT credentials or secrets.
    private const KEYS = [
        'lab-portal-crm-provision-a' => 'FAKE-PUBLIC-CI-ONLY-PORTAL-CRM-PROVISION-A-2026-NOT-A-SECRET',
        'lab-portal-crm-provision-b' => 'FAKE-PUBLIC-CI-ONLY-PORTAL-CRM-PROVISION-B-2026-NOT-A-SECRET',
        'lab-portal-crm-revoke-c' => 'FAKE-PUBLIC-CI-ONLY-PORTAL-CRM-REVOKE-C-2026-NOT-A-SECRET',
    ];

    private const FIELDS = [
        'protocol', 'sourceService', 'audienceService', 'keyId',
        'action', 'organizationReference', 'orderReference',
        'bindingReference', 'expectedOrderVersion', 'nonce',
        'issuedAt', 'expiresAt',
    ];

    public function __construct(private Connection $owner) {}

    /**
     * Fake signer only for GitHub Actions against the same fixed CI database.
     * It represents the external Portal emitter, not operational authority.
     * @param array<string,mixed> $claim
     * @return array<string,mixed>
     */
    public function signForCi(array $claim): array
    {
        $this->assertLab();
        if (!self::valid($claim, (int) ($claim['issuedAt'] ?? 0), false)) {
            throw new LogicException('Malformed fake CI Portal S2S claim.');
        }
        $key = self::KEYS[$claim['keyId']] ?? null;
        if (!is_string($key)) {
            throw new LogicException('Unknown fake CI signing key.');
        }
        $claim['signature'] = hash_hmac('sha256', self::canonical($claim), $key);
        return $claim;
    }

    /**
     * @param array<string,mixed> $wire
     * @return array{status_code:string,can_publish:false,can_contact:false,can_activate:false}
     */
    public function consume(array $wire, int $now): array
    {
        $this->assertLab();

        if (!self::valid($wire, $now, true)) {
            return self::denied();
        }
        $key = self::KEYS[$wire['keyId']] ?? null;
        if (!is_string($key)
            || !hash_equals(
                hash_hmac('sha256', self::canonical($wire), $key),
                $wire['signature'],
            )) {
            return self::denied();
        }

        return $this->owner->transaction(function () use ($wire, $now): array {
            // The key's role, audience, life window and revocation are
            // authoritative fixture-owner records, not signed assertions.
            $key = $this->owner->table('public.crm_lab_site_s2s_keys')
                ->where('key_id', $wire['keyId'])
                ->lockForUpdate()
                ->first();
            if (!$key || $key->state !== 'active'
                || $key->source_service !== $wire['sourceService']
                || $key->audience_service !== $wire['audienceService']
                || $key->allowed_action !== $wire['action']
                || (int) $key->valid_from > $now
                || (int) $key->valid_until < $now
                || (int) $key->valid_from > $wire['issuedAt']
                || (int) $key->valid_until < $wire['expiresAt']) {
                return self::denied();
            }

            $digest = hash('sha256', self::canonical($wire));
            // Unique source/nonce across keys, companies and actions.
            // Check after authenticating the key so a revoked credential
            // cannot obtain a replay acknowledgement.
            $seen = $this->owner->table('public.crm_lab_site_s2s_inbox')
                ->where('source_service', $wire['sourceService'])
                ->where('nonce', strtolower($wire['nonce']))
                ->first();
            if ($seen) {
                return $seen->command_digest === $digest
                    && $seen->key_id === $wire['keyId']
                    ? self::response('synthetic_s2s_replayed')
                    : self::denied();
            }

            $order = $this->owner->table('public.crm_lab_site_provision_orders')
                ->where('organization_id', strtolower($wire['organizationReference']))
                ->where('order_reference', $wire['orderReference'])
                ->lockForUpdate()
                ->first();
            if (!$order
                || (string) $order->binding_reference !== strtolower($wire['bindingReference'])
                || (int) $order->version !== $wire['expectedOrderVersion']) {
                return self::denied();
            }

            $operator = new LaboratorySiteBindingProvisioner($this->owner);
            $result = $wire['action'] === 'provision'
                ? $operator->provision(
                    $wire['organizationReference'], $wire['orderReference'],
                )
                : $operator->revoke(
                    $wire['organizationReference'], $wire['orderReference'],
                );

            $accepted = $wire['action'] === 'provision'
                ? in_array(
                    $result['status_code'],
                    ['synthetic_provisioned', 'synthetic_already_provisioned'],
                    true,
                )
                : in_array(
                    $result['status_code'],
                    ['synthetic_revoked', 'synthetic_already_revoked'],
                    true,
                );
            if (!$accepted) {
                return self::denied();
            }

            // Atomic durable receipt with the binding side-effect. If the
            // DB transaction fails, both action and receipt roll back.
            $this->owner->table('public.crm_lab_site_s2s_inbox')->insert([
                'source_service' => $wire['sourceService'],
                'nonce' => strtolower($wire['nonce']),
                'key_id' => $wire['keyId'],
                'organization_id' => strtolower($wire['organizationReference']),
                'order_reference' => $wire['orderReference'],
                'action' => $wire['action'],
                'command_digest' => $digest,
                'outcome' => $result['status_code'],
            ]);
            return self::response($result['status_code']);
        });
    }

    /** @param array<string,mixed> $claim */
    private static function canonical(array $claim): string
    {
        return self::DOMAIN . "\n"
            . implode("\n", array_map(
                static fn (string $name): string => (string) $claim[$name],
                self::FIELDS,
            ));
    }

    /** @param array<string,mixed> $wire */
    private static function valid(array $wire, int $now, bool $withSignature): bool
    {
        $expected = $withSignature
            ? [...self::FIELDS, 'signature']
            : self::FIELDS;
        if (array_diff(array_keys($wire), $expected) !== []
            || array_diff($expected, array_keys($wire)) !== []) {
            return false;
        }
        foreach ([
            'protocol', 'sourceService', 'audienceService', 'keyId',
            'action', 'organizationReference', 'orderReference',
            'bindingReference', 'nonce',
        ] as $field) {
            if (!is_string($wire[$field])) {
                return false;
            }
        }
        if ($wire['protocol'] !== 'imagine-s2s-f400-ci-v1'
            || $wire['sourceService'] !== 'imagine-portal'
            || $wire['audienceService'] !== 'imagine-crm'
            || !in_array($wire['action'], ['provision', 'revoke'], true)
            || !array_key_exists($wire['keyId'], self::KEYS)
            || !Str::isUuid($wire['organizationReference'])
            || !Str::isUuid($wire['bindingReference'])
            || !Str::isUuid($wire['nonce'])
            || strlen($wire['orderReference']) > 128
            || preg_match(
                '/\A[A-Za-z0-9][A-Za-z0-9._:-]{0,127}\z/D',
                $wire['orderReference'],
            ) !== 1) {
            return false;
        }
        foreach (['expectedOrderVersion', 'issuedAt', 'expiresAt'] as $field) {
            if (!is_int($wire[$field])) {
                return false;
            }
        }
        if ($wire['expectedOrderVersion'] < 1
            || $wire['issuedAt'] <= 0
            || $wire['expiresAt'] <= $wire['issuedAt']
            || $wire['expiresAt'] - $wire['issuedAt'] > 300
            || $wire['issuedAt'] > $now + 30
            || $wire['issuedAt'] < $now - 300
            || $wire['expiresAt'] < $now) {
            return false;
        }
        return !$withSignature
            || (is_string($wire['signature'])
                && strlen($wire['signature']) === 64
                && ctype_xdigit($wire['signature']));
    }

    private function assertLab(): void
    {
        if (!app()->environment('testing')
            || getenv('CI') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_SITES_F399_PROVISION_CI') !== '1'
            || getenv('CRM_SITES_F400_S2S_CI') !== '1'
            || getenv('CRM_LEADS_LAB') !== 'synthetic-only'
            || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'imagine_crm_ci'
            || $this->owner->getDriverName() !== 'pgsql'
            || $this->owner->getDatabaseName() !== 'imagine_crm_ci'
            || $this->owner->getConfig('username') !== 'postgres') {
            throw new LogicException('F400 fake S2S authorization unavailable outside disposable CI.');
        }
    }

    /** @return array{status_code:string,can_publish:false,can_contact:false,can_activate:false} */
    private static function response(string $status): array
    {
        return [
            'status_code' => $status,
            'can_publish' => false,
            'can_contact' => false,
            'can_activate' => false,
        ];
    }

    /** @return array{status_code:string,can_publish:false,can_contact:false,can_activate:false} */
    private static function denied(): array
    {
        return self::response('synthetic_s2s_denied');
    }
}
