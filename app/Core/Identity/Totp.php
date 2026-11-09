<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/** RFC 4226/6238 SHA-1; PHP HMAC primitive, no new cryptographic primitive. */
final class Totp
{
    public function code(#[\SensitiveParameter] string $base32, int $timestamp, int $digits = 6): string
    {
        if (!in_array($digits, [6, 8], true) || $timestamp < 0
            || !preg_match('/\A[A-Z2-7]{32,128}\z/D', $base32)) {
            throw new InvalidArgumentException('Invalid TOTP parameters.');
        }
        $buffer = 0;
        $bits = 0;
        $secret = '';
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        foreach (str_split($base32) as $character) {
            $buffer = ($buffer << 5) | strpos($alphabet, $character);
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $secret .= chr(($buffer >> $bits) & 255);
                $buffer &= (1 << $bits) - 1;
            }
        }
        $step = intdiv($timestamp, 30);
        $hmac = hash_hmac('sha1', pack('N2', intdiv($step, 4294967296), $step % 4294967296), $secret, true);
        $offset = ord($hmac[19]) & 15;
        $value = unpack('N', substr($hmac, $offset, 4))[1] & 0x7fffffff;

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function matchStep(#[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code, int $timestamp): ?int
    {
        if (!preg_match('/\A[0-9]{6}\z/D', $code)) {
            return null;
        }
        // A bounded +/- one step window; replay is rejected by the locked registry.
        foreach ([0, -1, 1] as $drift) {
            $time = $timestamp + $drift * 30;
            if ($time >= 0 && hash_equals($this->code($secret, $time), $code)) {
                return intdiv($time, 30);
            }
        }

        return null;
    }
}
