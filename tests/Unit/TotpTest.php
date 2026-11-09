<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\Totp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    public static function rfcVectors(): array
    {
        return [[59, '94287082'], [1111111109, '07081804'], [1111111111, '14050471'],
            [1234567890, '89005924'], [2000000000, '69279037'], [20000000000, '65353130']];
    }

    #[DataProvider('rfcVectors')]
    public function test_rfc_6238_sha1_vectors(int $time, string $expected): void
    {
        $this->assertSame($expected, (new Totp())->code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $time, 8));
    }

    public function test_malformed_code_and_outside_window_are_rejected(): void
    {
        $totp = new Totp();
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertNull($totp->matchStep($secret, '1e1234', 1234567890));
        $this->assertNull($totp->matchStep($secret, $totp->code($secret, 1234567890 - 300), 1234567890));
        $this->assertSame(intdiv(1234567890, 30), $totp->matchStep($secret, $totp->code($secret, 1234567890), 1234567890));
    }
}
