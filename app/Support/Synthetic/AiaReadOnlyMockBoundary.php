<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * CRM-side interoperability check for the Agents F1-12 OP-01 mock only.
 *
 * A correctly shaped request is still ALWAYS denied: a claimed principal,
 * tenant or purpose is not authentication, membership, ACL or consent.
 * No PII, storage, API, network, token or runtime binding.
 */
final class AiaReadOnlyMockBoundary
{
    private const KEYS = [
        'contract', 'request_ref', 'operation', 'tenant_ref',
        'principal_ref', 'resource_ref', 'purpose_ref', 'fields',
    ];

    /**
     * Syntactic compatibility only. NEVER grants any read permission.
     *
     * @param array<mixed> $wire
     */
    public static function hasFixtureShape(array $wire): bool
    {
        if (count($wire) !== count(self::KEYS)) {
            return false;
        }
        $keys = array_keys($wire);
        sort($keys, SORT_STRING);
        $expected = self::KEYS;
        sort($expected, SORT_STRING);
        return $keys === $expected
            && $wire['contract'] === 'SYNTHETIC_CRM_AIA_READ_V1'
            && $wire['operation'] === 'crm.lead.read_minimal'
            && self::mockRefsAreValid($wire)
            && self::fieldsAreValid($wire['fields']);
    }

    /**
     * @param array<mixed> $wire
     * @return array{contract:string,state:string,projection:null,real_authorization:false,external_calls:0}
     */
    public static function inspect(array $wire): array
    {
        // Intentional uniform denial, even for well-formed fixture requests.
        // Shape matching is testable above, but conveys no service identity.
        return self::denied();
    }

    /** @param array<string,mixed> $wire */
    private static function mockRefsAreValid(array $wire): bool
    {
        foreach (['request_ref','tenant_ref','principal_ref','resource_ref','purpose_ref'] as $key) {
            $value = $wire[$key];
            if (!is_string($value) || strlen($value) > 90
                || preg_match('/\ASYNTHETIC_[A-Z0-9_]{1,80}\z/D', $value) !== 1) {
                return false;
            }
        }
        return true;
    }

    private static function fieldsAreValid(mixed $fields): bool
    {
        if (!is_array($fields) || !array_is_list($fields)
            || count($fields) < 1 || count($fields) > 2) {
            return false;
        }
        foreach ($fields as $field) {
            if (!is_string($field) || !in_array($field, ['ref', 'stage'], true)) {
                return false;
            }
        }
        return count(array_unique($fields)) === count($fields);
    }

    /** @return array{contract:string,state:string,projection:null,real_authorization:false,external_calls:0} */
    private static function denied(): array
    {
        return [
            'contract' => 'SYNTHETIC_CRM_AIA_READ_V1',
            'state' => 'DENIED_OR_NOT_FOUND',
            'projection' => null,
            'real_authorization' => false,
            'external_calls' => 0,
        ];
    }
}
