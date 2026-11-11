<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Pure, zero-I/O inspection of the Agents OP-01 public mock response.
 *
 * Do NOT treat READ_SYNTHETIC_ONLY as a CRM read grant. The upstream mock
 * itself says real_authorization=false, external_calls=0; no identity,
 * consent, tenant ACL, provenance or CRM persistence can be inferred.
 *
 * The inspector never returns a projection or copies any supplied value.
 */
final class AgentsReadResponseInspector
{
    private const KEYS = [
        'contract', 'state', 'projection', 'real_authorization', 'external_calls',
    ];

    private const FIELDS = ['ref', 'stage'];
    private const SYNTHETIC_REF = '/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D';

    /** @param mixed $response Untrusted message/array from a mock service.
     * @param mixed $requestedFields The original independently selected fields.
     */
    public function inspect(mixed $response, mixed $requestedFields): AgentsReadResponseAssessment
    {
        if (!is_array($response) || array_is_list($response)
            || count($response) !== count(self::KEYS)) {
            return new AgentsReadResponseAssessment('PUBLIC_REPLY_INVALID');
        }

        $keys = array_keys($response);
        sort($keys, SORT_STRING);
        $expectedKeys = self::KEYS;
        sort($expectedKeys, SORT_STRING);

        if ($keys !== $expectedKeys
            || $response['contract'] !== 'SYNTHETIC_CRM_AIA_PUBLIC_V1'
            || $response['real_authorization'] !== false
            || $response['external_calls'] !== 0) {
            return new AgentsReadResponseAssessment('PUBLIC_REPLY_INVALID');
        }

        if ($response['state'] === 'DENIED_OR_NOT_FOUND') {
            return new AgentsReadResponseAssessment(
                $response['projection'] === null
                    ? 'SYNTHETIC_DENIAL_ONLY'
                    : 'PUBLIC_REPLY_INVALID',
            );
        }

        if ($response['state'] !== 'READ_SYNTHETIC_ONLY') {
            return new AgentsReadResponseAssessment('PUBLIC_REPLY_INVALID');
        }

        if (!is_array($requestedFields) || !array_is_list($requestedFields)
            || count($requestedFields) < 1 || count($requestedFields) > 2
            || !is_array($response['projection'])
            || array_is_list($response['projection'])
            || count($response['projection']) !== count($requestedFields)) {
            return new AgentsReadResponseAssessment('PROJECTION_INVALID');
        }

        foreach ($requestedFields as $field) {
            if (!is_string($field) || !in_array($field, self::FIELDS, true)) {
                return new AgentsReadResponseAssessment('PROJECTION_INVALID');
            }
        }
        if (count(array_unique($requestedFields)) !== count($requestedFields)
            || array_keys($response['projection']) !== $requestedFields) {
            return new AgentsReadResponseAssessment('PROJECTION_INVALID');
        }

        foreach ($response['projection'] as $value) {
            if (!is_string($value)
                || strlen($value) > 90
                || preg_match(self::SYNTHETIC_REF, $value) !== 1) {
                return new AgentsReadResponseAssessment('PROJECTION_INVALID');
            }
        }

        return new AgentsReadResponseAssessment('SYNTHETIC_PROJECTION_UNTRUSTED');
    }
}
