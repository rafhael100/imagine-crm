<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * Conservative preflight for a raw UNTRUSTED TEXT cell before CSV/XLSX export.
 *
 * It never produces CSV/XLSX, strips/escapes a formula, stores records, or
 * authorizes export. A real exporter must independently enforce tenancy,
 * per-field rights, format-appropriate quoting, audit, and client compatibility.
 * This preflight deliberately rejects some benign values such as "-10".
 */
final class SpreadsheetTextRiskGuard
{
    public static function isPotentiallyExecutable(string $cell): bool
    {
        if ($cell === '') {
            return false;
        }

        // Invalid UTF-8 must not silently pass Unicode-aware prefix checks.
        if (preg_match('//u', $cell) !== 1) {
            return true;
        }

        // Leading tabs/newlines/control characters are unsafe even before text.
        if (preg_match('/\A[\x00-\x1f\x7f]/u', $cell) === 1) {
            return true;
        }

        // Spreadsheet engines may ignore leading whitespace, BOM or hidden
        // Unicode separators before interpreting =, +, -, @ as a formula.
        return preg_match(
            '/\A(?:[[:space:]\x{00a0}\x{2000}-\x{200f}\x{2028}\x{2029}\x{202f}\x{205f}\x{3000}\x{feff}])*[=+\-@]/u',
            $cell,
        ) === 1;
    }

    public static function assertPlainText(string $cell): void
    {
        if (self::isPotentiallyExecutable($cell)) {
            // Do not reflect untrusted cell contents into error messages/logs.
            throw new InvalidArgumentException('Celula de planilha requer tratamento seguro.');
        }
    }
}
