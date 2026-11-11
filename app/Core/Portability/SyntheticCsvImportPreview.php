<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-53: bounded, offline CSV *preview* for synthetic fixtures only.
 *
 * Parses explicit comma or semicolon/quote/CRLF or LF text in memory. It does not
 * ingest files/uploads, store cells, infer ownership, authenticate the tenant,
 * deduplicate contacts or authorize an import. No row/cell data leaves inspect.
 * F3-55 makes this the single canonical decoder for legacy preview + mapped preview.
 */
final class SyntheticCsvImportPreview
{
    private const MAX_BYTES = 32768;
    private const MAX_ROWS_WITH_HEADER = 51;
    private const MAX_COLUMNS = 8;
    private const MAX_CELL_BYTES = 512;

    public function inspect(string $csv, string $delimiter = ','): SyntheticImportPreviewSummary
    {
        return $this->decode($csv, $delimiter)[0];
    }

    /**
     * Parse once, validate a position-only mapping without leaking headers.
     *
     * @param array<mixed> $targetLabels
     * @param array<mixed> $mappingPairs
     */
    public function inspectMapped(
        string $csv,
        array $targetLabels,
        array $mappingPairs,
        string $delimiter = ',',
    ): SyntheticImportMappedPreviewSummary {
        [$preview, $sourceLabels] = $this->decode($csv, $delimiter);

        try {
            $mapping = (new SyntheticColumnMappingInspector())->assess(
                $sourceLabels, $targetLabels, $mappingPairs,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        return new SyntheticImportMappedPreviewSummary($preview, $mapping);
    }

    /**
     * F3-56: no guessing, normalization, aliases or implicit field rights.
     * A matching label proposes only a technical POSITION, never authority.
     *
     * @param array<mixed> $targetLabels Synthetic technical labels only.
     */
    public function inspectSuggested(
        string $csv,
        array $targetLabels,
        string $delimiter = ',',
    ): SyntheticImportMappedPreviewSummary {
        [$preview, $sourceLabels] = $this->decode($csv, $delimiter);

        try {
            $proposal = (new SyntheticColumnMappingInspector())->suggestExactMatches(
                $sourceLabels, $targetLabels,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        return new SyntheticImportMappedPreviewSummary($preview, $proposal);
    }

    /**
     * F3-66: structural duplicate ROW diagnostics only. Identical, already
     * decoded cell sequences receive one-based first/repeated positions.
     * No text/hash is exposed or persisted. This is not contact dedup.
     */
    public function inspectRepeatedRows(
        string $csv,
        string $delimiter = ',',
    ): SyntheticCsvRowRepeatSummary {
        [$preview, , $rows] = $this->decode($csv, $delimiter);
        $repeated = [];

        // Bounded at 50 rows: exact in-memory comparisons avoid hash collisions
        // and avoid retaining untrusted row text as map keys or identifiers.
        foreach ($rows as $rowIndex => $row) {
            for ($firstIndex = 0; $firstIndex < $rowIndex; ++$firstIndex) {
                if ($rows[$firstIndex] === $row) {
                    $repeated[] = [
                        'firstRow' => $firstIndex + 1,
                        'repeatedRow' => $rowIndex + 1,
                    ];
                    break;
                }
            }
        }

        return new SyntheticCsvRowRepeatSummary($preview, $repeated);
    }

    /**
     * F3-69: assess caller-selected technical REQUIRED fields against a
     * bounded, fully decoded synthetic CSV, returning positions only.
     * Missing headers remain positional warnings, not inferred aliases.
     *
     * The same ASCII trim rule as SyntheticImportPreviewInspector is used
     * for empty cells so overall empty-cell counts stay consistent.
     *
     * @param array<mixed> $requiredTechnicalLabels
     */
    public function inspectRequiredCells(
        string $csv,
        array $requiredTechnicalLabels,
        string $delimiter = ',',
    ): SyntheticCsvRequiredCellsSummary {
        [$preview, $sourceLabels, $rows] = $this->decode($csv, $delimiter);

        try {
            $mapping = (new SyntheticColumnMappingInspector())->suggestExactMatches(
                $sourceLabels, $requiredTechnicalLabels,
            );
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $requiredSourcePositions = [];
        foreach ($mapping->positions as $pair) {
            $requiredSourcePositions[] = $pair['source'];
        }

        $emptyRequired = [];
        foreach ($rows as $rowIndex => $row) {
            foreach ($requiredSourcePositions as $position) {
                if (trim($row[$position - 1]) === '') {
                    $emptyRequired[] = [
                        'row' => $rowIndex + 1,
                        'column' => $position,
                    ];
                }
            }
        }

        return new SyntheticCsvRequiredCellsSummary(
            csvPreview: $preview,
            requiredSourcePositions: $requiredSourcePositions,
            missingRequiredTargetPositions: $mapping->unmappedTargetPositions,
            emptyRequiredCellPositions: $emptyRequired,
        );
    }

    /** @return array{SyntheticImportPreviewSummary, list<string>, list<list<string>>} */

    private function decode(string $csv, string $delimiter): array
    {
        // Never guess a locale or silently reinterpret the header. Two known
        // delimiters are permitted only when the caller explicitly selects one.
        if ($delimiter !== ',' && $delimiter !== ';') {
            self::invalid();
        }
        if ($csv === '' || strlen($csv) > self::MAX_BYTES
            || preg_match('//u', $csv) !== 1 || str_contains($csv, "\0")) {
            self::invalid();
        }

        // A single UTF-8 BOM before the header is common in exported CSV.
        // It must not enter the first column label or bypass formula checks.
        if (str_starts_with($csv, "\xEF\xBB\xBF")) {
            $csv = substr($csv, 3);
        }
        if ($csv === '') {
            self::invalid();
        }

        $rows = [];
        $row = [];
        $cell = '';
        $mode = 'start'; // start | bare | quoted | closed
        $endedByRowSeparator = false;
        $rowHasRepresentation = false; // Distinguish blank line from quoted "".
        $length = strlen($csv);

        for ($i = 0; $i < $length; ++$i) {
            $char = $csv[$i];
            $endedByRowSeparator = false;

            if ($char === '"') {
                $rowHasRepresentation = true;
                if ($mode === 'start') {
                    $mode = 'quoted';
                } elseif ($mode === 'quoted') {
                    if ($i + 1 < $length && $csv[$i + 1] === '"') {
                        $cell .= '"';
                        ++$i;
                    } else {
                        $mode = 'closed';
                    }
                } else {
                    self::invalid();
                }
            } elseif ($char === $delimiter && $mode !== 'quoted') {
                $rowHasRepresentation = true;
                self::finishCell($row, $cell);
                $mode = 'start';
            } elseif (($char === "\r" || $char === "\n") && $mode !== 'quoted') {
                if (!$rowHasRepresentation) {
                    self::invalid(); // Legacy fgetcsv rejected empty physical rows.
                }
                if ($char === "\r") {
                    if ($i + 1 >= $length || $csv[$i + 1] !== "\n") {
                        self::invalid(); // Reject ambiguous CR-only line breaks.
                    }
                    ++$i;
                }
                self::finishCell($row, $cell);
                self::finishRow($rows, $row);
                $mode = 'start';
                $rowHasRepresentation = false;
                $endedByRowSeparator = true;
            } else {
                $rowHasRepresentation = true;
                if ($mode === 'closed') {
                    self::invalid(); // No characters allowed after closing quote.
                }
                $cell .= $char;
                if ($mode === 'start') {
                    $mode = 'bare';
                }
            }

            if (strlen($cell) > self::MAX_CELL_BYTES) {
                self::invalid();
            }
        }

        if ($mode === 'quoted') {
            self::invalid(); // Unterminated quoted field.
        }

        if (!$endedByRowSeparator) {
            self::finishCell($row, $cell);
            self::finishRow($rows, $row);
        }

        $columns = array_shift($rows);
        if (!is_array($columns)) {
            self::invalid();
        }

        try {
            $preview = (new SyntheticImportPreviewInspector())->inspect($columns, $rows);
        } catch (InvalidArgumentException) {
            self::invalid(); // Generic error; no original header or cell.
        }
        return [$preview, $columns, $rows];
    }

    /** @param list<string> $row */
    private static function finishCell(array &$row, string &$cell): void
    {
        $row[] = $cell;
        $cell = '';
        if (count($row) > self::MAX_COLUMNS) {
            self::invalid();
        }
    }

    /** @param list<list<string>> $rows
     *  @param list<string> $row
     */
    private static function finishRow(array &$rows, array &$row): void
    {
        $rows[] = $row;
        $row = [];
        if (count($rows) > self::MAX_ROWS_WITH_HEADER) {
            self::invalid();
        }
    }

    private static function invalid(): never
    {
        // Never include file content, PII, a cell, header or byte offset.
        throw new InvalidArgumentException('Estrutura CSV de pre-visualizacao invalida.');
    }
}
