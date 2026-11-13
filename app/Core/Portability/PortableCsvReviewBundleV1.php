<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;
use JsonException;

/**
 * F3-223 / IN-118: aggregate-only offline bundle combining TWO versioned
 * review domains without mutating PortableCsvReviewReportV1's wire schema.
 *
 * No file I/O, API, tenant authority, PII, SQL, merge or import/export.
 */
final class PortableCsvReviewBundleV1
{
    public const SCHEMA_VERSION = 1;
    public const MAX_JSON_BYTES = 12288;

    private const SIGNALS = [
        'SPREADSHEET_CONTROL_POLICY_NOT_VERIFIED',
        'SOURCE_CELLS_HAVE_ASCII_CONTROL_BYTES',
        'SOURCE_CELLS_HAVE_LINE_SEPARATOR_SIGNALS',
        'SOURCE_CELLS_HAVE_UNICODE_FORMAT_MARKS',
        'SOURCE_CELLS_HAVE_BIDI_DIRECTIONAL_CONTROLS',
        'MAPPED_VALUES_HAVE_CONTROL_SIGNALS',
    ];

    /** @return array<string,mixed> Strict fixed-key offline review bundle */
    public function build(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
        mixed $requiredFields = null,
    ): array {
        try {
            $structure = (new PortableCsvReviewReportV1())->build(
                $csv, $delimiter, $bindings, $hasHeader, $requiredFields,
            );
            $controls = (new PortableCsvControlRiskReview())->inspect(
                $csv, $delimiter, $bindings, $hasHeader,
            );

            $rows = $structure['counts']['data_rows'];
            $sourceColumns = $structure['counts']['source_columns'];
            $selectedFields = $structure['counts']['selected_fields'];
            if ($structure['protocol'] !== 'imagine.crm.portable_csv_review'
                || $structure['schema_version'] !== PortableCsvReviewReportV1::SCHEMA_VERSION
                || $structure['status'] !== 'REVIEW_ONLY_DENIED'
                || $structure['header_claimed'] !== $controls['header_claimed']
                || $rows !== $controls['local_data_row_count']
                || $sourceColumns !== $controls['local_source_column_count']
                || $selectedFields !== $controls['local_selected_field_count']) {
                self::invalid();
            }

            $counts = [
                'source_ascii_control_cells' => self::count($controls, 'local_source_ascii_control_cell_count', $rows * $sourceColumns),
                'source_line_separator_cells' => self::count($controls, 'local_source_line_separator_cell_count', $rows * $sourceColumns),
                'source_format_mark_cells' => self::count($controls, 'local_source_format_mark_cell_count', $rows * $sourceColumns),
                'source_bidi_control_cells' => self::count($controls, 'local_source_bidi_control_cell_count', $rows * $sourceColumns),
                'mapped_ascii_control_cells' => self::count($controls, 'local_mapped_ascii_control_cell_count', $rows * $selectedFields),
                'mapped_line_separator_cells' => self::count($controls, 'local_mapped_line_separator_cell_count', $rows * $selectedFields),
                'mapped_format_mark_cells' => self::count($controls, 'local_mapped_format_mark_cell_count', $rows * $selectedFields),
                'mapped_bidi_control_cells' => self::count($controls, 'local_mapped_bidi_control_cell_count', $rows * $selectedFields),
            ];

            foreach ([
                'ascii_control_cells', 'line_separator_cells',
                'format_mark_cells', 'bidi_control_cells',
            ] as $name) {
                if ($counts['mapped_'.$name] > $counts['source_'.$name]) {
                    self::invalid();
                }
            }
            if ($counts['source_bidi_control_cells'] > $counts['source_format_mark_cells']
                || $counts['mapped_bidi_control_cells'] > $counts['mapped_format_mark_cells']) {
                self::invalid();
            }

            $warnings = $controls['warning_codes'] ?? null;
            if (!is_array($warnings) || !array_is_list($warnings)
                || $warnings === [] || $warnings[0] !== self::SIGNALS[0]
                || count($warnings) > count(self::SIGNALS)
                || ($controls['warning_count'] ?? null) !== count($warnings)
                || count(array_unique($warnings)) !== count($warnings)) {
                self::invalid();
            }
            foreach ($warnings as $warning) {
                if (!is_string($warning) || !in_array($warning, self::SIGNALS, true)) {
                    self::invalid();
                }
            }

            foreach ([
                'informational_only' => true,
                'spreadsheet_client_behavior_verified' => false,
                'tenant_scope_verified' => false,
                'authorization_verified' => false,
                'can_import' => false,
                'can_export' => false,
                'can_persist' => false,
            ] as $key => $expected) {
                if (($controls[$key] ?? null) !== $expected) {
                    self::invalid();
                }
            }

            return [
                'protocol' => 'imagine.crm.portable_csv_review_bundle',
                'schema_version' => self::SCHEMA_VERSION,
                'status' => 'REVIEW_ONLY_DENIED',
                'structural_review' => $structure,
                'control_signals' => [
                    'counts' => $counts,
                    'warning_codes' => $warnings,
                    'spreadsheet_client_behavior_verified' => false,
                ],
                'authorized' => false,
                'can_import' => false,
                'can_export' => false,
                'can_merge' => false,
                'can_deduplicate' => false,
                'can_persist' => false,
            ];
        } catch (InvalidArgumentException) {
            self::invalid();
        }
    }

    public function encode(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
        mixed $requiredFields = null,
    ): string {
        try {
            $json = json_encode(
                $this->build($csv, $delimiter, $bindings, $hasHeader, $requiredFields),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
        } catch (InvalidArgumentException|JsonException) {
            self::invalid();
        }
        if (strlen($json) > self::MAX_JSON_BYTES) {
            self::invalid();
        }
        return $json;
    }

    /** @param array<string,mixed> $values */
    private static function count(array $values, string $key, int $max): int
    {
        $n = $values[$key] ?? null;
        if (!is_int($n) || $n < 0 || $n > $max) {
            self::invalid();
        }
        return $n;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Pacote de revisao CSV V1 invalido.');
    }
}
