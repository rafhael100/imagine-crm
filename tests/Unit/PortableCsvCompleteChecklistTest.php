<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvImportReadinessSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortableCsvCompleteChecklistTest extends TestCase
{
    private static function binding(int $position, string $field): array
    {
        return ['source_position' => $position, 'target_field' => $field];
    }

    public function test_opt_in_includes_required_field_and_duplicate_diagnostics_without_values(): void
    {
        $csv = "title,mail,omitted\nPRIVATE_A,,one\nPRIVATE_A,,two";
        $inspector = new PortableCsvImportReadinessSummary();
        $report = $inspector->inspect(
            $csv, 'comma',
            [self::binding(0, 'name'), self::binding(1, 'email')],
            true, ['name', 'email', 'phone'],
        );

        self::assertSame(2, $report['local_data_row_count']);
        self::assertSame(1, $report['local_projection_collision_extra_row_count']);
        self::assertSame(0, $report['local_exact_source_repeated_row_count']);
        self::assertSame(3, $report['local_claimed_required_field_count']);
        self::assertSame(1, $report['local_unmapped_required_field_count']);
        self::assertSame(2, $report['local_empty_required_value_count']);
        self::assertSame(2, $report['local_rows_with_empty_required_value_count']);
        self::assertSame(count($report['warning_codes']), $report['warning_count']);
        self::assertContains('CLAIMED_REQUIRED_FIELDS_NOT_MAPPED', $report['warning_codes']);
        self::assertContains('EMPTY_VALUES_IN_CLAIMED_REQUIRED_FIELDS', $report['warning_codes']);
        $json = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['PRIVATE_A', '"name"', '"email"', '"phone"', '"rows"'] as $value) {
            self::assertStringNotContainsString($value, $json);
        }
        foreach (['can_import','can_export','can_merge','can_deduplicate','can_persist','required_policy_verified'] as $flag) {
            self::assertFalse($report[$flag]);
        }
    }

    public function test_default_four_argument_compatibility_preserves_exact_report(): void
    {
        $inspector = new PortableCsvImportReadinessSummary();
        $args = ["header,value\nA,one", 'comma', [self::binding(0, 'name')], true];
        $without = $inspector->inspect(...$args);
        $explicitNull = $inspector->inspect(...[...$args, null]);
        self::assertSame($without, $explicitNull);
        self::assertArrayNotHasKey('local_claimed_required_field_count', $without);
        self::assertNotContains('CLAIMED_REQUIRED_FIELD_POLICY_NOT_VERIFIED', $without['warning_codes']);
    }

    public function test_invalid_opt_in_claim_is_rejected_without_echo(): void
    {
        try {
            (new PortableCsvImportReadinessSummary())->inspect(
                'SECRET_PRIVATE', 'comma', [self::binding(0, 'name')], false, ['password'],
            );
            self::fail('Unknown required field should be invalid.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Revisao agregada CSV local invalida.', $e->getMessage());
            self::assertStringNotContainsString('SECRET_PRIVATE', $e->getMessage());
        }
    }
}
