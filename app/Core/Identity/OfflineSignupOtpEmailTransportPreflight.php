<?php

declare(strict_types=1);

namespace App\Core\Identity;

/**
 * F3-237: inspect caller-supplied SMTP settings in memory only.
 * Never dispatch mail, resolve real Laravel config, or approve transport.
 */
final class OfflineSignupOtpEmailTransportPreflight
{
    /**
     * @param mixed $mailer Fictional mailer settings, not real credentials
     * @param mixed $sender Fictional sender settings
     * @return array<string,mixed> Sanitized fixed reason codes only
     */
    public function inspect(
        #[\SensitiveParameter] mixed $mailer,
        #[\SensitiveParameter] mixed $sender,
    ): array {
        if (!is_array($mailer) || !is_array($sender)) {
            return self::response('invalid_configuration', false);
        }

        // A Laravel log transport leaks plaintext verification codes to
        // persistent logs. Failover/roundrobin can silently select it.
        if (($mailer['transport'] ?? null) !== 'smtp'
            || array_key_exists('mailers', $mailer)
            || array_key_exists('url', $mailer)
            || array_key_exists('stream', $mailer)
            || array_key_exists('failover', $mailer)
            || array_key_exists('roundrobin', $mailer)
            || array_key_exists('verify_peer', $mailer)
            || array_key_exists('allow_self_signed', $mailer)) {
            return self::response('unsafe_or_ambiguous_transport', false);
        }

        // Strict SMTP over implicit TLS. This does NOT prove that any
        // certificate, SMTP server or STARTTLS implementation is safe.
        if (($mailer['scheme'] ?? null) !== 'smtps'
            || ($mailer['port'] ?? null) !== 465) {
            return self::response('tls_requirements_not_established', false);
        }

        $host = $mailer['host'] ?? null;
        $username = $mailer['username'] ?? null;
        $password = $mailer['password'] ?? null;
        $timeout = $mailer['timeout'] ?? null;

        if (!is_string($host) || !self::publicHostShape($host)
            || !is_string($username) || strlen($username) < 1 || strlen($username) > 254
            || !is_string($password) || strlen($password) < 16 || strlen($password) > 512
            || !is_int($timeout) || $timeout < 3 || $timeout > 30) {
            return self::response('invalid_smtp_settings', false);
        }

        $address = $sender['address'] ?? null;
        $name = $sender['name'] ?? null;
        if (!is_string($address) || strlen($address) > 254
            || filter_var($address, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/\A[\x21-\x7E]+\z/D', $address) !== 1
            || !is_string($name) || strlen($name) < 1 || strlen($name) > 80
            || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            return self::response('invalid_sender', false);
        }

        return self::response('offline_candidate_only', true);
    }

    private static function publicHostShape(string $host): bool
    {
        if (strlen($host) < 4 || strlen($host) > 253
            || $host !== strtolower($host) || !str_contains($host, '.')
            || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }
        foreach (['.local', '.localhost', '.internal', '.test', '.invalid', '.example'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }
        foreach (explode('.', $host) as $label) {
            if ($label === '' || strlen($label) > 63
                || preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/D', $label) !== 1) {
                return false;
            }
        }
        return true;
    }

    /** @return array<string,mixed> */
    private static function response(string $code, bool $candidate): array
    {
        return [
            'reason_code' => $code,
            'offline_configuration_candidate' => $candidate,
            'live_smtp_delivery_verified' => false,
            'tls_peer_verified' => false,
            'dns_and_sender_verified' => false,
            'spf_dkim_dmarc_verified' => false,
            'recipient_control_verified' => false,
            'rate_limit_verified' => false,
            'lgpd_verified' => false,
            'independent_review_verified' => false,
            'informational_only' => true,
            'can_send' => false,
            'can_queue' => false,
            'can_log_code' => false,
            'can_register' => false,
            'can_authenticate' => false,
        ];
    }
}
