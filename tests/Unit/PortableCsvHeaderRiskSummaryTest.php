<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvHeaderRiskSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvHeaderRiskSummaryTest extends TestCase
{
    public function test_clean_header_is_counted_without_returning_source_values(): void
    {
        $csv = "SENSITIVE_NAME,SENSITIVE_EMAIL\nFiction,example.invalid";
        $out = (new PortableCsvHeaderRiskSummary())->inspect($csv, 'comma', true);
        self::assertSame(2, $out['local_source_column_count']);
        self::assertSame(1, $out['local_data_row_count']);
        self::assertSame(0, $out['local_empty_header_count']);
        self::assertSame(0, $out['local_duplicate_exact_header_count']);
        self::assertSame(0, $out['local_header_refused_cell_count']);
        self::assertSame(['HEADER_AUTHORITY_AND_SCHEMA_NOT_VERIFIED'], $out['warning_codes']);
        self::assertStringNotContainsString('SENSITIVE', json_encode($out, JSON_THROW_ON_ERROR));
        self::assertFalse($out['can_import']);
        self::assertFalse($out['authorization_verified']);
    }

    public function test_duplicate_empty_and_formula_like_headers_are_separately_reported(): void
    {
        $csv = "alpha,alpha, ,=HYPERLINK(1),other\n1,2,3,4,5";
        $out = (new PortableCsvHeaderRiskSummary())->inspect($csv, 'comma', true);
        self::assertSame(1, $out['local_duplicate_exact_header_count']);
        self::assertSame(1, $out['local_empty_header_count']);
        self::assertSame(1, $out['local_header_refused_cell_count']);
        self::assertContains('DUPLICATE_EXACT_CLAIMED_HEADER', $out['warning_codes']);
        self::assertContains('EMPTY_CLAIMED_HEADER', $out['warning_codes']);
        self::assertContains('CLAIMED_HEADER_REFUSED_BY_STRICT_CSV_WRITER', $out['warning_codes']);
        self::assertStringNotContainsString('HYPERLINK', json_encode($out, JSON_THROW_ON_ERROR));
    }

    public function test_no_header_claim_does_not_interpret_first_record_as_titles(): void
    {
        $out = (new PortableCsvHeaderRiskSummary())->inspect('a,a\n1,2', 'comma', false);
        self::assertSame(0, $out['local_duplicate_exact_header_count']);
        self::assertSame(2, $out['local_data_row_count']);
        self::assertContains('NO_HEADER_DECLARED', $out['warning_codes']);
    }

    public function test_single_header_row_has_no_data_warning(): void
    {
        $out = (new PortableCsvHeaderRiskSummary())->inspect('a;b', 'semicolon', true);
        self::assertSame(0, $out['local_data_row_count']);
        self::assertContains('NO_DATA_RECORDS_IN_LOCAL_SAMPLE', $out['warning_codes']);
    }

    public function test_invalid_inputs_fail_closed_without_echoing_data(): void
    {
        foreach ([
            ['PrivateFixture', 'pipe', true],
            ['PrivateFixture', 'comma', 'true'],
            ['a,"unterminated', 'comma', true],
            ['', 'comma', true],
        ] as $args) {
            try {
                (new PortableCsvHeaderRiskSummary())->inspect(...$args);
                self::fail('Invalid sample accepted');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Resumo local de cabecalho CSV invalido.', $e->getMessage());
                self::assertStringNotContainsString('PrivateFixture', $e->getMessage());
            }
        }
    }
}
