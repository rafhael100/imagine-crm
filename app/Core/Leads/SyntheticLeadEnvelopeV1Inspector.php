<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-11: versioned, technical ENVELOPE SHAPE PROPOSAL for synthetic tests.
 *
 * Not a wire specification for production and not a JSON parser, API
 * validator, authorization service, lead payload or authenticated binding.
 * The exact fields below require an integration/security design review.
 *
 * Note: submission content, time of first durable persistence, consent,
 * signatures and server-authenticated binding are deliberately NOT accepted.
 */
final class SyntheticLeadEnvelopeV1Inspector
{
    private const PROTOCOL = 'imagine.lead-intake';
    private const VERSION = 1;

    private const REQUIRED = [
        'protocol',
        'schema_version',
        'organization_reference',
        'binding_reference',
        'submission_reference',
        'site_reference',
        'form_reference',
        'destination_reference',
    ];

    private const REFERENCE_KEYS = [
        'organization_reference',
        'binding_reference',
        'submission_reference',
        'site_reference',
        'form_reference',
        'destination_reference',
    ];

    public function inspect(mixed $candidate): SyntheticLeadEnvelopeV1Assessment
    {
        if (!is_array($candidate)
            || !array_is_list(self::REQUIRED)
            || count($candidate) !== count(self::REQUIRED)) {
            return new SyntheticLeadEnvelopeV1Assessment('ENVELOPE_STRUCTURE_INVALID');
        }

        foreach (self::REQUIRED as $key) {
            if (!array_key_exists($key, $candidate)) {
                return new SyntheticLeadEnvelopeV1Assessment('ENVELOPE_STRUCTURE_INVALID');
            }
        }

        if (!is_string($candidate['protocol']) || $candidate['protocol'] !== self::PROTOCOL) {
            return new SyntheticLeadEnvelopeV1Assessment('PROTOCOL_UNSUPPORTED');
        }

        if (!is_int($candidate['schema_version']) || $candidate['schema_version'] !== self::VERSION) {
            return new SyntheticLeadEnvelopeV1Assessment('VERSION_UNSUPPORTED');
        }

        foreach (self::REFERENCE_KEYS as $key) {
            if (!$this->isUuid($candidate[$key])) {
                return new SyntheticLeadEnvelopeV1Assessment('REFERENCE_FORMAT_INVALID');
            }
        }

        // Syntax and a claimed destination cannot be trusted as an
        // authenticated binding; no payload or permission passes this gate.
        return new SyntheticLeadEnvelopeV1Assessment('STRUCTURE_ONLY_UNVERIFIED');
    }

    private function isUuid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $value) === 1
            && strtolower($value) !== '00000000-0000-0000-0000-000000000000';
    }
}
