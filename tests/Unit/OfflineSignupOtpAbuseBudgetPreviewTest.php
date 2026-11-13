<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupContactHmacBinding;
use App\Core\Identity\OfflineSignupOtpAbuseBudgetPreview;
use App\Core\Identity\OfflineSignupOtpChallenge;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineSignupOtpAbuseBudgetPreviewTest extends TestCase
{
    private const KEY = 'fictional-only-contact-hmac-binding-key-over-32-bytes';

    private static function refs(): array
    {
        $contact = (new OfflineSignupContactHmacBinding())
            ->derive('fictional@example.com', 'email', self::KEY);
        $origin = hash_hmac('sha256', 'fictional-origin-1', self::KEY);

        return [$contact, $origin];
    }

    private static function snap(int $start = 1000, int $count = 0, int $last = 1000): array
    {
        return ['window_start' => $start, 'events' => $count, 'latest_at' => $last];
    }

    public function test_favorable_snapshots_never_grant_real_otp_or_account_authority(): void
    {
        [$contact, $origin] = self::refs();
        $engine = new OfflineSignupOtpAbuseBudgetPreview();
        foreach (['issue', 'verify'] as $action) {
            $view = $engine->inspect($action, $contact, $origin, 1100, self::snap(), self::snap());
            self::assertSame('sample_within_illustrative_budget', $view['reason_code']);
            self::assertSame($action, $view['action']);
            self::assertSame(OfflineSignupOtpChallenge::VALIDITY_SECONDS, $view['example_window_seconds']);
            self::assertTrue($view['informational_only']);
            foreach ([
                'durable_counter_verified', 'atomic_reservation_verified',
                'origin_authenticity_verified', 'contact_ownership_verified',
                'abuse_policy_approved', 'can_send', 'can_verify_real_code',
                'can_queue', 'can_register', 'can_authenticate', 'can_persist',
            ] as $flag) {
                self::assertFalse($view[$flag], $flag);
            }
            $wire = json_encode($view, JSON_THROW_ON_ERROR);
            self::assertStringNotContainsString($contact, $wire);
            self::assertStringNotContainsString($origin, $wire);
            self::assertStringNotContainsString('fictional@example.com', $wire);
            self::assertLessThan(1024, strlen($wire));
        }
    }

    public function test_per_contact_and_origin_example_budgets_are_independent(): void
    {
        [$contact, $origin] = self::refs();
        $engine = new OfflineSignupOtpAbuseBudgetPreview();
        foreach ([['issue', 3, 20], ['verify', 5, 60]] as [$action, $contactLimit, $originLimit]) {
            $contactView = $engine->inspect(
                $action, $contact, $origin, 1200,
                self::snap(1000, $contactLimit, 1001), self::snap(),
            );
            self::assertSame('sample_contact_budget_exhausted', $contactView['reason_code']);
            $originView = $engine->inspect(
                $action, $contact, $origin, 1200,
                self::snap(), self::snap(1000, $originLimit, 1001),
            );
            self::assertSame('sample_origin_budget_exhausted', $originView['reason_code']);
            self::assertFalse($contactView['can_send']);
            self::assertFalse($originView['can_verify_real_code']);
        }
    }

    public function test_cooldown_does_not_affect_expired_windows_at_exact_900s_boundary(): void
    {
        [$contact, $origin] = self::refs();
        $engine = new OfflineSignupOtpAbuseBudgetPreview();
        $before = $engine->inspect(
            'issue', $contact, $origin, 1009,
            self::snap(1000, 1, 1000), self::snap(1000, 0, 1000),
        );
        self::assertSame('sample_cooldown_active', $before['reason_code']);

        $afterCooldown = $engine->inspect(
            'issue', $contact, $origin, 1060,
            self::snap(1000, 1, 1000), self::snap(1000, 0, 1000),
        );
        self::assertSame('sample_within_illustrative_budget', $afterCooldown['reason_code']);

        $nearBoundary = $engine->inspect(
            'issue', $contact, $origin, 1899,
            self::snap(1000, 3, 1898), self::snap(),
        );
        self::assertSame('sample_contact_budget_exhausted', $nearBoundary['reason_code']);
        $atBoundary = $engine->inspect(
            'issue', $contact, $origin, 1900,
            self::snap(1000, 3, 1899), self::snap(1000, 20, 1899),
        );
        self::assertSame('sample_within_illustrative_budget', $atBoundary['reason_code']);
        self::assertFalse($atBoundary['can_register']);
    }

    public function test_verify_example_uses_exact_core_attempt_budget(): void
    {
        [$contact, $origin] = self::refs();
        $engine = new OfflineSignupOtpAbuseBudgetPreview();
        $view = $engine->inspect(
            'verify', $contact, $origin, 1200,
            self::snap(1000, OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS, 1001),
            self::snap(),
        );
        self::assertSame(OfflineSignupOtpChallenge::MAX_CANDIDATE_ATTEMPTS, $view['example_contact_limit']);
        self::assertSame('sample_contact_budget_exhausted', $view['reason_code']);
    }

    public function test_malformed_snapshots_and_unhashed_identifiers_fail_closed_without_reflection(): void
    {
        [$contact, $origin] = self::refs();
        $engine = new OfflineSignupOtpAbuseBudgetPreview();
        $bad = [
            ['send', $contact, $origin, 1200, self::snap(), self::snap()],
            ['issue', 'fictional@example.com', $origin, 1200, self::snap(), self::snap()],
            ['issue', $contact, '127.0.0.1', 1200, self::snap(), self::snap()],
            ['issue', $contact, $contact, 1200, self::snap(), self::snap()],
            ['issue', $contact, $origin, -1, self::snap(), self::snap()],
            ['issue', $contact, $origin, '1200', self::snap(), self::snap()],
            ['issue', $contact, $origin, 1200, ['window_start' => 1000], self::snap()],
            ['issue', $contact, $origin, 1200, array_merge(self::snap(), ['extra' => 1]), self::snap()],
            ['issue', $contact, $origin, 1200, self::snap(1000, -1), self::snap()],
            ['issue', $contact, $origin, 1200, self::snap(1000, 100001), self::snap()],
            ['issue', $contact, $origin, 1200, self::snap(1300), self::snap()],
            ['issue', $contact, $origin, 1200, self::snap(1000, 1, 1300), self::snap()],
            ['issue', $contact, $origin, 1200, self::snap(1000, 1, 999), self::snap()],
            ['issue', $contact, $origin, 1200, self::snap(), null],
        ];
        foreach ($bad as $args) {
            try {
                $engine->inspect(...$args);
                self::fail('Invalid counter claim accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Revisao local de limite OTP invalida.', $e->getMessage());
                self::assertStringNotContainsString('fictional@example.com', $e->getMessage());
                self::assertStringNotContainsString($contact, $e->getMessage());
                self::assertStringNotContainsString($origin, $e->getMessage());
            }
        }
    }

    public function test_no_transport_counter_storage_or_authentication_api_is_exposed(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Core/Identity/OfflineSignupOtpAbuseBudgetPreview.php',
        );
        self::assertIsString($source);
        foreach ([
            'Mail::', 'Http::', 'DB::', 'file_put_contents(',
            'getenv(', 'env(', 'Cache::', 'Redis::',
            'curl_', 'https://', 'http://',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
