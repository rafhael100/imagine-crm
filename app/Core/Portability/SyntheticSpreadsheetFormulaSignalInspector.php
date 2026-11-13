<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-205 / IN-118: content-based but offline-only formula risk signals.
 *
 * Pure triage of tiny fictional UTF-8 cells. Never parses CSV/XLSX files,
 * normalizes source data, emits cell values, sanitizes output or confirms
 * that an export is safe in Excel, LibreOffice, Sheets or another client.
 * No file IO, persistence, transport, tenant IDs or authorization.
 */
final class SyntheticSpreadsheetFormulaSignalInspector
{
    private const MAX_CELLS = 20;
    private const MAX_CELL_BYTES = 512;

    /**
     * @param mixed $cells A list of at most 20 fictional UTF-8 cell strings
     * @return array<string,mixed> Bounded aggregate risk codes, never values
     */
    public function inspect(mixed $cells): array
    {
        if (!is_array($cells) || !array_is_list($cells)
            || count($cells) > self::MAX_CELLS) {
            self::invalid();
        }

        $formula = 0;
        $invisiblePrefix = 0;
        $compatibility = 0;
        $apostrophe = 0;
        $lineBreaks = 0;
        $flagged = 0;

        foreach ($cells as $cell) {
            if (!is_string($cell) || strlen($cell) > self::MAX_CELL_BYTES
                || str_contains($cell, "\0") || preg_match('//u', $cell) !== 1) {
                self::invalid();
            }

            // Whitespace, BOM, NBSP, zero-width, directionality and format
            // controls may hide a formula prefix from naive prefix checks.
            // This is deliberately a *signal*, not a CSV parser or a safe
            // stripping/normalization policy for production.
            $candidate = preg_replace(
                '/\A(?:[\x01-\x20\x7F]|\x{00A0}|[\x{200B}-\x{200F}]|[\x{202A}-\x{202E}]|[\x{2060}-\x{2069}]|\x{FEFF})*/u',
                '',
                $cell,
            );
            if (!is_string($candidate)) {
                self::invalid();
            }

            $hasPrefix = $candidate !== $cell;
            $hasFormula = preg_match('/\A[=+\-@]/u', $candidate) === 1;
            $hasCompatibility = preg_match('/\A[＝＋－＠]/u', $candidate) === 1;
            $hasApostrophe = preg_match('/\A\x27[=+\-@＝＋－＠]/u', $candidate) === 1;
            $hasLineBreak = str_contains($cell, "\n") || str_contains($cell, "\r");

            $invisiblePrefix += (int) $hasPrefix;
            $formula += (int) $hasFormula;
            $compatibility += (int) $hasCompatibility;
            $apostrophe += (int) $hasApostrophe;
            $lineBreaks += (int) $hasLineBreak;

            if ($hasPrefix || $hasFormula || $hasCompatibility
                || $hasApostrophe || $hasLineBreak) {
                ++$flagged;
            }
        }

        $warnings = [];
        if ($cells === []) {
            $warnings[] = 'NO_FICTIONAL_CELLS_INSPECTED';
        }
        if ($formula > 0) {
            $warnings[] = 'FORMULA_PREFIX_SIGNAL';
        }
        if ($invisiblePrefix > 0) {
            $warnings[] = 'LEADING_WHITESPACE_OR_FORMAT_SIGNAL';
        }
        if ($compatibility > 0) {
            $warnings[] = 'UNICODE_COMPATIBILITY_PREFIX_REVIEW';
        }
        if ($apostrophe > 0) {
            $warnings[] = 'SPREADSHEET_ESCAPE_SEMANTICS_UNVERIFIED';
        }
        if ($lineBreaks > 0) {
            $warnings[] = 'MULTILINE_CELL_REVIEW';
        }

        return [
            'inspected_fictional_cell_count' => count($cells),
            'flagged_fictional_cell_count' => $flagged,
            'formula_prefix_count' => $formula,
            'formatting_prefix_count' => $invisiblePrefix,
            'unicode_compatibility_prefix_count' => $compatibility,
            'apostrophe_escape_claim_count' => $apostrophe,
            'multiline_cell_count' => $lineBreaks,
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'csv_parser_verified' => false,
            'unicode_normalization_verified' => false,
            'spreadsheet_client_behavior_verified' => false,
            'formula_safety_verified' => false,
            'customer_content_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_export' => false,
            'can_import' => false,
            'can_sanitize' => false,
            'can_download' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Sinais sinteticos de formula invalidos.');
    }
}
