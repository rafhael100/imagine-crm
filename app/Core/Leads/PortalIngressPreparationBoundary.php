<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-48: composed, side-effect-free technical ingress boundary.
 *
 * This unifies F3-47's strict JSON decoding and F3-46's independently pinned
 * metadata/HMAC check, but does NOT authenticate the caller, fetch tenant
 * membership, store a lead, reserve a nonce, enqueue work or issue a receipt.
 *
 * No route/controller/middleware/worker is registered. This cannot ALLOW
 * a business effect in any environment, including testing or production.
 */
final class PortalIngressPreparationBoundary
{
    /**
     * @param string $rawJson Untrusted technical frame, never a PII payload.
     * @param mixed $serverBinding Independently resolved by future server
     *        infrastructure; using request values here proves nothing.
     * @param mixed $authentication Scalar signed request metadata only.
     */
    public function inspect(
        string $rawJson,
        mixed $serverBinding,
        mixed $authentication,
        int $now,
        string $fixtureKey,
        string $serverOperation,
    ): PortalIngressPreparationAssessment {
        try {
            $envelope = (new PortalTechnicalEnvelopeDecoder())->decode($rawJson);
        } catch (InvalidArgumentException $error) {
            // Expose only coarse, stable codes, never a JSON key/value/body.
            return new PortalIngressPreparationAssessment(
                $error->getMessage() === 'TECHNICAL_ENVELOPE_DUPLICATE_KEY'
                    ? 'DUPLICATE_JSON_KEY_REJECTED'
                    : 'TECHNICAL_JSON_REJECTED'
            );
        }

        $result=(new PortalServiceHandoffInspector())->inspect(
            $envelope,$serverBinding,$authentication,$now,
            $fixtureKey,$serverOperation
        );
        if ($result->reasonCode !== 'LAB_REQUEST_MAC_MATCH_UNTRUSTED') {
            return new PortalIngressPreparationAssessment($result->reasonCode);
        }

        // Syntactic/cryptographic integrity is NOT operational authority:
        // the binding and key supplied here are not proven server identities.
        return new PortalIngressPreparationAssessment(
            'LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED'
        );
    }
}
