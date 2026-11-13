<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvDuplicateReview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvDuplicateReviewTest extends TestCase
{
    private static function bind(int $source, string $target): array
    {
        return ['source_position' => $source, 'target_field' => $target];
    }

    public function test_counts_exact_source_duplicates_separately_from_projection_collisions(): void
    {
        $csv = "label,ignored\nExample,one\nExample,one\nExample,two\nAnother,three";
        $out = (new PortableCsvDuplicateReview())->inspect(
            $csv, 'comma', [self::bind(0, 'name')], true,
        );

        self::assertSame(4, $out['local_data_row_count']);
        self::assertSame(1, $out['local_exact_source_repeated_row_count']);
        self::assertSame(2, $out['local_exact_mapped_repeated_row_count']);
        self::assertSame(1, $out['local_projection_collision_extra_row_count']);
        self::assertContains('EXACT_SOURCE_RECORDS_REPEATED_IN_LOCAL_SAMPLE', $out['warning_codes']);
        self::assertContains('DISTINCT_SOURCE_RECORDS_COLLAPSE_AFTER_COLUMN_MAPPING', $out['warning_codes']);
        self::assertSame(3, $out['warning_count']);
        self::assertFalse($out['can_deduplicate']);
        self::assertFalse($out['can_import']);
    }

    public function test_all_selected_columns_make_projection_collisions_zero(): void
    {
        $csv = "a;b\nFiction;one\nFiction;one\nFiction;two";
        $out = (new PortableCsvDuplicateReview())->inspect(
            $csv, 'semicolon', [self::bind(0, 'name'), self::bind(1, 'origin')], true,
        );
        self::assertSame(1, $out['local_exact_source_repeated_row_count']);
        self::assertSame(1, $out['local_exact_mapped_repeated_row_count']);
        self::assertSame(0, $out['local_projection_collision_extra_row_count']);
    }

    public function test_reordered_mapping_and_headerless_records_stay_distinct(): void
    {
        $out = (new PortableCsvDuplicateReview())->inspect(
            "A,B\nB,A\nA,B", 'comma',
            [self::bind(1, 'name'), self::bind(0, 'notes')], false,
        );
        self::assertSame(3, $out['local_data_row_count']);
        self::assertSame(1, $out['local_exact_source_repeated_row_count']);
        self::assertSame(1, $out['local_exact_mapped_repeated_row_count']);
        self::assertSame(0, $out['local_projection_collision_extra_row_count']);
        self::assertFalse($out['header_claimed']);
    }

    public function test_no_rows_after_claimed_header_is_not_duplicate(): void
    {
        $out = (new PortableCsvDuplicateReview())->inspect(
            'title,other', 'comma', [self::bind(0, 'name')], true,
        );
        self::assertSame(0, $out['local_data_row_count']);
        self::assertSame(0, $out['local_exact_source_repeated_row_count']);
        self::assertSame(0, $out['local_exact_mapped_repeated_row_count']);
    }

    public function test_output_never_discloses_values_or_row_fingerprints(): void
    {
        $out = (new PortableCsvDuplicateReview())->inspect(
            "PRIVATE_LABEL,hidden\nSECRET_FIXTURE,one\nSECRET_FIXTURE,two",
            'comma', [self::bind(0, 'name')], true,
        );
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['PRIVATE_LABEL', 'SECRET_FIXTURE', '"name"', '"rows"', '"fingerprint"', '"hash"'] as $secret) {
            self::assertStringNotContainsString($secret, $json);
        }
    }

    public function test_rejects_bad_source_mapping_and_claim_without_echoing_csv(): void
    {
        foreach ([
            ['SECRET_FIXTURE', 'pipe', [self::bind(0, 'name')], true],
            ['SECRET_FIXTURE', 'comma', [self::bind(5, 'name')], true],
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], 1],
            ['a,"unterminated', 'comma', [self::bind(0, 'name')], false],
        ] as $arguments) {
            try {
                (new PortableCsvDuplicateReview())->inspect(...$arguments);
                self::fail('Invalid sample accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('Revisao local de repeticoes CSV invalida.', $exception->getMessage());
                self::assertStringNotContainsString('SECRET_FIXTURE', $exception->getMessage());
            }
        }
    }
}
