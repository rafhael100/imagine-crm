<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Bounded, read-only inspection of invented CSV-like cells.
 *
 * This is NOT a CSV parser, export sanitizer, file converter, authorization
 * check, permission to import data or assurance of spreadsheet safety.
 * Actual import/export encoding and escaping policies remain unapproved.
 */
final class CsvCellRiskPreview
{
    private const MAX_ROWS = 100;
    private const MAX_COLUMNS = 25;
    private const MAX_CELL_BYTES = 4096;

    /**
     * The caller supplies an already split matrix of synthetic strings.
     * Reports contain coordinates and coarse codes only, never cell data.
     *
     * @param list<list<string>> $rows
     * @return array{
     *     rows:int,
     *     max_columns:int,
     *     flagged_count:int,
     *     findings:list<array{row:int,column:int,code:string}>
     * }
     */
    public static function inspect(array $rows): array
    {
        if (!array_is_list($rows) || count($rows) > self::MAX_ROWS) {
            throw new InvalidArgumentException('Synthetic preview requires a bounded list of rows.');
        }

        $findings = [];
        $maxColumns = 0;
        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row) || !array_is_list($row)
                || count($row) > self::MAX_COLUMNS) {
                throw new InvalidArgumentException('Invalid or oversized synthetic preview row.');
            }

            $maxColumns = max($maxColumns, count($row));
            foreach ($row as $columnIndex => $cell) {
                if (!is_string($cell) || strlen($cell) > self::MAX_CELL_BYTES) {
                    throw new InvalidArgumentException('Invalid synthetic preview cell or size.');
                }

                $code = self::classify($cell);
                if ($code !== null) {
                    $findings[] = [
                        'row' => $rowIndex + 1,
                        'column' => $columnIndex + 1,
                        'code' => $code,
                    ];
                }
            }
        }

        return [
            'rows' => count($rows),
            'max_columns' => $maxColumns,
            'flagged_count' => count($findings),
            'findings' => $findings,
        ];
    }

    private static function classify(string $value): ?string
    {
        // Embedded NUL is a distinct high-risk data-format ambiguity.
        if (str_contains($value, "\x00")) {
            return 'nul_byte';
        }

        // Look past one leading UTF-8 BOM and ASCII spaces/tab/CR/LF/FF/VT.
        // This is deliberately conservative: a negative number is flagged.
        $candidate = preg_replace(
            '/\A(?:\xEF\xBB\xBF)?[ \t\r\n\f\v]*/',
            '',
            $value,
        );
        if ($candidate === null) {
            throw new InvalidArgumentException('Unable to examine synthetic preview cell.');
        }

        if ($candidate !== '' && str_contains('=+-@', $candidate[0])) {
            return 'formula_prefix';
        }

        // A leading apostrophe is NOT accepted as a universal safe escape.
        if (str_starts_with($candidate, "'")) {
            $afterQuote = ltrim(substr($candidate, 1), " \t\r\n\f\v");
            if ($afterQuote !== '' && str_contains('=+-@', $afterQuote[0])) {
                return 'apostrophe_formula_ambiguous';
            }
        }

        if (preg_match('/[\x01-\x1f\x7f]/', $value) === 1) {
            return 'control_byte';
        }

        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return 'bom_prefix';
        }

        return null;
    }
}
