<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R03: bounded, DENY-ONLY batch preview for fictional Kanban cards.
 *
 * All visibility, selection, event history and field-presence facts are caller
 * claims. They are not server-verified membership, authorization or a lock.
 * Never use this preview to execute or authorize database writes.
 */
final class SyntheticKanbanBatchStagePreview
{
    private const MAX_CARDS = 12;

    /**
     * @param mixed $cards 1..12 strict synthetic card claims (F01..F12).
     * @param mixed $draggedReference A single invented F01..F12 reference.
     * @param mixed $targetStage One of the four invented stages (0..3).
     * @return array<string, mixed> Numeric/allowlisted review only; no IDs/PII.
     */
    public function inspect(mixed $cards, mixed $draggedReference, mixed $targetStage): array
    {
        if (!is_array($cards) || !array_is_list($cards)
            || count($cards) < 1 || count($cards) > self::MAX_CARDS
            || !self::isFictionalId($draggedReference)
            || !is_int($targetStage) || $targetStage < 0 || $targetStage > 3) {
            self::invalid();
        }

        $byReference = [];
        $previews = [];
        $inspector = new SyntheticStageMoveReadinessInspector();

        foreach ($cards as $position => $card) {
            if (!is_array($card) || count($card) !== 7) {
                self::invalid();
            }
            $keys = array_keys($card);
            sort($keys);
            if ($keys !== [
                'events', 'expected_revision', 'field_presence', 'ref',
                'requirements', 'selected', 'visible',
            ] || !self::isFictionalId($card['ref'])
                || isset($byReference[$card['ref']])
                || !is_bool($card['selected']) || !is_bool($card['visible'])) {
                self::invalid();
            }
            $byReference[$card['ref']] = $position;

            // Check ALL records, even filtered-out records; invalid/PII-laden
            // inputs cannot hide behind a selected/visible state.
            $previews[] = $inspector->inspect(
                $card['events'], $card['expected_revision'],
                $targetStage, $card['field_presence'], $card['requirements'],
            );
        }

        if (!array_key_exists($draggedReference, $byReference)) {
            self::invalid();
        }

        $dragPosition = $byReference[$draggedReference];
        $dragged = $cards[$dragPosition];
        $mode = $dragged['selected'] ? 'selected_group' : 'single_card';
        $dragVisible = $dragged['visible'];

        $scoped = [];
        $criteria = [];
        $blocked = [];
        foreach ($cards as $position => $card) {
            if (!$dragVisible || !$card['visible']) {
                continue;
            }
            if ($mode === 'selected_group'
                ? !$card['selected'] : $position !== $dragPosition) {
                continue;
            }

            $preview = $previews[$position];
            $scoped[] = $position;
            if ($preview['reason_code'] === 'FIELDS_PRESENT_UNVERIFIED') {
                $criteria[] = $position;
            } else {
                $blocked[] = [
                    'position' => $position,
                    'reason_code' => $preview['reason_code'],
                    'missing_count' => $preview['missing_count'],
                    'missing_field_codes' => $preview['missing_field_codes'],
                ];
            }
        }

        // Only array positions leave this boundary; synthetic reference IDs,
        // source event times and untrusted field values never leave.
        return [
            'mode' => $mode,
            'card_count' => count($cards),
            'dragged_visible_claimed' => $dragVisible,
            'scope_positions' => $scoped,
            'criteria_positions' => $criteria,
            'blocked' => $blocked,
            'scope_count' => count($scoped),
            'criteria_count' => count($criteria),
            'blocked_count' => count($blocked),
            'selection_verified' => false,
            'visibility_verified' => false,
            'identity_verified' => false,
            'tenant_verified' => false,
            'concurrency_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_bulk_move' => false,
            'can_move' => false,
            'can_persist' => false,
        ];
    }

    private static function isFictionalId(mixed $candidate): bool
    {
        return is_string($candidate)
            && preg_match('/\AF(0[1-9]|1[0-2])\z/D', $candidate) === 1;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Lote Kanban sintetico invalido.');
    }
}
