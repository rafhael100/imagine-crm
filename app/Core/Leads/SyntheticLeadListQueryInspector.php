<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-29: closed, synthetic list-query DESCRIPTOR inspector, never a reader.
 *
 * The "lab.lead" identifiers and fields are fixed test vocabulary only.
 * They do not describe an approved business policy or an executable SQL/HTTP
 * contract. Claimed tenant references and syntactic fields are not trusted.
 *
 * An unsupported cursor is never decoded or echoed. Pagination of real data
 * will require a separately reviewed, scoped and integrity-protected token.
 */
final class SyntheticLeadListQueryInspector
{
    private const KEYS = [
        'schema_version',
        'resource',
        'action',
        'tenant_reference',
        'fields',
        'sort_mode',
        'page_size',
        'cursor',
    ];

    private const LAB_FIELDS = [
        'lead_reference',
        'stage_label',
        'score_band',
    ];

    private const LAB_SORTS = [
        'original',
        'score_high',
        'score_low',
    ];

    /**
     * @param mixed $candidate Untrusted, exact-key associative PHP array.
     */
    public function inspect(string $expectedTenant, mixed $candidate): SyntheticLeadListQueryAssessment
    {
        if (!self::uuid($expectedTenant)
            || !is_array($candidate)
            || array_is_list($candidate)
            || count($candidate) !== count(self::KEYS)) {
            return new SyntheticLeadListQueryAssessment('CONTRACT_INVALID');
        }

        foreach (self::KEYS as $key) {
            if (!array_key_exists($key, $candidate)) {
                return new SyntheticLeadListQueryAssessment('CONTRACT_INVALID');
            }
        }

        if (!is_int($candidate['schema_version']) || $candidate['schema_version'] !== 1) {
            return new SyntheticLeadListQueryAssessment('VERSION_UNSUPPORTED');
        }

        if ($candidate['resource'] !== 'lab.lead') {
            return new SyntheticLeadListQueryAssessment('RESOURCE_UNSUPPORTED');
        }

        if ($candidate['action'] !== 'list') {
            return new SyntheticLeadListQueryAssessment('ACTION_UNSUPPORTED');
        }

        if (!is_string($candidate['tenant_reference']) || !self::uuid($candidate['tenant_reference'])) {
            return new SyntheticLeadListQueryAssessment('REFERENCE_FORMAT_INVALID');
        }

        if (strtolower($candidate['tenant_reference']) !== strtolower($expectedTenant)) {
            return new SyntheticLeadListQueryAssessment('SCOPE_MISMATCH');
        }

        $fields = $candidate['fields'];
        if (!is_array($fields) || !array_is_list($fields)
            || count($fields) < 1 || count($fields) > count(self::LAB_FIELDS)) {
            return new SyntheticLeadListQueryAssessment('FIELDS_UNVERIFIED');
        }

        $seen = [];
        foreach ($fields as $field) {
            if (!is_string($field) || !in_array($field, self::LAB_FIELDS, true) || isset($seen[$field])) {
                return new SyntheticLeadListQueryAssessment('FIELDS_UNVERIFIED');
            }
            $seen[$field] = true;
        }

        if (!is_string($candidate['sort_mode'])
            || !in_array($candidate['sort_mode'], self::LAB_SORTS, true)) {
            return new SyntheticLeadListQueryAssessment('SORT_UNVERIFIED');
        }

        if (!is_int($candidate['page_size'])
            || $candidate['page_size'] < 1 || $candidate['page_size'] > 20) {
            return new SyntheticLeadListQueryAssessment('PAGE_SIZE_INVALID');
        }

        // Any supplied cursor, including an empty string, is untrusted.
        // A future opaque cursor must be authenticated and bound to tenant,
        // actor, filter, projection, position and lifetime before use.
        if ($candidate['cursor'] !== null) {
            return new SyntheticLeadListQueryAssessment('CURSOR_UNVERIFIED');
        }

        // This return value states only that a limited LAB descriptor has
        // a coherent shape. It does not confirm the tenant or authorize reads.
        return new SyntheticLeadListQueryAssessment('STRUCTURE_ONLY_UNVERIFIED');
    }

    private static function uuid(string $value): bool
    {
        return preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $value) === 1
            && strtolower($value) !== '00000000-0000-0000-0000-000000000000';
    }
}
