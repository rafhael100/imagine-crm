<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-68: render an EMPTY CSV header template using synthetic technical labels.
 *
 * This is not a CRM schema, customer export, download service or grant to
 * import. It emits zero data rows and has no database/filesystem/network I/O.
 */
final class SyntheticCsvHeaderTemplate
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /** @param array<mixed> $technicalLabels */
    public function render(
        array $technicalLabels,
        string $delimiter = ',',
        bool $withBom = false,
    ): string {
        if ($delimiter !== ',' && $delimiter !== ';') {
            self::invalid();
        }

        try {
            // Reuse the same strict 1..8 unique lowercase technical-label
            // validation as the existing mapped CSV preview. Every source
            // label must map exactly to itself. No inferred aliases.
            $identity = (new SyntheticColumnMappingInspector())->suggestExactMatches(
                $technicalLabels,
                $technicalLabels,
            );
            if (!$identity->isCompleteOneToOne()) {
                self::invalid();
            }
        } catch (InvalidArgumentException) {
            self::invalid();
        }

        // Labels cannot contain delimiters, quotes, LF, CR, formula prefixes
        // or Unicode controls after the shared technical-label validation.
        // CRLF terminator is unambiguous and accepted by the canonical parser.
        return ($withBom ? self::UTF8_BOM : '')
            .implode($delimiter, $technicalLabels)."\r\n";
    }

    public function canExportContacts(): bool
    {
        return false;
    }

    public function canAuthorizeImport(): bool
    {
        return false;
    }

    private static function invalid(): never
    {
        // Do not reveal rejected labels, customer-shaped inputs or their bytes.
        throw new InvalidArgumentException('Modelo CSV sintetico invalido.');
    }
}
