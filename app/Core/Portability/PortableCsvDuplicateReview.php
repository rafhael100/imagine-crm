<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;
use JsonException;

/**
 * F3-216 / IN-118: bounded, exact-row repetition diagnostics in memory.
 *
 * Not person deduplication. Never stores identities, returns identifiers,
 * hashes or row values, infers permissions or writes data.
 */
final class PortableCsvDuplicateReview
{
    /**
     * @param mixed $csv Untrusted bounded local CSV string
     * @param mixed $delimiter Explicit 'comma' or 'semicolon'
     * @param mixed $bindings Explicit source -> candidate field mapping
     * @param mixed $hasHeader Caller claim, never schema evidence
     * @return array<string,mixed> Aggregate counters and fixed warning codes
     */
    public function inspect(
        mixed $csv,
        mixed $delimiter,
        mixed $bindings,
        mixed $hasHeader,
    ): array {
        try {
            $source = (new PortableCsvTableCodec())->decode($csv, $delimiter);
            $mapped = (new PortableCsvFieldMapping())->map(
                $source, $bindings, $hasHeader,
            );

            $sourceRows = $hasHeader ? array_slice($source, 1) : $source;
            $sourceRepeats = self::repetitionCount($sourceRows);
            $mappedRepeats = self::repetitionCount($mapped['untrusted_mapped_rows']);
        } catch (InvalidArgumentException|JsonException) {
            throw new InvalidArgumentException('Revisao local de repeticoes CSV invalida.');
        }

        // Every exact repeat in the source must also repeat after projection.
        // Excess mapped repeats are ambiguous collisions from dropped columns,
        // not proof of duplicate people or of consent.
        if ($mappedRepeats < $sourceRepeats) {
            throw new InvalidArgumentException('Revisao local de repeticoes CSV invalida.');
        }

        $projectionCollisions = $mappedRepeats - $sourceRepeats;
        $warnings = ['ROW_IDENTITY_AND_DUPLICATE_POLICY_NOT_VERIFIED'];
        if ($sourceRepeats > 0) {
            $warnings[] = 'EXACT_SOURCE_RECORDS_REPEATED_IN_LOCAL_SAMPLE';
        }
        if ($projectionCollisions > 0) {
            $warnings[] = 'DISTINCT_SOURCE_RECORDS_COLLAPSE_AFTER_COLUMN_MAPPING';
        }

        return [
            'header_claimed' => $hasHeader,
            'local_data_row_count' => $mapped['data_row_count'],
            'local_source_column_count' => $mapped['source_column_count'],
            'local_selected_field_count' => $mapped['mapped_field_count'],
            'local_exact_source_repeated_row_count' => $sourceRepeats,
            'local_exact_mapped_repeated_row_count' => $mappedRepeats,
            'local_projection_collision_extra_row_count' => $projectionCollisions,
            'warning_codes' => $warnings,
            'warning_count' => count($warnings),
            'informational_only' => true,
            'duplicate_person_identity_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'consent_evidence_verified' => false,
            'can_import' => false,
            'can_deduplicate' => false,
            'can_merge' => false,
            'can_persist' => false,
        ];
    }

    /**
     * Exact JSON encoding preserves string types, field order and boundaries.
     * Keys are kept only in this bounded execution; none leave the method.
     *
     * @param list<array<int|string, string>> $rows
     */
    private static function repetitionCount(array $rows): int
    {
        $seen = [];
        $repeated = 0;
        foreach ($rows as $row) {
            $fingerprint = json_encode($row, JSON_THROW_ON_ERROR);
            if (isset($seen[$fingerprint])) {
                ++$repeated;
            } else {
                $seen[$fingerprint] = true;
            }
        }

        return $repeated;
    }
}
