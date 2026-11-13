<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineCnpjTrialHmacBindingTest extends TestCase
{
    private const KEY_A = 'laboratory-key-A-longer-than-thirty-two-bytes';
    private const KEY_B = 'laboratory-key-B-longer-than-thirty-two-bytes';

    public function test_equivalent_numeric_representations_have_same_private_binding(): void
    {
        $engine = new OfflineCnpjTrialHmacBinding();
        $fromCompact = $engine->derive('11222333000181', self::KEY_A);
        $fromPunctuated = $engine->derive('11.222.333/0001-81', self::KEY_A);
        self::assertSame($fromCompact, $fromPunctuated);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $fromCompact);
        self::assertStringNotContainsString('11222333000181', $fromCompact);
        self::assertNotSame($fromCompact, $engine->derive('11222333000181', self::KEY_B));
    }

    public function test_equivalent_alphanumeric_representations_have_same_private_binding(): void
    {
        $engine = new OfflineCnpjTrialHmacBinding();
        $a = $engine->derive('12.ABC.345/01DE-35', self::KEY_A);
        $b = $engine->derive('12abc34501de35', self::KEY_A);
        $c = $engine->derive(" 12ABC34501DE35 \t", self::KEY_A);
        self::assertSame($a, $b);
        self::assertSame($a, $c);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $a);
        self::assertStringNotContainsString('12ABC34501DE35', $a);
        self::assertNotSame($a, $engine->derive('11222333000181', self::KEY_A));
    }

    public function test_invalid_documents_and_secrets_never_produce_identifiers(): void
    {
        $engine = new OfflineCnpjTrialHmacBinding();
        foreach ([
            ['12.ABC.345/01DE-36', self::KEY_A],
            ['11222333000182', self::KEY_A],
            ['00000000000000', self::KEY_A],
            ["12ABC34501DE35\u{200B}", self::KEY_A],
            ['12ABC34501DE35', 'short-key'],
            ['12ABC34501DE35', ''],
            [null, self::KEY_A],
            [12345678901234, self::KEY_A],
        ] as $args) {
            try {
                $engine->derive(...$args);
                self::fail('Invalid input must not derive a trial binding.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Vinculo CNPJ de trial local invalido.', $e->getMessage());
                self::assertStringNotContainsString('ABC', $e->getMessage());
                self::assertStringNotContainsString('11222333000182', $e->getMessage());
            }
        }
    }

    public function test_digest_is_purpose_separated_from_plain_sha_and_does_not_authorize(): void
    {
        $engine = new OfflineCnpjTrialHmacBinding();
        $binding = $engine->derive('12.ABC.345/01DE-35', self::KEY_A);
        self::assertNotSame(hash('sha256', '12ABC34501DE35'), $binding);
        self::assertNotSame(hash_hmac('sha256', '12ABC34501DE35', self::KEY_A), $binding);
        self::assertFalse(method_exists($engine, 'authorize'));
        self::assertFalse(method_exists($engine, 'claimTrial'));
        self::assertFalse(method_exists($engine, 'register'));
        self::assertFalse(method_exists($engine, 'persist'));
    }
}
