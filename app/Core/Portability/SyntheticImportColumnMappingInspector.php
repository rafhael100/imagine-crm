<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-202 / IN-118: deterministic, identity-free CSV/XLSX mapping preview.
 *
 * This accepts positional COLUMN METADATA, never real CSV headers, file bytes,
 * rows, contact values, company/tenant IDs, tokens or hashes. Target codes are
 * illustrative UI choices, not a production schema or verified right to write.
 */
final class SyntheticImportColumnMappingInspector
{
    private const MAX_ROWS = 20;
    private const MAX_COLUMNS = 12;
    private const TARGETS = [
        'ignore', 'name', 'email', 'phone',
        'origin', 'consent', 'notes', 'status',
    ];

    /**
     * @param mixed $request Fixed-key fictional table-to-field mapping
     * @return array<string,mixed> Non-authoritative risk codes and counts only
     */
    public function inspect(mixed $request): array
    {
        if (!is_array($request) || count($request) !== 6) {
            self::invalid();
        }
        $keys = array_keys($request);
        sort($keys);
        if ($keys !== [
            'columns', 'declared_duplicate_candidates', 'declared_row_count',
            'entity_kind', 'review_claimed', 'target_state',
        ] || !is_array($request['columns'])
            || !array_is_list($request['columns'])
            || count($request['columns']) > self::MAX_COLUMNS
            || !is_int($request['declared_row_count'])
            || $request['declared_row_count'] < 0
            || $request['declared_row_count'] > self::MAX_ROWS
            || !is_int($request['declared_duplicate_candidates'])
            || $request['declared_duplicate_candidates'] < 0
            || $request['declared_duplicate_candidates'] > $request['declared_row_count']
            || !is_bool($request['review_claimed'])
            || !is_string($request['entity_kind'])
            || !in_array($request['entity_kind'], ['leads', 'contacts'], true)
            || !is_string($request['target_state'])
            || !in_array($request['target_state'], ['empty', 'populated', 'unknown'], true)) {
            self::invalid();
        }

        $sourcePositions = [];
        $targetPositions = [];
        $mappedCount = 0;
        $ignoredCount = 0;
        $formulaWarnings = 0;
        $consentColumnCount = 0;

        foreach ($request['columns'] as $column) {
            if (!is_array($column) || count($column) !== 4) {
                self::invalid();
            }
            $columnKeys = array_keys($column);
            sort($columnKeys);
            if ($columnKeys !== [
                'formula_warning_count', 'nonempty_count',
                'source_position', 'target_code',
            ] || !is_int($column['source_position'])
                || $column['source_position'] < 0
                || $column['source_position'] >= count($request['columns'])
                || isset($sourcePositions[$column['source_position']])
                || !is_string($column['target_code'])
                || !in_array($column['target_code'], self::TARGETS, true)
                || !is_int($column['nonempty_count'])
                || $column['nonempty_count'] < 0
                || $column['nonempty_count'] > $request['declared_row_count']
                || !is_int($column['formula_warning_count'])
                || $column['formula_warning_count'] < 0
                || $column['formula_warning_count'] > $column['nonempty_count']) {
                self::invalid();
            }
            $sourcePositions[$column['source_position']] = true;
            $formulaWarnings += $column['formula_warning_count'];
            $targetCode = $column['target_code'];
            if ($targetCode === 'ignore') {
                ++$ignoredCount;
                continue;
            }

            ++$mappedCount;
            $targetPositions[$targetCode] = ($targetPositions[$targetCode] ?? 0) + 1;
            if ($targetCode === 'consent') {
                ++$consentColumnCount;
            }
        }

        // Because every index is < count and positions are unique, exactly
        // count([columns]) positions are present without leaking headers.
        $collisions = 0;
        foreach ($targetPositions as $occurrences) {
            if ($occurrences > 1) {
                $collisions += $occurrences - 1;
            }
        }

        $warnings = [];
        if ($request['columns'] === []) {
            $warnings[] = 'NO_SOURCE_COLUMNS_DECLARED';
        }
        if ($ignoredCount > 0) {
            $warnings[] = 'SOURCE_COLUMNS_INTENTIONALLY_UNMAPPED';
        }
        if ($collisions > 0) {
            $warnings[] = 'MULTIPLE_SOURCES_SHARE_ONE_TARGET';
        }
        if ($formulaWarnings > 0) {
            $warnings[] = 'SPREADSHEET_FORMULA_RISK_REQUIRES_REVIEW';
        }
        if ($consentColumnCount > 0) {
            // A matching column title cannot prove that a person's consent
            // was collected lawfully or permits reuse in another account.
            $warnings[] = 'CONSENT_EVIDENCE_CANNOT_BE_IMPORTED_AS_AUTHORITY';
        }
        if ($request['declared_duplicate_candidates'] > 0) {
            $warnings[] = 'DUPLICATE_CANDIDATES_REQUIRE_REVIEW';
        }
        if ($request['target_state'] === 'populated') {
            $warnings[] = 'EXISTING_TARGET_NEEDS_CONFLICT_DECISIONS';
        } elseif ($request['target_state'] === 'unknown') {
            $warnings[] = 'TARGET_STATE_NOT_VERIFIED';
        }
        if (!$request['review_claimed']) {
            $warnings[] = 'REVIEW_CONFIRMATION_NOT_CLAIMED';
        }

        return [
            'entity_kind' => $request['entity_kind'],
            'declared_row_count' => $request['declared_row_count'],
            'declared_source_column_count' => count($request['columns']),
            'mapped_column_count' => $mappedCount,
            'ignored_column_count' => $ignoredCount,
            'target_collision_count' => $collisions,
            'declared_formula_warning_count' => $formulaWarnings,
            'declared_duplicate_candidate_count' => $request['declared_duplicate_candidates'],
            'consent_column_present_claimed' => $consentColumnCount > 0,
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'field_mapping_verified' => false,
            'row_content_verified' => false,
            'target_schema_verified' => false,
            'consent_evidence_verified' => false,
            'duplicate_decisions_verified' => false,
            'target_state_verified' => false,
            'tenant_scope_verified' => false,
            'reviewer_permissions_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_import' => false,
            'can_merge' => false,
            'can_assign' => false,
            'can_export' => false,
            'can_confirm_consent' => false,
            'can_write_file' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Mapeamento sintetico de colunas invalido.');
    }
}
