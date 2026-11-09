<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticRetentionWindowInspector;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticRetentionWindowInspectorTest extends TestCase
{
    private static function origin(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-01T00:00:00+00:00');
    }

    private static function atSeconds(int $seconds): DateTimeImmutable
    {
        $t0 = self::origin();
        return $t0->setTimestamp($t0->getTimestamp() + $seconds);
    }

    public static function timelineCases(): array
    {
        $day = 86400;
        return [
            'at T0' => [0, 'NORMAL_WINDOW', false, false],
            'one second before 3 days' => [3 * $day - 1, 'NORMAL_WINDOW', false, false],
            'exactly day 3' => [3 * $day, 'FAILURE_WINDOW', false, false],
            'day 14' => [14 * $day, 'FAILURE_WINDOW', false, false],
            'one second before warning' => [15 * $day - 1, 'FAILURE_WINDOW', false, false],
            'exactly warning at day 15' => [15 * $day, 'FAILURE_WINDOW_WARNING_REVIEW', true, false],
            'one second before 18 days' => [18 * $day - 1, 'FAILURE_WINDOW_WARNING_REVIEW', true, false],
            'exactly day 18' => [18 * $day, 'EXPIRY_RECONCILIATION_REQUIRED', false, true],
            'after day 18' => [19 * $day, 'EXPIRY_RECONCILIATION_REQUIRED', false, true],
        ];
    }

    #[DataProvider('timelineCases')]
    public function test_exact_normal_failure_warning_and_expiry_windows(
        int $seconds,
        string $phase,
        bool $warning,
        bool $expiry,
    ): void {
        $result = (new SyntheticRetentionWindowInspector())->inspect(
            self::origin(), self::atSeconds($seconds),
        );
        self::assertSame($phase, $result->phase);
        self::assertSame($warning, $result->warningReviewDue);
        self::assertSame($expiry, $result->expirationReviewDue);
        self::assertFalse($result->deliveryCleanupReviewDue);
        self::assertFalse($result->canDeleteTemporaryPayload());
        self::assertFalse($result->canRetryAutomatically());
        self::assertFalse($result->canSendNotification());
    }

    public function test_later_retry_does_not_reset_original_first_durable_receipt_clock(): void
    {
        $first = self::origin();
        // A retry timestamp is intentionally NOT an input to the inspector.
        $retryAt = self::atSeconds(16 * 86400);
        $result = (new SyntheticRetentionWindowInspector())->inspect(
            $first, $retryAt,
        );

        self::assertSame('FAILURE_WINDOW_WARNING_REVIEW', $result->phase);
        self::assertFalse($result->canRetryAutomatically());
    }

    public function test_unverified_delivery_claim_stays_blocked_before_and_after_24_hours(): void
    {
        $claim = self::atSeconds(2 * 86400);
        $inspector = new SyntheticRetentionWindowInspector();
        $before = $inspector->inspect(
            self::origin(), $claim->setTimestamp($claim->getTimestamp() + 86400 - 1), $claim,
        );
        $at = $inspector->inspect(
            self::origin(), $claim->setTimestamp($claim->getTimestamp() + 86400), $claim,
        );
        self::assertSame('DELIVERY_CLAIM_UNVERIFIED', $before->phase);
        self::assertFalse($before->deliveryCleanupReviewDue);
        self::assertTrue($at->deliveryCleanupReviewDue);
        self::assertFalse($at->expirationReviewDue);
        self::assertFalse($at->canDeleteTemporaryPayload());
    }

    public function test_delivery_claim_does_not_turn_18_day_expiry_into_silent_deletion(): void
    {
        $result = (new SyntheticRetentionWindowInspector())->inspect(
            self::origin(), self::atSeconds(19 * 86400), self::atSeconds(17 * 86400),
        );
        self::assertSame('DELIVERY_CLAIM_UNVERIFIED', $result->phase);
        self::assertTrue($result->deliveryCleanupReviewDue);
        self::assertFalse($result->expirationReviewDue);
        self::assertFalse($result->canDeleteTemporaryPayload());
    }

    public function test_future_first_receipt_is_invalid(): void
    {
        $result = (new SyntheticRetentionWindowInspector())->inspect(
            self::atSeconds(1), self::origin(),
        );
        self::assertSame('INVALID_TIMELINE', $result->phase);
        self::assertFalse($result->canDeleteTemporaryPayload());
    }

    public function test_delivery_claim_before_receipt_is_invalid(): void
    {
        $result = (new SyntheticRetentionWindowInspector())->inspect(
            self::atSeconds(86400), self::atSeconds(2 * 86400), self::origin(),
        );
        self::assertSame('INVALID_TIMELINE', $result->phase);
    }

    public function test_delivery_claim_in_future_is_invalid(): void
    {
        $result = (new SyntheticRetentionWindowInspector())->inspect(
            self::origin(), self::atSeconds(86400), self::atSeconds(2 * 86400),
        );
        self::assertSame('INVALID_TIMELINE', $result->phase);
    }

    public function test_timezone_offsets_do_not_change_elapsed_seconds(): void
    {
        $t0 = new DateTimeImmutable('2026-11-01T00:00:00+00:00');
        $now = new DateTimeImmutable('2026-11-04T00:00:00+00:00');
        $alternative = new DateTimeImmutable('2026-11-03T21:00:00-03:00');
        $inspector = new SyntheticRetentionWindowInspector();
        self::assertSame($inspector->inspect($t0, $now)->phase, $inspector->inspect($t0, $alternative)->phase);
        self::assertSame('FAILURE_WINDOW', $inspector->inspect($t0, $alternative)->phase);
    }

    public function test_no_payload_or_identifiers_are_exposed(): void
    {
        $result = (new SyntheticRetentionWindowInspector())->inspect(
            self::origin(), self::atSeconds(18 * 86400),
        );
        $serialized = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('Lead Exemplo', $serialized);
        self::assertStringNotContainsString('customer', $serialized);
        self::assertFalse($result->canDeleteTemporaryPayload());
    }
}
