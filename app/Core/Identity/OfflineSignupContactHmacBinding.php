<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-234: a conservative, in-memory HMAC binding for an unverified contact.
 *
 * This is NOT evidence that the email exists, that a phone is controlled by
 * the applicant, that a WhatsApp account exists, or that consent was given.
 * The binding may be used only as an opaque context in offline OTP tests.
 */
final class OfflineSignupContactHmacBinding
{
    private const DOMAIN = "imagine.crm.signup_contact_binding.v1\x00";

    /**
     * @param mixed $contact The original email or E.164 phone; never returned
     * @param mixed $channel Exactly email or phone (WhatsApp transport is separate)
     * @param string $key Secret dedicated to contact binding, 32..128 bytes
     * @return string Opaque 64-hex HMAC, not a login or delivery token
     */
    public function derive(
        #[\SensitiveParameter] mixed $contact,
        mixed $channel,
        #[\SensitiveParameter] string $key,
    ): string {
        if (!is_string($contact) || !is_string($channel)
            || !in_array($channel, ['email', 'phone'], true)
            || strlen($key) < 32 || strlen($key) > 128) {
            self::invalid();
        }

        $canonical = match ($channel) {
            'email' => self::email($contact),
            'phone' => self::phone($contact),
        };

        return hash_hmac(
            'sha256',
            self::DOMAIN.$channel."\x00".$canonical,
            $key,
        );
    }

    private static function email(string $address): string
    {
        if (strlen($address) > 254 || strlen($address) < 3
            || preg_match('/\A[\x21-\x7E]+\z/D', $address) !== 1
            || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            self::invalid();
        }

        $at = strrpos($address, '@');
        if ($at === false || $at < 1 || $at > 64) {
            self::invalid();
        }

        // The mailbox local-part may be case-sensitive. Only lowercase its
        // ASCII DNS domain; never collapse +tags, dots, or local-part case.
        $local = substr($address, 0, $at);
        $domain = strtolower(substr($address, $at + 1));
        if (strlen($domain) > 253 || strpos($domain, '.') === false
            || preg_match('/\A[a-z0-9.-]+\z/D', $domain) !== 1
            || str_contains($domain, '..')) {
            self::invalid();
        }

        return $local.'@'.$domain;
    }

    private static function phone(string $e164): string
    {
        // E.164 canonical syntax; no guessed Brazilian area code, whitespace,
        // trunk prefix, extension, separators, national or WhatsApp claims.
        if (preg_match('/\A\+[1-9][0-9]{7,14}\z/D', $e164) !== 1) {
            self::invalid();
        }

        return $e164;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Vinculo local de contato invalido.');
    }
}
