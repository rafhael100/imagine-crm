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

        // Embedded or leading ASCII controls (including CR/LF/TAB) are risky
        // as spreadsheet text. A quoted multiline CSV cell is syntactically
        // valid, but still needs format-specific protection before export.
        if (preg_match('/[\x00-\x1f\x7f]/', $cell) === 1) {
            return true;
        }

        // Treat all Unicode separators (Z) and invisible format marks (Cf)
        // as possible engine-trimmed prefixes. Standalone leading combining marks (M) can also obscure
        // the executable prefix. This is conservative risk detection,
        // NOT CSV/XLSX escaping or sanitization.
        return preg_match(
            '/\A(?:[[:space:]\p{Z}\p{Cf}\p{M}])*[=+\-@]/u',
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
