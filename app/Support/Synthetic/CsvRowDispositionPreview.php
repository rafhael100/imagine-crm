<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;

/**
 * Simulation of row-review dispositions on a fully invented, in-memory CSV.
 *
 * The caller can forge every decision: none is an authenticated human
 * approval, a waiver, an import authorization or a business operation.
 * Only bounded row numbers and closed fixture_* states are processed.
 */
final class CsvRowDispositionPreview
{
    private const STATUS_REVIEWED = 'fixture_reviewed_no_findings';
    private const STATUS_CORRECTION = 'fixture_needs_correction';

    /**
     * @param list<list<string>> $rows Fictional, already segmented matrix.
     * @param list<array{column:int,target:string}> $mapping Fictional mapping only.
     * @param list<array{row:int,status:string}> $decisions Untrusted, simulated row selections.
     * @return array{
     *     data_rows:int,submitted_decision_count:int,pending_count:int,
     *     reviewed_no_findings_count:int,needs_correction_count:int,
     *     rows_with_detected_findings:int,global_issue_codes:list<string>,
     *     row_dispositions:list<array{row:int,has_detected_findings:bool,status:string}>,
     *     human_verified:bool,review_required:bool,import_permitted:bool
     * }
     */
    public static function summarize(
        array $rows,
        array $mapping = [],
        array $decisions = [],
    ): array {
        // Always re-evaluate the underlying fixture, including all matrix
        // bounds and mapping checks. No caller-supplied "clean" flag is read.
        $review = CsvRowReviewPreview::inspect($rows, $mapping);

        if (!array_is_list($decisions) || count($decisions) > 99
            || count($decisions) > $review['data_rows']) {
            throw new InvalidArgumentException('Invalid synthetic decision list.');
        }

        $byRow = [];
        foreach ($decisions as $decision) {
            if (!is_array($decision) || count($decision) !== 2
                || !isset($decision['row'], $decision['status'])
                || !is_int($decision['row'])
                || $decision['row'] < 2
                || $decision['row'] > $review['data_rows'] + 1
                || !is_string($decision['status'])
                || !in_array($decision['status'], [
                    self::STATUS_REVIEWED, self::STATUS_CORRECTION,
                ], true)
                || isset($byRow[$decision['row']])) {
                throw new InvalidArgumentException('Invalid synthetic row decision.');
            }

            $byRow[$decision['row']] = $decision['status'];
        }

        $rowDispositions = [];
        $pending = 0;
        $reviewedClean = 0;
        $needsCorrection = 0;

        foreach ($review['row_reviews'] as $rowReview) {
            $row = $rowReview['row'];
            $detected = $rowReview['issue_codes'] !== [];
            $status = $byRow[$row] ?? 'fixture_pending';

            // A row with flagged data cannot be described as reviewed clean.
            // Even an unflagged row is NEVER an approved import.
            if ($detected && $status === self::STATUS_REVIEWED) {
                throw new InvalidArgumentException(
                    'Cannot mark a flagged synthetic row as having no findings.',
                );
            }

            switch ($status) {
                case self::STATUS_REVIEWED:
                    ++$reviewedClean;
                    break;
                case self::STATUS_CORRECTION:
                    ++$needsCorrection;
                    break;
                default:
                    ++$pending;
            }

            $rowDispositions[] = [
                'row' => $row,
                'has_detected_findings' => $detected,
                'status' => $status,
            ];
        }

        return [
            'data_rows' => $review['data_rows'],
            'submitted_decision_count' => count($decisions),
            'pending_count' => $pending,
            'reviewed_no_findings_count' => $reviewedClean,
            'needs_correction_count' => $needsCorrection,
            'rows_with_detected_findings' => $review['rows_with_findings'],
            'global_issue_codes' => $review['global_issue_codes'],
            'row_dispositions' => $rowDispositions,
            // Inputs cannot establish identity, human intent or authority.
            'human_verified' => false,
            'review_required' => true,
            'import_permitted' => false,
        ];
    }
}
