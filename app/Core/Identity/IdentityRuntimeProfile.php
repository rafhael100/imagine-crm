<?php

declare(strict_types=1);

namespace App\Core\Identity;

/** Configuration admission only; SQL/session/credential verification still applies. */
final class IdentityRuntimeProfile
{
    public static function enabled(): bool
    {
        if (!config('identity.experimental')) {
            return false;
        }
        if (app()->environment('local', 'testing')) {
            return true;
        }

        return self::allowsPrivateStaging(app()->environment(), [
            'mode' => config('identity.private_staging_profile'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'session_driver' => config('session.driver'),
            'abuse_store' => config('identity.abuse_store'),
            'default_connection' => config('database.default'),
            'business' => config('database.connections.pgsql'),
            'iam' => config('database.connections.crm_iam'),
        ]);
    }

    /**
     * The private profile is a separate database/role contract, never CI impersonation.
     * No credentials are accepted from the browser and no fallback role is allowed.
     * @param array<string,mixed> $settings
     */
    public static function allowsPrivateStaging(string $environment, array $settings): bool
    {
        if ($environment !== 'staging'
            || ($settings['mode'] ?? null) !== 'private-homologation-synthetic-v1'
            || ($settings['debug'] ?? null) !== false
            || ($settings['url'] ?? null) !== 'https://crm-dev.imaginecomunica.com.br'
            || ($settings['session_driver'] ?? null) !== 'redis'
            || ($settings['abuse_store'] ?? null) !== 'redis'
            || ($settings['default_connection'] ?? null) !== 'pgsql') {
            return false;
        }
        $business = $settings['business'] ?? null;
        $iam = $settings['iam'] ?? null;
        if (!is_array($business) || !is_array($iam)) {
            return false;
        }
        foreach (['business' => $business, 'iam' => $iam] as $name => $connection) {
            if (($connection['driver'] ?? null) !== 'pgsql'
                || ($connection['database'] ?? null) !== 'imagine_crm_private_homologation'
                || !in_array($connection['host'] ?? null, ['postgres', '127.0.0.1'], true)
                || !in_array($connection['port'] ?? null, [5432, '5432'], true)
                || !empty($connection['url'])
                || ($connection['username'] ?? null) !== ($name === 'business'
                    ? 'crm_homologation_app' : 'crm_homologation_iam')
                || !is_string($connection['password'] ?? null)
                || strlen($connection['password']) < 24) {
                return false;
            }
        }

        return $business['host'] === $iam['host']
            && !hash_equals($business['password'], $iam['password']);
    }
}
