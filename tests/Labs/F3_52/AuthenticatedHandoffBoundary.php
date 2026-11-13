<?php
declare(strict_types=1);

namespace Tests\Labs\F3_52;

/**
 * Laboratory-only verification boundary, deliberately disconnected from runtime.
 * A matching MAC is NOT tenant authentication, an atomic replay claim, or CRM ACK.
 */
final class AuthenticatedHandoffBoundary
{
    private const FIELDS = [
        'protocol','schema_version','organization_reference','binding_reference',
        'submission_reference','site_reference','form_reference','destination_reference',
    ];
    private const BOUND = [
        'organization_reference','binding_reference','site_reference',
        'form_reference','destination_reference',
    ];

    public function inspect(
        array $envelope, array $serverBinding, array $authentication,
        string $requestKey, int $now
    ): array {
        $deny = static fn (string $reason): array => self::result($reason);
        if (strlen($requestKey) < 32 || strlen($requestKey) > 128) {
            return $deny('SERVICE_KEY_UNAVAILABLE');
        }
        if (array_keys($envelope) !== self::FIELDS
            || ($envelope['protocol'] ?? null) !== 'imagine.lead-intake'
            || ($envelope['schema_version'] ?? null) !== 1) {
            return $deny('CONTRACT_REJECTED');
        }
        if (array_keys($serverBinding) !== self::BOUND) {
            return $deny('SERVER_BINDING_REQUIRED');
        }
        foreach (self::BOUND as $key) {
            if (!self::isUuid($envelope[$key])
                || !self::isUuid($serverBinding[$key])
                || !hash_equals($serverBinding[$key], $envelope[$key])) {
                return $deny('PINNED_CONTEXT_REJECTED');
            }
        }
        if (!self::isUuid($envelope['submission_reference'])) {
            return $deny('SUBMISSION_REJECTED');
        }
        if (array_keys($authentication) !== ['issued_at','expires_at','nonce','mac']) {
            return $deny('AUTHENTICATION_REJECTED');
        }
        $iat = $authentication['issued_at'];
        $exp = $authentication['expires_at'];
        $nonce = $authentication['nonce'];
        $mac = $authentication['mac'];
        if (!is_int($iat) || !is_int($exp) || !self::isUuid($nonce)
            || !is_string($mac) || preg_match('/\A[a-f0-9]{64}\z/D', $mac) !== 1
            || $iat > $now + 30 || $iat < $now - 120 || $exp <= $now
            || $exp <= $iat || $exp > $iat + 120) {
            return $deny('AUTHENTICATION_REJECTED');
        }
        $parts = ['imagine.portal.operational-preparation.v1'];
        foreach (self::BOUND as $field) {
            $parts[] = $envelope[$field];
        }
        $parts[] = $envelope['submission_reference'];
        array_push($parts, (string) $iat, (string) $exp, $nonce);
        $expected = hash_hmac('sha256', implode("\n", $parts), $requestKey);
        if (!hash_equals($expected, $mac)) {
            return $deny('AUTHENTICATION_REJECTED');
        }
        // This intentionally cannot claim or look up a persistent nonce or lead.
        return self::result('SIGNED_FRAME_REQUIRES_ATOMIC_NONCE_CLAIM');
    }

    private static function isUuid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/D', $value) === 1;
    }

    private static function result(string $reason): array
    {
        return [
            'schema'=>'imagine.crm.f352.preparation.v1',
            'reasonCode'=>$reason,
            'serviceAuthenticatedForProduction'=>false,
            'tenantAuthenticatedForProduction'=>false,
            'nonceClaimedDurably'=>false,
            'leadPersisted'=>false,
            'canAcknowledgeVisitor'=>false,
            'canDeploy'=>false,
        ];
    }
}
