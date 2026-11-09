<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-21: strict in-memory mapping of synthetic column labels by positions.
 * Not an approved CRM schema, authorization check, converter or importer.
 * No network, filesystem, tenant resolution, storage or write operations.
 */
final class SyntheticColumnMappingInspector
{
    private const MAX_COLUMNS = 8;

    /**
     * @param array<mixed> $sourceLabels Synthetic technical header labels
     * @param array<mixed> $targetLabels Synthetic technical target labels
     * @param array<mixed> $mappingPairs One-based {source,target} pairs
     */
    public function assess(
        array $sourceLabels,
        array $targetLabels,
        array $mappingPairs,
    ): SyntheticColumnMappingSummary {
        self::validateLabels($sourceLabels);
        self::validateLabels($targetLabels);

        if (!array_is_list($mappingPairs)
            || count($mappingPairs) < 1
            || count($mappingPairs) > self::MAX_COLUMNS) {
            self::invalid();
        }

        $sourceSeen = [];
        $targetSeen = [];
        $positions = [];

        foreach ($mappingPairs as $pair) {
            if (!is_array($pair)
                || array_keys($pair) !== ['source', 'target']
                || !is_int($pair['source'])
                || !is_int($pair['target'])) {
                self::invalid();
            }

            $source = $pair['source'];
            $target = $pair['target'];
            if ($source < 1 || $source > count($sourceLabels)
                || $target < 1 || $target > count($targetLabels)
                || isset($sourceSeen[$source]) || isset($targetSeen[$target])) {
                self::invalid();
            }

            $sourceSeen[$source] = true;
            $targetSeen[$target] = true;
            $positions[] = ['source' => $source, 'target' => $target];
        }

        // Canonical, deterministic ordering irrespective of submitted order.
        usort($positions, static fn (array $a, array $b): int => $a['source'] <=> $b['source']);

        $unmappedSource = [];
        for ($index = 1; $index <= count($sourceLabels); $index++) {
            if (!isset($sourceSeen[$index])) {
                $unmappedSource[] = $index;
            }
        }

        $unmappedTarget = [];
        for ($index = 1; $index <= count($targetLabels); $index++) {
            if (!isset($targetSeen[$index])) {
                $unmappedTarget[] = $index;
            }
        }

        return new SyntheticColumnMappingSummary(
            sourceColumns: count($sourceLabels),
            targetColumns: count($targetLabels),
            positions: $positions,
            unmappedSourcePositions: $unmappedSource,
            unmappedTargetPositions: $unmappedTarget,
        );
    }

    /** @param array<mixed> $labels */
    private static function validateLabels(array $labels): void
    {
        if (!array_is_list($labels) || count($labels) < 1 || count($labels) > self::MAX_COLUMNS) {
            self::invalid();
        }

        $seen = [];
        foreach ($labels as $label) {
            if (!is_string($label)
                || preg_match('/\A[a-z][a-z0-9_]{0,31}\z/D', $label) !== 1
                || isset($seen[$label])) {
                self::invalid();
            }
            $seen[$label] = true;
        }
    }

    private static function invalid(): never
    {
        // Never echo a source/target label, user content or raw input.
        throw new InvalidArgumentException('Mapeamento sintetico de colunas invalido.');
    }
}
