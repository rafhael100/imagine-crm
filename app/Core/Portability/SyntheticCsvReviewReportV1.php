<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-73: versioned, metadata-only serialization of a SYNTHETIC CSV preflight.
 *
 * This is not a public API or authenticated customer/report endpoint.
 * All output fields are fixed literals, booleans, integers and validated
 * one-based positions. No dynamic labels, row values, hashes, identities,
 * tenant references, correlation IDs or fields from an untrusted exception.
 */
final class SyntheticCsvReviewReportV1
{
    public const SCHEMA_VERSION = 1;
    private const REASONS = [
        'POLICY_NOT_CONFIGURED',
        'CONTRACT_INVALID',
        'SCOPE_NOT_VERIFIED',
        'CHANNEL_AUTHORITY_MISSING',
    ];

    /** @return array<string, mixed> */
    public function build(SyntheticCsvImportPreflightSummary $preflight): array
    {
        $preview = $preflight->preview->csvPreview;
        $mapping = $preflight->preview->mappingPreview;

        $rows = $preview->rowsInspected;
        $columns = $preview->columnsInspected;
        $targets = $mapping->targetColumns;
        if ($rows < 0 || $rows > 50 || $columns < 1 || $columns > 8
            || $targets < 1 || $targets > 8
            || $mapping->sourceColumns !== $columns
            || $preview->emptyCells < 0 || $preview->emptyCells > $rows * $columns
            || $preflight->decision->allowed || $preflight->canImport()
            || $preflight->canPersistLead() || $preflight->canEnableImportRoute()
            || !in_array($preflight->decision->reasonCode, self::REASONS, true)) {
            self::invalid();
        }

        $mapped = self::pairs($mapping->positions, 'source', 'target', $columns, $targets);
        $unmappedSource = self::indices($mapping->unmappedSourcePositions, $columns);
        $unmappedTarget = self::indices($mapping->unmappedTargetPositions, $targets);
        $usedSource = [];
        $usedTarget = [];
        foreach ($mapped as $pair) {
            if (isset($usedSource[$pair['source']]) || isset($usedTarget[$pair['target']])
                || in_array($pair['source'], $unmappedSource, true)
                || in_array($pair['target'], $unmappedTarget, true)) {
                self::invalid();
            }
            $usedSource[$pair['source']] = true;
            $usedTarget[$pair['target']] = true;
        }
        if (count($mapped) + count($unmappedSource) !== $columns
            || count($mapped) + count($unmappedTarget) !== $targets) {
            self::invalid();
        }

        $formula = self::pairs($preview->potentialFormulaPositions, 'row', 'column', $rows, $columns);
        $repeats = [];
        if ($preflight->repeatPreview !== null) {
            if ($preflight->repeatPreview->csvPreview != $preview) {
                self::invalid();
            }
            $repeats = self::pairs(
                $preflight->repeatPreview->repeatedRowPositions,
                'firstRow', 'repeatedRow', $rows, $rows,
            );
            $seenRepeated = [];
            foreach ($repeats as $pair) {
                if ($pair['firstRow'] >= $pair['repeatedRow']
                    || isset($seenRepeated[$pair['repeatedRow']])) {
                    self::invalid();
                }
                $seenRepeated[$pair['repeatedRow']] = true;
            }
        }

        $requiredSource = [];
        $missingHeaders = [];
        $requiredEmpty = [];
        if ($preflight->requiredCellsPreview !== null) {
            $required = $preflight->requiredCellsPreview;
            if ($required->csvPreview != $preview) {
                self::invalid();
            }
            $requiredSource = self::indices($required->requiredSourcePositions, $columns);
            $missingHeaders = self::indices($required->missingRequiredTargetPositions, 8);
            $requiredEmpty = self::pairs(
                $required->emptyRequiredCellPositions,
                'row', 'column', $rows, $columns,
            );
            foreach ($requiredEmpty as $cell) {
                if (!in_array($cell['column'], $requiredSource, true)) {
                    self::invalid();
                }
            }
        }

        return [
            'protocol' => 'imagine.crm.synthetic_csv_review',
            'schema_version' => self::SCHEMA_VERSION,
            'status' => 'REVIEW_ONLY_DENIED',
            'rows_inspected' => $rows,
            'columns_inspected' => $columns,
            'empty_cells' => $preview->emptyCells,
            'mapping' => [
                'mapped_source_to_target_positions' => $mapped,
                'unmapped_source_positions' => $unmappedSource,
                'unmapped_target_positions' => $unmappedTarget,
            ],
            'warnings' => [
                'potential_formula_positions' => $formula,
                'repeated_row_positions' => $repeats,
                'required_source_positions' => $requiredSource,
                'missing_required_header_positions' => $missingHeaders,
                'empty_required_cell_positions' => $requiredEmpty,
            ],
            'analysis_performed' => [
                'repeated_rows' => $preflight->repeatPreview !== null,
                'required_cells' => $preflight->requiredCellsPreview !== null,
            ],
            'denial_reason' => $preflight->decision->reasonCode,
            'allowed' => false,
            'can_import' => false,
            'can_persist_lead' => false,
            'can_enable_import_route' => false,
        ];
    }

    /**
     * @param array<mixed> $values
     * @return list<int>
     */
    private static function indices(array $values, int $max): array
    {
        if (!array_is_list($values) || count($values) > $max) {
            self::invalid();
        }
        $seen = [];
        foreach ($values as $number) {
            if (!is_int($number) || $number < 1 || $number > $max || isset($seen[$number])) {
                self::invalid();
            }
            $seen[$number] = true;
        }

        return $values;
    }

    /**
     * @param array<mixed> $values
     * @return list<array<string, int>>
     */
    private static function pairs(
        array $values,
        string $first,
        string $second,
        int $maxFirst,
        int $maxSecond,
    ): array {
        if (!array_is_list($values) || count($values) > 400) {
            self::invalid();
        }
        foreach ($values as $pair) {
            if (!is_array($pair) || array_keys($pair) !== [$first, $second]
                || !is_int($pair[$first]) || !is_int($pair[$second])
                || $pair[$first] < 1 || $pair[$first] > $maxFirst
                || $pair[$second] < 1 || $pair[$second] > $maxSecond) {
                self::invalid();
            }
        }

        return $values;
    }

    private static function invalid(): never
    {
        // Never embed raw CSV content, labels or malformed JSON fields in errors.
        throw new InvalidArgumentException('Relatorio CSV sintetico invalido.');
    }
}
