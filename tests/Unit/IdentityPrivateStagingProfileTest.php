<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\IdentityRuntimeProfile;
use PHPUnit\Framework\TestCase;

final class IdentityPrivateStagingProfileTest extends TestCase
{
    private function settings(): array
    {
        $connection = ['driver' => 'pgsql', 'database' => 'imagine_crm_private_homologation',
            'host' => 'postgres', 'port' => '5432', 'url' => null];
        return ['mode' => 'private-homologation-synthetic-v1', 'debug' => false,
            'url' => 'https://crm-dev.imaginecomunica.com.br', 'session_driver' => 'redis',
            'abuse_store' => 'redis', 'default_connection' => 'pgsql',
            'business' => $connection + ['username' => 'crm_homologation_app', 'password' => str_repeat('A', 32)],
            'iam' => $connection + ['username' => 'crm_homologation_iam', 'password' => str_repeat('B', 32)]];
    }

    public function test_requires_exact_isolated_private_contract(): void
    {
        self::assertTrue(IdentityRuntimeProfile::allowsPrivateStaging('staging', $this->settings()));
        foreach (['production', 'local', 'testing', '', 'STAGING'] as $environment) {
            self::assertFalse(IdentityRuntimeProfile::allowsPrivateStaging($environment, $this->settings()));
        }
        foreach (['mode' => true, 'debug' => true, 'url' => 'http://crm-dev.imaginecomunica.com.br',
            'session_driver' => 'array', 'abuse_store' => 'file', 'default_connection' => 'sqlite'] as $key => $bad) {
            $settings = $this->settings();
            $settings[$key] = $bad;
            self::assertFalse(IdentityRuntimeProfile::allowsPrivateStaging('staging', $settings), $key);
        }
        foreach (['business', 'iam'] as $kind) {
            foreach (['database' => 'imagine_crm_ci', 'username' => 'postgres', 'host' => 'customer-db',
                'password' => '', 'url' => 'postgres://wrong', 'driver' => 'sqlite', 'port' => '5433'] as $key => $bad) {
                $settings = $this->settings();
                $settings[$kind][$key] = $bad;
                self::assertFalse(IdentityRuntimeProfile::allowsPrivateStaging('staging', $settings), $kind.'.'.$key);
            }
        }
        $settings = $this->settings();
        $settings['business']['port'] = true;
        self::assertFalse(IdentityRuntimeProfile::allowsPrivateStaging('staging', $settings));
        $settings = $this->settings();
        $settings['iam']['password'] = $settings['business']['password'];
        self::assertFalse(IdentityRuntimeProfile::allowsPrivateStaging('staging', $settings));
    }
}
