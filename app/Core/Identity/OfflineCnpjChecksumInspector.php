<?php

declare(strict_types=1);

namespace App\Core\Identity;

/**
 * F3-230 — strict syntax/check-digit review for numeric and alphanumeric CNPJ.
 *
 * Based on RFB IN 2.229/2024: 12 ASCII base characters, 2 decimal check
 * digits; ASCII code minus 48, same mod-11 weighted checksum as numeric CNPJ.
 *
 * Does NOT query Receita, identify company ownership, authorize one trial,
 * validate tax registration status, log or return source document values.
 * Outputs only static status codes/booleans.
 */
final class OfflineCnpjChecksumInspector
{
    private const FIRST_WEIGHTS = [5,4,3,2,9,8,7,6,5,4,3,2];
    private const SECOND_WEIGHTS = [6,5,4,3,2,9,8,7,6,5,4,3,2];

    /**
     * @param mixed $candidate A compact or fully punctuated CNPJ string
     * @return array<string,mixed> Closed no-identifier assessment
     */
    public function inspect(#[\SensitiveParameter] mixed $candidate): array
    {
        if (!is_string($candidate) || strlen($candidate) > 24) {
            return self::report('format_rejected', 'unknown', false, false);
        }

        $text = strtoupper(trim($candidate, " \t\r\n"));
        $compact = preg_match('/\A[A-Z0-9]{12}[0-9]{2}\z/D', $text) === 1;
        $formatted = preg_match(
            '/\A[A-Z0-9]{2}\.[A-Z0-9]{3}\.[A-Z0-9]{3}\/[A-Z0-9]{4}-[0-9]{2}\z/D',
            $text,
        ) === 1;
        if (!$compact && !$formatted) {
            return self::report('format_rejected', 'unknown', false, false);
        }

        $normalized = $formatted
            ? str_replace(['.', '/', '-'], '', $text)
            : $text;
        $numeric = preg_match('/\A[0-9]{14}\z/D', $normalized) === 1;
        $kind = $numeric ? 'numeric' : 'alphanumeric';

        // A numeric string made from 14 identical digits is a known invalid
        // legacy input even if its checksums happen to be zero.
        if ($numeric && preg_match('/\A([0-9])\1{13}\z/D', $normalized) === 1) {
            return self::report('check_digits_rejected', $kind, true, false);
        }

        $base = substr($normalized, 0, 12);
        $first = self::digit($base, self::FIRST_WEIGHTS);
        $second = self::digit($base.(string) $first, self::SECOND_WEIGHTS);
        $valid = hash_equals((string) $first.(string) $second, substr($normalized, 12, 2));

        return self::report(
            $valid ? 'structurally_valid_only' : 'check_digits_rejected',
            $kind,
            true,
            $valid,
        );
    }

    /**
     * @param list<int> $weights
     */
    private static function digit(string $characters, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $position => $weight) {
            $sum += (ord($characters[$position]) - 48) * $weight;
        }
        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    /** @return array<string,mixed> */
    private static function report(
        string $status,
        string $kind,
        bool $shapeValid,
        bool $checksumValid,
    ): array {
        return [
            'status_code' => $status,
            'format_kind' => $kind,
            'shape_valid' => $shapeValid,
            'check_digits_valid' => $checksumValid,
            'supports_numeric_and_alphanumeric' => true,
            'informational_only' => true,
            'registered_company_verified' => false,
            'tax_authority_verified' => false,
            'company_owner_verified' => false,
            'one_trial_per_cnpj_verified' => false,
            'tenant_identity_verified' => false,
            'can_register' => false,
            'can_reserve_trial' => false,
            'can_grant_access' => false,
            'can_persist' => false,
        ];
    }
}
