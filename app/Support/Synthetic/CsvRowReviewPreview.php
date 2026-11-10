<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * Read-only, bounded, position-only diagnosis of invented CSV-like rows.
 *
 * This does not parse CSV files, identify users/tenants, approve imports,
 * transform records, skip rows, or write anything. A clean synthetic sample
 * still REQUIRES independent human and security decisions.
 */
final class CsvRowReviewPreview
{
    /**
     * @param list<list<string>> $rows Invented, already split cell matrix.
     * @param list<array{column:int,target:string}> $mapping Fixture-only, position-based proposal.
     * @return array{
     *   data_rows:int,expected_columns:int,rows_with_findings:int,
     *   rows_without_detected_findings:int,header_cell_warning_count:int,
     *   global_issue_codes:list<string>,unmapped_columns:list<int>,
     *   row_reviews:list<array{
     *     row:int,actual_columns:int,issue_codes:list<string>,
     *     cell_findings:list<array{column:int,code:string}>,
     *     review_required:bool
     *   }>,
     *   review_required:bool,import_permitted:bool
     * }
     */
    public static function inspect(array $rows, array $mapping = []): array
    {
        // Validate the complete matrix and every fixture mapping first.
        // These preexisting guards reject oversize and malformed inputs.
        $plan = CsvMappingPlanPreview::inspect($rows, $mapping);
        $cellRisk = CsvCellRiskPreview::inspect($rows);

        $cellFindingsByRow = [];
        $headerWarnings = 0;
        foreach ($cellRisk['findings'] as $finding) {
            if ($finding['row'] === 1) {
                ++$headerWarnings;
                continue;
            }

            $cellFindingsByRow[$finding['row']][] = [
                'column' => $finding['column'],
                'code' => $finding['code'],
            ];
        }

        $reviews = [];
        $rowsWithFindings = 0;
        $expectedColumns = $plan['header_columns'];
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue; // header is inspected globally, not as a contact
            }

            $rowNumber = $index + 1; // 1-based position in the original matrix
            $warnings = $cellFindingsByRow[$rowNumber] ?? [];
            $codes = [];

            if (count($row) !== $expectedColumns) {
                $codes[] = 'column_count_mismatch';
            }
            if ($warnings !== []) {
                $codes[] = 'risky_cells';
            }
            if ($codes !== []) {
                ++$rowsWithFindings;
            }

            $reviews[] = [
                'row' => $rowNumber,
                'actual_columns' => count($row),
                'issue_codes' => $codes,
                'cell_findings' => $warnings,
                // No row, even one with no detected issues, is approved.
                'review_required' => true,
            ];
        }

        return [
            'data_rows' => $plan['data_rows'],
            'expected_columns' => $expectedColumns,
            'rows_with_findings' => $rowsWithFindings,
            'rows_without_detected_findings' => count($reviews) - $rowsWithFindings,
            'header_cell_warning_count' => $headerWarnings,
            'global_issue_codes' => $plan['issue_codes'],
            'unmapped_columns' => $plan['unmapped_columns'],
            'row_reviews' => $reviews,
            'review_required' => true,
            'import_permitted' => false,
        ];
    }
}
