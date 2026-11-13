<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-222 / IN-118: aggregate-only CSV invisible/control-character signals.
 *
 * Inspects bounded in-memory decoded records; flags are NOT proof of
 * spreadsheet-client behavior or grounds to release import/export. Does not
 * sanitize or return source values, identifiers, hashes, headers or positions.
 */
final class PortableCsvControlRiskReview
{
    /** @return array<string,mixed> Fixed counters and warnings, no cell data */
    public function inspect(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
    ): array {
        try {
            $table = (new PortableCsvTableCodec())->decode($csv, $delimiter);
            $mapped = (new PortableCsvFieldMapping())->map(
                $table, $bindings, $hasHeader,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $sourceRows = $hasHeader ? array_slice($table, 1) : $table;
        $source = self::countSignals($sourceRows);
        $selected = self::countSignals($mapped['untrusted_mapped_rows']);
        foreach ($selected as $signal => $count) {
            // A mapped cell is one selected source cell. It cannot introduce
            // an extra control signal absent from the raw source.
            if ($count > $source[$signal]) {
                self::invalid();
            }
        }

        $warnings = ['SPREADSHEET_CONTROL_POLICY_NOT_VERIFIED'];
        if ($source['ascii_controls'] > 0) {
            $warnings[] = 'SOURCE_CELLS_HAVE_ASCII_CONTROL_BYTES';
        }
        if ($source['line_separators'] > 0) {
            $warnings[] = 'SOURCE_CELLS_HAVE_LINE_SEPARATOR_SIGNALS';
        }
        if ($source['format_marks'] > 0) {
            $warnings[] = 'SOURCE_CELLS_HAVE_UNICODE_FORMAT_MARKS';
        }
        if ($source['bidi_controls'] > 0) {
            $warnings[] = 'SOURCE_CELLS_HAVE_BIDI_DIRECTIONAL_CONTROLS';
        }
        if (max($selected) > 0) {
            $warnings[] = 'MAPPED_VALUES_HAVE_CONTROL_SIGNALS';
        }

        return [
            'header_claimed' => $hasHeader,
            'local_source_column_count' => $mapped['source_column_count'],
            'local_selected_field_count' => $mapped['mapped_field_count'],
            'local_data_row_count' => $mapped['data_row_count'],
            'local_source_ascii_control_cell_count' => $source['ascii_controls'],
            'local_source_line_separator_cell_count' => $source['line_separators'],
            'local_source_format_mark_cell_count' => $source['format_marks'],
            'local_source_bidi_control_cell_count' => $source['bidi_controls'],
            'local_mapped_ascii_control_cell_count' => $selected['ascii_controls'],
            'local_mapped_line_separator_cell_count' => $selected['line_separators'],
            'local_mapped_format_mark_cell_count' => $selected['format_marks'],
            'local_mapped_bidi_control_cell_count' => $selected['bidi_controls'],
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'informational_only' => true,
            'spreadsheet_client_behavior_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'can_import' => false,
            'can_export' => false,
            'can_persist' => false,
        ];
    }

    /**
     * @param list<array<int|string,string>> $rows
     * @return array{ascii_controls:int,line_separators:int,format_marks:int,bidi_controls:int}
     */
    private static function countSignals(array $rows): array
    {
        $out = [
            'ascii_controls' => 0,
            'line_separators' => 0,
            'format_marks' => 0,
            'bidi_controls' => 0,
        ];
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $out['ascii_controls'] += (int) (preg_match('/[\x01-\x1F\x7F]/u', $value) === 1);
                $out['line_separators'] += (int) (preg_match('/[\r\n\x{0085}\x{2028}\x{2029}]/u', $value) === 1);
                $out['format_marks'] += (int) (preg_match('/\p{Cf}/u', $value) === 1);
                $out['bidi_controls'] += (int) (preg_match('/[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', $value) === 1);
            }
        }
        return $out;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Revisao de controles CSV local invalida.');
    }
}
