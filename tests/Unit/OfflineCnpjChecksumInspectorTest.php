<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineCnpjChecksumInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OfflineCnpjChecksumInspectorTest extends TestCase
{
    public static function officialAndLegacyExamples(): array
    {
        return [
            'official RFB alphanumeric 12.ABC.345/01DE-35' => ['12.ABC.345/01DE-35', 'alphanumeric'],
            'official alphanumeric compact uppercase' => ['12ABC34501DE35', 'alphanumeric'],
            'official alphanumeric compact lowercase' => ['12abc34501de35', 'alphanumeric'],
            'official alphanumeric surrounding ASCII whitespace' => [" 12.ABC.345/01DE-35\t", 'alphanumeric'],
            'legacy standard numeric CNPJ example' => ['11.222.333/0001-81', 'numeric'],
            'legacy compact digits' => ['11222333000181', 'numeric'],
        ];
    }

    #[DataProvider('officialAndLegacyExamples')]
    public function test_numeric_and_new_alphanumeric_formats_share_mod11(string $candidate, string $kind): void
    {
        $out = (new OfflineCnpjChecksumInspector())->inspect($candidate);
        self::assertSame('structurally_valid_only', $out['status_code']);
        self::assertSame($kind, $out['format_kind']);
        self::assertTrue($out['shape_valid']);
        self::assertTrue($out['check_digits_valid']);
        self::assertFalse($out['registered_company_verified']);
        self::assertFalse($out['company_owner_verified']);
        self::assertFalse($out['one_trial_per_cnpj_verified']);
        self::assertFalse($out['can_register']);
        self::assertFalse($out['can_reserve_trial']);
    }

    public static function invalidCheckDigits(): array
    {
        return [
            ['12.ABC.345/01DE-36', 'alphanumeric'],
            ['12ABC34501DE30', 'alphanumeric'],
            ['11.222.333/0001-82', 'numeric'],
            ['11222333000180', 'numeric'],
            ['00000000000000', 'numeric'],
            ['11111111111111', 'numeric'],
        ];
    }

    #[DataProvider('invalidCheckDigits')]
    public function test_wrong_checksum_and_repeated_numeric_identifiers_are_rejected(string $input, string $kind): void
    {
        $out = (new OfflineCnpjChecksumInspector())->inspect($input);
        self::assertSame('check_digits_rejected', $out['status_code']);
        self::assertSame($kind, $out['format_kind']);
        self::assertTrue($out['shape_valid']);
        self::assertFalse($out['check_digits_valid']);
        self::assertFalse($out['can_register']);
    }

    public static function invalidSyntax(): array
    {
        return [
            ['12.ABC.345/01DE-AA'],
            ['12ABC34501DE3X'],
            ['12-ABC-345/01DE-35'],
            ['12.ABC.345/01DE-35-extra'],
            ['12ＡBC34501DE35'],
            ["12ABC34501DE35\u{200B}"],
            ["12ABC34501DE35\njunk"],
            ['12ABC34501DE35@example.com'],
            [''],
            [null],
            [12345678901234],
            [str_repeat('A', 1000)],
        ];
    }

    #[DataProvider('invalidSyntax')]
    public function test_malformed_and_unicode_confusable_inputs_are_not_normalized_into_valid_cnpj(mixed $input): void
    {
        $out = (new OfflineCnpjChecksumInspector())->inspect($input);
        self::assertSame('format_rejected', $out['status_code']);
        self::assertSame('unknown', $out['format_kind']);
        self::assertFalse($out['shape_valid']);
        self::assertFalse($out['check_digits_valid']);
        self::assertFalse($out['can_grant_access']);
    }

    public function test_assessment_never_returns_source_document_or_raw_hash(): void
    {
        $engine = new OfflineCnpjChecksumInspector();
        foreach (['12.ABC.345/01DE-35', '11.222.333/0001-81', 'PRIVATE_CNPJ_INPUT'] as $raw) {
            $out = $engine->inspect($raw);
            $wire = json_encode($out, JSON_THROW_ON_ERROR);
            self::assertStringNotContainsString($raw, $wire);
            self::assertStringNotContainsString('11222333000181', $wire);
            self::assertStringNotContainsString('12ABC34501DE35', $wire);
            self::assertStringNotContainsString('PRIVATE_CNPJ_INPUT', $wire);
            self::assertArrayNotHasKey('cnpj', $out);
            self::assertArrayNotHasKey('normalized', $out);
            foreach (['can_register', 'can_reserve_trial', 'can_persist'] as $flag) {
                self::assertFalse($out[$flag]);
            }
        }
    }
}
