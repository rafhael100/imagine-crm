<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-214 / IN-118: aggregate-only inspection of *claimed* CSV headers.
 *
 * No I/O, identity, schema inference, normalization of customer records,
 * authorization or persistence. Never returns header strings.
 */
final class PortableCsvHeaderRiskSummary
{
    /**
     * @param mixed $csv Bounded in-memory CSV text, not an uploaded file
     * @param mixed $delimiter 'comma' or 'semicolon'
     * @param mixed $hasHeader Explicit caller claim, not verified
     * @return array<string,mixed> Aggregate counters and fixed codes only
     */
    public function inspect(mixed $csv, mixed $delimiter, mixed $hasHeader): array
    {
        if (!is_bool($hasHeader)) {
            self::invalid();
        }

        try {
            $codec = new PortableCsvTableCodec();
            $rows = $codec->decode($csv, $delimiter);
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        $width = count($rows[0]);
        $empty = 0;
        $duplicates = 0;
        $unsafe = 0;
        $seen = [];

        if ($hasHeader) {
            foreach ($rows[0] as $header) {
                // Empty means empty or ASCII whitespace only; no assertion
                // about semantics, Unicode confusables or column identity.
                if (trim($header, " \t\r\n\v\f") === '') {
                    ++$empty;
                }
                // Exact byte duplicate only; deliberately no lossy folding.
                if (isset($seen[$header])) {
                    ++$duplicates;
                }
                $seen[$header] = true;

                try {
                    $codec->encode([[$header]], $delimiter);
                } catch (InvalidArgumentException) {
                    ++$unsafe;
                }
            }
        }

        $warnings = ['HEADER_AUTHORITY_AND_SCHEMA_NOT_VERIFIED'];
        if (!$hasHeader) {
            $warnings[] = 'NO_HEADER_DECLARED';
        } else {
            if ($empty > 0) {
                $warnings[] = 'EMPTY_CLAIMED_HEADER';
            }
            if ($duplicates > 0) {
                $warnings[] = 'DUPLICATE_EXACT_CLAIMED_HEADER';
            }
            if ($unsafe > 0) {
                $warnings[] = 'CLAIMED_HEADER_REFUSED_BY_STRICT_CSV_WRITER';
            }
            if (count($rows) === 1) {
                $warnings[] = 'NO_DATA_RECORDS_IN_LOCAL_SAMPLE';
            }
        }

        return [
            'header_claimed' => $hasHeader,
            'local_source_column_count' => $width,
            'local_data_row_count' => count($rows) - ($hasHeader ? 1 : 0),
            'local_empty_header_count' => $empty,
            'local_duplicate_exact_header_count' => $duplicates,
            'local_header_refused_cell_count' => $unsafe,
            'warning_codes' => $warnings,
            'informational_only' => true,
            'header_meaning_verified' => false,
            'tenant_scope_verified' => false,
            'authorization_verified' => false,
            'can_import' => false,
            'can_merge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Resumo local de cabecalho CSV invalido.');
    }
}
