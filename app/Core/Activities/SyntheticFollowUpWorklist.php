<?php

declare(strict_types=1);

namespace App\Core\Activities;

use InvalidArgumentException;

/**
 * F4-02: bounded offline-only worklist over anonymous synthetic timestamps.
 *
 * This returns LOCAL POSITIONS in the caller's array, never task records,
 * tenant/actor information, assignments, rights, notifications or persisted state.
 */
final class SyntheticFollowUpWorklist
{
    private const VIEWS = ['open', 'overdue', 'due_now', 'upcoming', 'completed'];

    /**
     * @param list<array{due_at:string,completed:bool}> $fixtures
     * @return array{
     *    positions:list<int>,view:string,total:int,matched:int,
     *    page:int,page_size:int,page_count:int,has_previous:bool,
     *    has_next:bool,empty:bool,authorized:false,can_notify:false
     * }
     */
    public function page(
        array $fixtures,
        string $nowUtc,
        string $view = 'open',
        int $page = 0,
        int $pageSize = 4,
    ): array {
        if (!in_array($view, self::VIEWS, true)
            || $page < 0 || $page > 19
            || $pageSize < 1 || $pageSize > 20) {
            throw new InvalidArgumentException('Invalid synthetic follow-up view.');
        }

        // The authoritative fixture *shape* and UTC time are validated first.
        // Hidden, completed and unselected rows must never bypass validation.
        $summary = (new SyntheticFollowUpQueue())->summarize($fixtures, $nowUtc);

        if ($view === 'completed') {
            $positions = [];
            foreach ($fixtures as $position => $fixture) {
                if ($fixture['completed']) {
                    $positions[] = $position;
                }
            }
            usort($positions, static fn (int $a, int $b): int =>
                strcmp($fixtures[$a]['due_at'], $fixtures[$b]['due_at']) ?: ($a <=> $b));
        } else {
            // F4-01 already sorted all open positions by due-at and stable index.
            $positions = [];
            foreach ($summary['open_positions'] as $position) {
                if ($view === 'open') {
                    $positions[] = $position;
                    continue;
                }
                // Canonical UTC ISO 8601 is fixed-length, zero-padded, and has
                // the same order under strcmp as UTC chronological order.
                $cmp = strcmp($fixtures[$position]['due_at'], $nowUtc);
                if (($view === 'overdue' && $cmp < 0)
                    || ($view === 'due_now' && $cmp === 0)
                    || ($view === 'upcoming' && $cmp > 0)) {
                    $positions[] = $position;
                }
            }
        }

        $matched = count($positions);
        if ($matched !== $summary[$view]) {
            // `open`, `overdue`, `due_now`, `upcoming`, `completed`
            // are fixed keys. A disagreement must fail, not expose a row.
            throw new InvalidArgumentException('Synthetic follow-up count mismatch.');
        }
        $pageCount = $matched === 0 ? 0 : intdiv($matched - 1, $pageSize) + 1;
        if (($pageCount === 0 && $page !== 0)
            || ($pageCount > 0 && $page >= $pageCount)) {
            throw new InvalidArgumentException('Invalid synthetic follow-up page.');
        }

        return [
            'positions' => array_values(array_slice($positions, $page * $pageSize, $pageSize)),
            'view' => $view,
            'total' => $summary['total'],
            'matched' => $matched,
            'page' => $page,
            'page_size' => $pageSize,
            'page_count' => $pageCount,
            'has_previous' => $page > 0,
            'has_next' => $pageCount > 0 && $page < $pageCount - 1,
            'empty' => $matched === 0,
            'authorized' => false,
            'can_notify' => false,
        ];
    }
}
