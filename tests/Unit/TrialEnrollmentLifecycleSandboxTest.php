<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\TrialWindowAdvisory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrialEnrollmentLifecycleSandbox;

final class TrialEnrollmentLifecycleSandboxTest extends TestCase
{
    private const KEY = 'a-fictional-cnpj-trial-hmac-secret-with-40-bytes';

    public function test_alphanumeric_cnpj_canonical_duplicate_is_seen_only_in_one_process(): void
    {
        $lab = new TrialEnrollmentLifecycleSandbox(self::KEY);
        $first = $lab->observeFirstUse('12.ABC.345/01DE-35', 1000);
        self::assertSame('sandbox_first_trial_marker', $first['status']);
        self::assertSame('alphanumeric', $first['format_kind']);
        self::assertFalse($first['can_reserve_trial']);

        $duplicate = $lab->observeFirstUse('12abc34501de35', 1001);
        self::assertSame('sandbox_duplicate_trial_claim', $duplicate['status']);
        self::assertFalse($duplicate['can_register']);
        self::assertSame(1, $lab->diagnostics()['synthetic_company_markers_in_memory']);
        self::assertFalse($lab->diagnostics()['durable_uniqueness_verified']);

        $another = new TrialEnrollmentLifecycleSandbox(self::KEY);
        self::assertSame('sandbox_first_trial_marker',
            $another->observeFirstUse('12.ABC.345/01DE-35', 1002)['status']);
        self::assertFalse($another->diagnostics()['can_create_tenant']);
    }

    public function test_numeric_and_alphanumeric_company_scopes_are_distinct(): void
    {
        $lab = new TrialEnrollmentLifecycleSandbox(self::KEY);
        self::assertSame('sandbox_first_trial_marker',
            $lab->observeFirstUse('11.222.333/0001-81', 1000)['status']);
        self::assertSame('sandbox_first_trial_marker',
            $lab->observeFirstUse('12.ABC.345/01DE-35', 1001)['status']);
        self::assertSame(2, $lab->diagnostics()['synthetic_company_markers_in_memory']);
        self::assertSame('numeric',
            $lab->describe('11222333000181', 1002)['format_kind']);
        self::assertSame('alphanumeric',
            $lab->describe('12abc34501de35', 1002)['format_kind']);
    }

    public function test_trial_14_days_and_seven_quotas_are_only_readouts(): void
    {
        $lab = new TrialEnrollmentLifecycleSandbox(self::KEY);
        $cnpj = '12.ABC.345/01DE-35';
        $lab->observeFirstUse($cnpj, 1000);
        $before = $lab->describe($cnpj, 999);
        self::assertSame('before_start', $before['trial_phase']);
        $start = $lab->describe($cnpj, 1000);
        self::assertSame('within_window', $start['trial_phase']);
        self::assertSame(14, $start['remaining_days_rounded_up']);
        self::assertSame(TrialWindowAdvisory::WINDOW_DAYS, $start['illustrative_trial_days']);
        $last = $lab->describe($cnpj, 1000 + 14 * 86400 - 1);
        self::assertSame('within_window', $last['trial_phase']);
        self::assertSame(1, $last['remaining_days_rounded_up']);
        $expired = $lab->describe($cnpj, 1000 + 14 * 86400);
        self::assertSame('after_window', $expired['trial_phase']);
        self::assertSame(0, $expired['remaining_days_rounded_up']);
        self::assertCount(7, $expired['quota_meters']);
        $limits = [];
        foreach ($expired['quota_meters'] as $card) {
            self::assertSame(0, $card['illustrative_used']);
            self::assertSame('in_range', $card['band']);
            $limits[$card['meter']] = $card['illustrative_limit'];
        }
        self::assertSame([
            'users' => 2,
            'leads' => 20,
            'automations' => 2,
            'pipelines' => 2,
            'attachments_bytes' => 20971520,
            'ai_initial_credits' => 10,
            'ai_bonus_credits' => 10,
        ], $limits);
        foreach ([
            'registered_company_verified', 'company_owner_verified',
            'tenant_membership_verified', 'durable_uniqueness_verified',
            'consent_and_lgpd_verified', 'can_reserve_trial',
            'can_create_tenant', 'can_register', 'can_authenticate',
            'can_charge', 'can_allocate_quota', 'can_persist',
        ] as $flag) {
            self::assertFalse($expired[$flag], $flag);
        }
        // A trial marker remains present after 14 days, preventing a local
        // second start; this is NOT permanent or durable enforcement.
        self::assertSame('sandbox_duplicate_trial_claim',
            $lab->observeFirstUse($cnpj, 1000 + 14 * 86400 + 1)['status']);

        $json = json_encode($expired, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('12ABC34501DE35', $json);
        self::assertStringNotContainsString($cnpj, $json);
        self::assertStringNotContainsString('digest', $json);
        self::assertLessThan(3500, strlen($json));
    }

    public function test_unknown_or_invalid_company_is_denied_without_creating_a_marker(): void
    {
        $lab = new TrialEnrollmentLifecycleSandbox(self::KEY);
        self::assertSame('sandbox_no_trial_marker',
            $lab->describe('11.222.333/0001-81', 1000)['status']);
        foreach ([null, '', 'not a CNPJ', '00000000000000', '12ABC34501DE00'] as $bad) {
            self::assertSame('sandbox_cnpj_rejected',
                $lab->observeFirstUse($bad, 1000)['status']);
            self::assertSame('sandbox_cnpj_rejected',
                $lab->describe($bad, 1001)['status']);
        }
        self::assertSame(0, $lab->diagnostics()['synthetic_company_markers_in_memory']);
    }

    public function test_invalid_keys_and_overflow_fail_without_echoing_inputs(): void
    {
        try {
            new TrialEnrollmentLifecycleSandbox('short');
            self::fail('Short key accepted.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Trial ficticio invalido.', $e->getMessage());
        }
        $lab = new TrialEnrollmentLifecycleSandbox(self::KEY);
        foreach ([-1, PHP_INT_MAX, PHP_INT_MAX - 100] as $time) {
            try {
                $lab->observeFirstUse('12.ABC.345/01DE-35', $time);
                self::fail('Invalid start accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Trial ficticio invalido.', $e->getMessage());
            }
        }
    }

    public function test_sandbox_has_no_routes_storage_transport_or_real_registration(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/tests/Support/TrialEnrollmentLifecycleSandbox.php',
        );
        self::assertIsString($source);
        foreach ([
            'Mail::', 'Http::', 'DB::', 'Redis::', 'Cache::',
            'file_put_contents(', 'getenv(', 'env(', 'curl_', 'https://',
            'http://', 'createUser(', 'createTenant(', 'insert(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
