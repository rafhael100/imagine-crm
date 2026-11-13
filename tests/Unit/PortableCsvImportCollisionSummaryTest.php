<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvImportReadinessSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvImportCollisionSummaryTest extends TestCase
{
    private static function bind(int $position, string $field): array
    {
        return ['source_position' => $position, 'target_field' => $field];
    }

    public function test_complete_preview_exposes_only_aggregate_collision_counts(): void
    {
        $csv = "title,ignored\nPRIVATE_MARKER,one\nPRIVATE_MARKER,one\nPRIVATE_MARKER,two";
        $report = (new PortableCsvImportReadinessSummary())->inspect(
            $csv, 'comma', [self::bind(0, 'name')], true,
        );

        self::assertSame(3, $report['local_data_row_count']);
        self::assertSame(1, $report['local_exact_source_repeated_row_count']);
        self::assertSame(2, $report['local_exact_mapped_repeated_row_count']);
        self::assertSame(1, $report['local_projection_collision_extra_row_count']);
        self::assertSame(count($report['warning_codes']), $report['warning_count']);
        self::assertSame(count(array_unique($report['warning_codes'])), $report['warning_count']);
        self::assertContains('EXACT_SOURCE_RECORDS_REPEATED_IN_LOCAL_SAMPLE', $report['warning_codes']);
        self::assertContains('DISTINCT_SOURCE_RECORDS_COLLAPSE_AFTER_COLUMN_MAPPING', $report['warning_codes']);
        $output = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('PRIVATE_MARKER', $output);
        self::assertStringNotContainsString('"hash"', $output);
        foreach (['can_import', 'can_export', 'can_merge', 'can_deduplicate', 'can_persist', 'authorization_verified', 'duplicate_person_identity_verified'] as $code) {
            self::assertFalse($report[$code], $code);
        }
    }

    public function test_distinct_source_and_mapped_rows_have_zero_repetition(): void
    {
        $report = (new PortableCsvImportReadinessSummary())->inspect(
            "a;b\nA;one\nB;two", 'semicolon',
            [self::bind(0, 'name'), self::bind(1, 'origin')], true,
        );
        self::assertSame(0, $report['local_exact_source_repeated_row_count']);
        self::assertSame(0, $report['local_exact_mapped_repeated_row_count']);
        self::assertSame(0, $report['local_projection_collision_extra_row_count']);
        self::assertNotContains('EXACT_SOURCE_RECORDS_REPEATED_IN_LOCAL_SAMPLE', $report['warning_codes']);
    }

    public function test_invalid_mapping_never_echoes_untrusted_records(): void
    {
        try {
            (new PortableCsvImportReadinessSummary())->inspect(
                'SECRET_PRIVATE', 'comma', [self::bind(15, 'name')], true,
            );
            self::fail('Expected fail closed validation');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Revisao agregada CSV local invalida.', $exception->getMessage());
            self::assertStringNotContainsString('SECRET_PRIVATE', $exception->getMessage());
        }
    }
}
