<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\TrialWindowAdvisory;
use App\Core\Plans\TrialWindowPhase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrialWindowAdvisoryTest extends TestCase
{
    public static function illustrativeSnapshots(): array
    {
        return [
            'before start is informational only' => [100, 99, TrialWindowPhase::BeforeStart, 1209600, 14],
            'exact start' => [100, 100, TrialWindowPhase::WithinWindow, 1209600, 14],
            'first second elapsed' => [100, 101, TrialWindowPhase::WithinWindow, 1209599, 14],
            'one full day left' => [100, 100 + 13 * 86400, TrialWindowPhase::WithinWindow, 86400, 1],
            'one second left' => [100, 100 + 1209599, TrialWindowPhase::WithinWindow, 1, 1],
            'exact end is not within window' => [100, 100 + 1209600, TrialWindowPhase::AfterWindow, 0, 0],
            'after end' => [100, 100 + 1209601, TrialWindowPhase::AfterWindow, 0, 0],
            'start at epoch zero' => [0, 0, TrialWindowPhase::WithinWindow, 1209600, 14],
            'large safe start' => [PHP_INT_MAX - 1209600, PHP_INT_MAX, TrialWindowPhase::AfterWindow, 0, 0],
        ];
    }

    #[DataProvider('illustrativeSnapshots')]
    public function test_window_is_bounded_display_only(
        int $start,
        int $observed,
        TrialWindowPhase $phase,
        int $remainingSeconds,
        int $remainingDays,
    ): void {
        $timeline = new TrialWindowAdvisory($start, $observed);

        self::assertSame(TrialWindowAdvisory::WINDOW_DAYS, 14);
        self::assertSame($start + 1209600, $timeline->endsAtEpochSeconds());
        self::assertSame($phase, $timeline->phase());
        self::assertSame($remainingSeconds, $timeline->remainingSeconds());
        self::assertSame($remainingDays, $timeline->remainingDaysRoundedUp());
        self::assertTrue((new \ReflectionClass(TrialWindowAdvisory::class))->isReadOnly());
        self::assertSame(['startedAtEpochSeconds', 'observedAtEpochSeconds'], array_keys(get_object_vars($timeline)));
    }

    public static function invalidSnapshots(): array
    {
        return [
            'negative start' => [-1, 0],
            'negative observation' => [0, -1],
            'negative both' => [-1, -1],
            'end would overflow integer' => [PHP_INT_MAX - 1209600 + 1, PHP_INT_MAX],
        ];
    }

    #[DataProvider('invalidSnapshots')]
    public function test_invalid_timestamps_fail_closed(int $start, int $observed): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cronologia demonstrativa de trial invalida.');
        new TrialWindowAdvisory($start, $observed);
    }

    public function test_phase_is_never_an_entitlement_or_retention_authority(): void
    {
        $timeline = new TrialWindowAdvisory(100, 101);
        self::assertSame(TrialWindowPhase::WithinWindow, $timeline->phase());

        foreach (['canAccess', 'isEligible', 'authorize', 'createTrial', 'extend', 'suspend',
            'delete', 'charge', 'notify', 'retain', 'persist', 'unlock'] as $method) {
            self::assertFalse(method_exists($timeline, $method), $method);
        }
    }
}
