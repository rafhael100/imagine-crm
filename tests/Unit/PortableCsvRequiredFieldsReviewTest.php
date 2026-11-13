<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvRequiredFieldsReview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvRequiredFieldsReviewTest extends TestCase
{
    private static function bind(int $position, string $target): array
    {
        return ['source_position' => $position, 'target_field' => $target];
    }

    public function test_separates_unmapped_requirements_from_empty_mapped_values(): void
    {
        $result = (new PortableCsvRequiredFieldsReview())->inspect(
            "title,mail\nAlpha,\n,second\nBeta,third",
            'comma',
            [self::bind(0, 'name'), self::bind(1, 'email')],
            true,
            ['name', 'email', 'phone'],
        );
        self::assertSame(3, $result['local_data_row_count']);
        self::assertSame(3, $result['local_claimed_required_field_count']);
        self::assertSame(1, $result['local_unmapped_required_field_count']);
        self::assertSame(2, $result['local_empty_required_value_count']);
        self::assertSame(2, $result['local_rows_with_empty_required_value_count']);
        self::assertContains('CLAIMED_REQUIRED_FIELDS_NOT_MAPPED', $result['warning_codes']);
        self::assertContains('EMPTY_VALUES_IN_CLAIMED_REQUIRED_FIELDS', $result['warning_codes']);
        self::assertFalse($result['can_import']);
        self::assertFalse($result['required_policy_verified']);
    }

    public function test_all_complete_selected_requirements_are_still_not_authorization(): void
    {
        $result = (new PortableCsvRequiredFieldsReview())->inspect(
            "alpha;beta\none;two", 'semicolon',
            [self::bind(0, 'name'), self::bind(1, 'status')], true, ['status', 'name'],
        );
        self::assertSame(0, $result['local_empty_required_value_count']);
        self::assertSame(0, $result['local_unmapped_required_field_count']);
        self::assertSame(['CLAIMED_REQUIRED_FIELD_POLICY_NOT_VERIFIED'], $result['warning_codes']);
        self::assertFalse($result['authorization_verified']);
    }

    public function test_all_unmapped_requirements_do_not_count_as_empty_mapped_cells(): void
    {
        $result = (new PortableCsvRequiredFieldsReview())->inspect(
            "plain\nvalue", 'comma', [self::bind(0, 'name')], true, ['consent', 'phone'],
        );
        self::assertSame(2, $result['local_unmapped_required_field_count']);
        self::assertSame(0, $result['local_empty_required_value_count']);
        self::assertSame(0, $result['local_rows_with_empty_required_value_count']);
        self::assertFalse($result['consent_evidence_verified']);
    }

    public function test_headerless_input_stays_data_and_no_data_does_not_create_empty_warning(): void
    {
        $engine = new PortableCsvRequiredFieldsReview();
        $first = $engine->inspect("A,\nB,x", 'comma', [self::bind(1, 'notes')], false, ['notes']);
        self::assertSame(2, $first['local_data_row_count']);
        self::assertSame(1, $first['local_rows_with_empty_required_value_count']);

        $second = $engine->inspect('title', 'comma', [self::bind(0, 'name')], true, ['name']);
        self::assertSame(0, $second['local_data_row_count']);
        self::assertSame(0, $second['local_empty_required_value_count']);
    }

    public function test_invalid_fields_mapping_and_source_are_rejected_without_echo(): void
    {
        $engine = new PortableCsvRequiredFieldsReview();
        foreach ([
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], true, []],
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], true, ['name', 'name']],
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], true, ['password']],
            ['SECRET_FIXTURE', 'comma', [self::bind(5, 'name')], true, ['name']],
            ['SECRET_FIXTURE', 'pipe', [self::bind(0, 'name')], true, ['name']],
            ['SECRET_FIXTURE', 'comma', [self::bind(0, 'name')], 'yes', ['name']],
        ] as $args) {
            try {
                $engine->inspect(...$args);
                self::fail('Invalid input accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Revisao local de campos obrigatorios CSV invalida.', $e->getMessage());
                self::assertStringNotContainsString('SECRET_FIXTURE', $e->getMessage());
            }
        }
    }

    public function test_output_does_not_echo_record_values_or_list_of_declared_fields(): void
    {
        $out = (new PortableCsvRequiredFieldsReview())->inspect(
            "PRIVATE_HEADER,second\nSENSITIVE_VALUE,", 'comma',
            [self::bind(0, 'name'), self::bind(1, 'consent')], true, ['consent'],
        );
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['PRIVATE_HEADER', 'SENSITIVE_VALUE', '"name"', '"consent"', '"rows"'] as $secret) {
            self::assertStringNotContainsString($secret, $json);
        }
        self::assertFalse($out['consent_evidence_verified']);
        self::assertFalse($out['can_persist']);
    }
}
