<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-231: generate a pseudonymous, versioned trial uniqueness key from a
 * checksum-valid numeric/alphanumeric CNPJ in ephemeral Core memory.
 *
 * Does not look up Receita, prove identity/ownership, persist a reservation,
 * enforce one trial, or authorize a business operation. HMAC is NOT a token.
 */
final class OfflineCnpjTrialHmacBinding
{
    private const DOMAIN = "imagine.crm.trial.cnpj.v1\x00";

    /**
     * @param mixed $cnpj Source value never returned or logged
     * @param string $key Dedicated 32-128-byte secret from a separately reviewed key store
     * @return string Lowercase 64-hex HMAC fingerprint, never customer-facing
     */
    public function derive(
        #[\SensitiveParameter] mixed $cnpj,
        #[\SensitiveParameter] string $key,
    ): string {
        if (strlen($key) < 32 || strlen($key) > 128 || !is_string($cnpj)) {
            self::invalid();
        }
        $check = (new OfflineCnpjChecksumInspector())->inspect($cnpj);
        if (($check['status_code'] ?? null) !== 'structurally_valid_only'
            || ($check['check_digits_valid'] ?? null) !== true) {
            self::invalid();
        }

        // Strict normalization is performed ONLY after the parser/CRC
        // accepted one of the two canonical CNPJ representations.
        $canonical = strtoupper(trim($cnpj, " \t\r\n"));
        $canonical = str_replace(['.', '/', '-'], '', $canonical);
        if (preg_match('/\A[A-Z0-9]{12}[0-9]{2}\z/D', $canonical) !== 1) {
            self::invalid();
        }

        // A secret HMAC prevents exposing raw, enumerable 14-character CNPJ
        // identifiers as plain SHA-256 digest values in a trial ledger.
        // The namespace is deliberately distinct from OTP and other IDs.
        return hash_hmac('sha256', self::DOMAIN.$canonical, $key);
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Vinculo CNPJ de trial local invalido.');
    }
}
