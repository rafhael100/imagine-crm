<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-212 / IN-118: explicit positional mapping to illustrative CRM fields.
 *
 * This transforms only bounded in-memory untrusted strings, never adds
 * authority, verifies consent, infers schema, merges persons, or writes data.
 * Target labels are design candidates, not production CRM column names.
 */
final class PortableCsvFieldMapping
{
    private const TARGETS = [
        'name', 'email', 'phone', 'origin',
        'consent', 'notes', 'status',
    ];

    /**
     * @param mixed $table Rectangular list<list<string>>, already in memory
     * @param mixed $bindings List of {source_position:int,target_field:string}
     * @param mixed $hasHeader Whether first source record is *claimed* header
     * @return array<string,mixed> Explicitly untrusted mapped record values
     */
    public function map(mixed $table, mixed $bindings, mixed $hasHeader = false): array
    {
        if (!is_array($bindings) || !array_is_list($bindings)
            || $bindings === [] || count($bindings) > count(self::TARGETS)
            || !is_bool($hasHeader)) {
            self::invalid();
        }

        $positions = [];
        $targets = [];
        $seenSource = [];
        $seenTargets = [];

        foreach ($bindings as $binding) {
            if (!is_array($binding) || count($binding) !== 2) {
                self::invalid();
            }
            $keys = array_keys($binding);
            sort($keys);
            if ($keys !== ['source_position', 'target_field']
                || !is_int($binding['source_position'])
                || $binding['source_position'] < 0
                || isset($seenSource[$binding['source_position']])
                || !is_string($binding['target_field'])
                || !in_array($binding['target_field'], self::TARGETS, true)
                || isset($seenTargets[$binding['target_field']])) {
                self::invalid();
            }
            $position = $binding['source_position'];
            $target = $binding['target_field'];
            $seenSource[$position] = true;
            $seenTargets[$target] = true;
            $positions[] = $position;
            $targets[] = $target;
        }

        try {
            $result = (new PortableCsvColumnProjection())->project(
                $table, $positions, $hasHeader,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $header = null;
        if ($result['untrusted_header'] !== null) {
            $header = array_combine($targets, $result['untrusted_header']);
        }

        $mapped = [];
        foreach ($result['untrusted_data_rows'] as $row) {
            $mapped[] = array_combine($targets, $row);
        }

        $consentPresent = in_array('consent', $targets, true);
        return [
            'target_field_codes' => $targets,
            'source_column_count' => $result['source_column_count'],
            'mapped_field_count' => $result['selected_column_count'],
            'source_record_count' => $result['source_record_count'],
            'data_row_count' => $result['data_row_count'],
            'header_claimed' => $hasHeader,
            'untrusted_header_titles' => $header,
            'untrusted_mapped_rows' => $mapped,
            'consent_field_present_claimed' => $consentPresent,
            'warning_codes' => $consentPresent
                ? ['CONSENT_COLUMN_DOES_NOT_VERIFY_LEGAL_CONSENT']
                : [],
            'schema_verified' => false,
            'source_values_verified' => false,
            'consent_evidence_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_import' => false,
            'can_merge' => false,
            'can_confirm_consent' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Mapeamento de campos CSV em memoria invalido.');
    }
}
