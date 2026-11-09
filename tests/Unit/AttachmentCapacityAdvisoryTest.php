<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\AttachmentCapacityAdvisory;
use App\Core\Plans\AttachmentCapacityBand;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AttachmentCapacityAdvisoryTest extends TestCase
{
    public static function warningBoundaries(): array
    {
        return [
            'zero limit and empty' => [0, 0, AttachmentCapacityBand::Unavailable, 0, 0],
            'zero limit with existing content' => [0, 1, AttachmentCapacityBand::OverLimit, 0, 1],
            'empty capacity' => [100, 0, AttachmentCapacityBand::BelowNotice, 100, 0],
            'one byte below 80%' => [100, 79, AttachmentCapacityBand::BelowNotice, 21, 0],
            'exactly 80%' => [100, 80, AttachmentCapacityBand::Notice80, 20, 0],
            'one byte below 95%' => [100, 94, AttachmentCapacityBand::Notice80, 6, 0],
            'exactly 95%' => [100, 95, AttachmentCapacityBand::Notice95, 5, 0],
            'almost full' => [100, 99, AttachmentCapacityBand::Notice95, 1, 0],
            'at exactly full' => [100, 100, AttachmentCapacityBand::AtLimit, 0, 0],
            'above full' => [100, 101, AttachmentCapacityBand::OverLimit, 0, 1],
            'non-rounded 80% not yet reached' => [101, 80, AttachmentCapacityBand::BelowNotice, 21, 0],
            'non-rounded 80% reached' => [101, 81, AttachmentCapacityBand::Notice80, 20, 0],
            'non-rounded 95% not yet reached' => [101, 95, AttachmentCapacityBand::Notice80, 6, 0],
            'non-rounded 95% reached' => [101, 96, AttachmentCapacityBand::Notice95, 5, 0],
            'one byte capacity empty' => [1, 0, AttachmentCapacityBand::BelowNotice, 1, 0],
            'one byte capacity consumed' => [1, 1, AttachmentCapacityBand::AtLimit, 0, 0],
            'one byte capacity exceeded' => [1, 2, AttachmentCapacityBand::OverLimit, 0, 1],
            'storage is unitless bytes not MB' => [1000000, 800000, AttachmentCapacityBand::Notice80, 200000, 0],
            'integer max near full' => [PHP_INT_MAX, PHP_INT_MAX-1, AttachmentCapacityBand::Notice95, 1, 0],
            'integer max exact full' => [PHP_INT_MAX, PHP_INT_MAX, AttachmentCapacityBand::AtLimit, 0, 0],
            'integer max fully consumed while limit is 1' => [1, PHP_INT_MAX, AttachmentCapacityBand::OverLimit, 0, PHP_INT_MAX-1],
        ];
    }

    #[DataProvider('warningBoundaries')]
    public function test_attachment_warning_math_is_pure_snapshot_read_only(
        int $limit,
        int $used,
        AttachmentCapacityBand $expected,
        int $remaining,
        int $excess,
    ): void {
        $advisory = new AttachmentCapacityAdvisory($limit, $used);

        self::assertSame($expected, $advisory->band());
        self::assertSame($remaining, $advisory->remainingBytes());
        self::assertSame($excess, $advisory->excessBytes());
        self::assertSame(['limitBytes', 'usedBytes'], array_keys(get_object_vars($advisory)));
    }

    public function test_very_large_thresholds_do_not_overflow_or_cast_to_float(): void
    {
        $limit = PHP_INT_MAX;
        $cutoff80 = intdiv($limit, 100) * 80 + intdiv(($limit % 100) * 80 + 99, 100);
        $cutoff95 = intdiv($limit, 100) * 95 + intdiv(($limit % 100) * 95 + 99, 100);

        self::assertSame(AttachmentCapacityBand::BelowNotice, (new AttachmentCapacityAdvisory($limit, $cutoff80 - 1))->band());
        self::assertSame(AttachmentCapacityBand::Notice80, (new AttachmentCapacityAdvisory($limit, $cutoff80))->band());
        self::assertSame(AttachmentCapacityBand::Notice80, (new AttachmentCapacityAdvisory($limit, $cutoff95 - 1))->band());
        self::assertSame(AttachmentCapacityBand::Notice95, (new AttachmentCapacityAdvisory($limit, $cutoff95))->band());
    }

    public static function invalidByteSnapshots(): array
    {
        return [
            'negative limit' => [-1, 0],
            'negative usage' => [10, -1],
            'negative both' => [-10, -20],
        ];
    }

    #[DataProvider('invalidByteSnapshots')]
    public function test_negative_usage_is_rejected_without_zero_clamping(int $limit, int $used): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Medicao de anexos invalida.');
        new AttachmentCapacityAdvisory($limit, $used);
    }

    public function test_display_band_is_not_a_permission_to_upload_or_bill(): void
    {
        $usage = new AttachmentCapacityAdvisory(100, 60);
        self::assertSame(AttachmentCapacityBand::BelowNotice, $usage->band());
        self::assertTrue((new \ReflectionClass(AttachmentCapacityAdvisory::class))->isReadOnly());
        foreach (['canUpload', 'allow', 'authorize', 'charge', 'notify', 'deleteFiles', 'reserve', 'commit'] as $method) {
            self::assertFalse(method_exists($usage, $method), $method);
        }

        // Prospective uploads require a separate trusted rights and
        // transactional usage check; this is not an admission decision.
        self::assertSame(AttachmentCapacityBand::OverLimit, (new AttachmentCapacityAdvisory(100, 150))->band());
        self::assertSame(50, (new AttachmentCapacityAdvisory(100, 150))->excessBytes());
    }
}
