<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-215 / IN-118: combines two in-memory inspectors into one deny-only report.
 * Never returns cell/header values or grants permission to import.
 */
final class PortableCsvImportReadinessSummary
{
    /** @return array<string,mixed> Aggregate warnings and counters only */
    public function inspect(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
    ): array {
        try {
            $preview = (new PortableCsvImportPreview())->inspect(
                $csv, $delimiter, $bindings, $hasHeader,
            );
            $headers = (new PortableCsvHeaderRiskSummary())->inspect(
                $csv, $delimiter, $hasHeader,
            );
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Revisao agregada CSV local invalida.');
        }

        // The two independent inspectors must agree on the record shape.
        if ($preview['local_source_column_count'] !== $headers['local_source_column_count']
            || $preview['local_data_row_count'] !== $headers['local_data_row_count']
            || $preview['header_claimed'] !== $headers['header_claimed']) {
            throw new InvalidArgumentException('Revisao agregada CSV local invalida.');
        }

        $warnings = array_values(array_unique(array_merge(
            $preview['warning_codes'],
            $headers['warning_codes'],
        )));

        return [
            'header_claimed' => $hasHeader,
            'local_source_column_count' => $preview['local_source_column_count'],
            'local_data_row_count' => $preview['local_data_row_count'],
            'local_selected_field_count' => $preview['local_selected_field_count'],
            'local_omitted_column_count' => $preview['local_omitted_column_count'],
            'local_empty_mapped_value_count' => $preview['local_empty_mapped_value_count'],
            'local_source_formula_refused_cell_count' => $preview['local_source_formula_refused_cell_count'],
            'local_mapped_formula_refused_cell_count' => $preview['local_mapped_formula_refused_cell_count'],
            'local_empty_header_count' => $headers['local_empty_header_count'],
            'local_duplicate_exact_header_count' => $headers['local_duplicate_exact_header_count'],
            'local_header_refused_cell_count' => $headers['local_header_refused_cell_count'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'unfulfilled_gate_codes' => $preview['unfulfilled_gate_codes'],
            'informational_only' => true,
            'header_meaning_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'consent_evidence_verified' => false,
            'can_import' => false,
            'can_export' => false,
            'can_merge' => false,
            'can_persist' => false,
        ];
    }
}
