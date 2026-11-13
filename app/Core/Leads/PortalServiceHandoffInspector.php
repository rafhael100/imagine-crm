<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-46 — pure Portal→CRM request MAC and tenant-binding compatibility check.
 *
 * This has no side effects and never grants authority. Even with a matching
 * MAC the caller must separately prove its server-side identity, rotated
 * keys, authorized tenant binding, nonce uniqueness, TLS and durable storage.
 * Never register this inspector directly as an HTTP handler or an ALLOW gate.
 */
final class PortalServiceHandoffInspector
{
    private const DOMAINS = [
        'deliver' => 'imagine.portal.synthetic-service-request.v1',
        'lookup' => 'imagine.portal.synthetic-crm-lookup.v1',
    ];

    private const PINNED_REFERENCES = [
        'organization_reference',
        'binding_reference',
        'site_reference',
        'form_reference',
        'destination_reference',
    ];

    private const SIGNED_REFERENCES = [
        'organization_reference',
        'binding_reference',
        'submission_reference',
        'site_reference',
        'form_reference',
        'destination_reference',
    ];

    private const UUID = '/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D';

    /**
     * @param mixed $candidate Exactly eight technical metadata fields.
     * @param mixed $independentBinding MUST originate independently of input.
     * @param mixed $authentication issued_at, expires_at, nonce, mac only.
     * @param string $fixtureKey CI key only; this does not consult a KMS.
     */
    public function inspect(
        mixed $candidate,
        mixed $independentBinding,
        mixed $authentication,
        int $now,
        string $fixtureKey,
        string $operation,
    ): PortalServiceHandoffAssessment {
        // Operation comes from the server-side call site, NOT request input.
        if (!array_key_exists($operation, self::DOMAINS)) {
            return new PortalServiceHandoffAssessment('OPERATION_UNSUPPORTED');
        }

        $structure = (new SyntheticLeadEnvelopeV1Inspector())->inspect($candidate);
        if ($structure->reasonCode !== 'STRUCTURE_ONLY_UNVERIFIED') {
            return new PortalServiceHandoffAssessment('ENVELOPE_INVALID');
        }

        if (!is_array($independentBinding)
            || array_is_list($independentBinding)
            || count($independentBinding) !== count(self::PINNED_REFERENCES)) {
            return new PortalServiceHandoffAssessment('INDEPENDENT_BINDING_REQUIRED');
        }
        foreach (self::PINNED_REFERENCES as $key) {
            if (!array_key_exists($key, $independentBinding)
                || !is_string($independentBinding[$key])
                || preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD',
                    $independentBinding[$key]) !== 1
                || strtolower($independentBinding[$key])
                    === '00000000-0000-0000-0000-000000000000') {
                return new PortalServiceHandoffAssessment('INDEPENDENT_BINDING_REQUIRED');
            }
            if (!hash_equals(
                strtolower($independentBinding[$key]),
                strtolower($candidate[$key]),
            )) {
                return new PortalServiceHandoffAssessment('PINNED_CONTEXT_MISMATCH');
            }
        }

        if (!is_array($authentication)
            || array_is_list($authentication)
            || count($authentication) !== 4
            || !array_key_exists('issued_at', $authentication)
            || !array_key_exists('expires_at', $authentication)
            || !array_key_exists('nonce', $authentication)
            || !array_key_exists('mac', $authentication)
            || !is_int($authentication['issued_at'])
            || !is_int($authentication['expires_at'])
            || !is_string($authentication['nonce'])
            || !is_string($authentication['mac'])
            || preg_match(self::UUID, $authentication['nonce']) !== 1
            || $authentication['nonce'] === '00000000-0000-0000-0000-000000000000'
            || preg_match('/\A[0-9a-f]{64}\z/D', $authentication['mac']) !== 1
            || strlen($fixtureKey) < 32) {
            return new PortalServiceHandoffAssessment('AUTH_INPUT_INVALID');
        }

        $issued = $authentication['issued_at'];
        $expires = $authentication['expires_at'];
        if ($now <= 0
            || $issued <= 0
            || $expires <= $issued
            || $expires - $issued > 300
            || $issued > $now + 30
            || $expires <= $now) {
            return new PortalServiceHandoffAssessment('AUTH_WINDOW_INVALID');
        }

        $message = [self::DOMAINS[$operation]];
        foreach (self::SIGNED_REFERENCES as $key) {
            $message[] = strtolower($candidate[$key]);
        }
        $message[] = (string) $issued;
        $message[] = (string) $expires;
        $message[] = $authentication['nonce'];

        $expected = hash_hmac('sha256', implode("\n", $message), $fixtureKey);
        if (!hash_equals($expected, $authentication['mac'])) {
            return new PortalServiceHandoffAssessment('AUTH_MAC_MISMATCH');
        }

        // Deliberately NOT AUTHENTICATED, ACCEPTED, REPLAY-SAFE or PERSISTED.
        return new PortalServiceHandoffAssessment('LAB_REQUEST_MAC_MATCH_UNTRUSTED');
    }
}
