<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticColumnMappingInspector;
use App\Core\Portability\SyntheticColumnMappingSummary;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticColumnMappingInspectorTest extends TestCase
{
    public function test_complete_mapping_is_canonical_but_never_authorizes_import(): void
    {
        $report = (new SyntheticColumnMappingInspector())->assess(
            ['label', 'source', 'note'],
            ['description', 'origin', 'comment'],
            [['source' => 3, 'target' => 1], ['source' => 1, 'target' => 3], ['source' => 2, 'target' => 2]],
        );

        self::assertSame(3, $report->sourceColumns);
        self::assertSame(3, $report->targetColumns);
        self::assertSame(3, $report->mappedColumns());
        self::assertSame([
            ['source' => 1, 'target' => 3],
            ['source' => 2, 'target' => 2],
            ['source' => 3, 'target' => 1],
        ], $report->positions);
        self::assertSame([], $report->unmappedSourcePositions);
        self::assertSame([], $report->unmappedTargetPositions);
        self::assertTrue($report->isCompleteOneToOne());
        self::assertFalse($report->canImport());
        self::assertTrue((new \ReflectionClass(SyntheticColumnMappingSummary::class))->isReadOnly());

        $serialized = json_encode($report, JSON_THROW_ON_ERROR);
        foreach (['description', 'origin', 'comment', 'label', 'note'] as $label) {
            self::assertStringNotContainsString('"'.$label.'"', $serialized);
        }
    }

    public function test_incomplete_mapping_has_missing_positions_without_auto_filling(): void
    {
        $result = (new SyntheticColumnMappingInspector())->assess(
            ['a', 'b', 'c'], ['x', 'y', 'z', 'k'],
            [['source' => 2, 'target' => 4]],
        );
        self::assertSame(1, $result->mappedColumns());
        self::assertSame([1, 3], $result->unmappedSourcePositions);
        self::assertSame([1, 2, 3], $result->unmappedTargetPositions);
        self::assertFalse($result->isCompleteOneToOne());
        self::assertFalse($result->canImport());
    }

    public function test_eight_columns_are_a_lab_boundary_not_a_business_limit(): void
    {
        $source = ['a','b','c','d','e','f','g','h'];
        $target = ['i','j','k','l','m','n','o','p'];
        $pairs = [];
        for ($index = 1; $index <= 8; $index++) {
            $pairs[] = ['source' => $index, 'target' => $index];
        }
        $report = (new SyntheticColumnMappingInspector())->assess($source, $target, $pairs);
        self::assertSame(8, $report->mappedColumns());
        self::assertTrue($report->isCompleteOneToOne());
        self::assertFalse($report->canImport());
    }

    public static function malformedFixtures(): array
    {
        $sources = ['label', 'source'];
        $targets = ['description', 'origin'];
        $pairs = [['source' => 1, 'target' => 2]];
        return [
            'empty source list' => [[], $targets, $pairs],
            'empty target list' => [$sources, [], $pairs],
            'empty pairs' => [$sources, $targets, []],
            '9 source columns' => [['a','b','c','d','e','f','g','h','i'], $targets, $pairs],
            '9 target columns' => [$sources, ['a','b','c','d','e','f','g','h','i'], $pairs],
            'duplicate source labels' => [['label','label'], $targets, $pairs],
            'duplicate target labels' => [$sources, ['origin','origin'], $pairs],
            'same source twice' => [$sources, $targets, [['source'=>1,'target'=>1],['source'=>1,'target'=>2]]],
            'same target twice' => [$sources, $targets, [['source'=>1,'target'=>1],['source'=>2,'target'=>1]]],
            'zero source' => [$sources, $targets, [['source'=>0,'target'=>1]]],
            'zero target' => [$sources, $targets, [['source'=>1,'target'=>0]]],
            'source beyond range' => [$sources, $targets, [['source'=>3,'target'=>1]]],
            'target beyond range' => [$sources, $targets, [['source'=>1,'target'=>3]]],
            'negative source' => [$sources, $targets, [['source'=>-1,'target'=>1]]],
            'huge source' => [$sources, $targets, [['source'=>PHP_INT_MAX,'target'=>1]]],
            'boolean index' => [$sources, $targets, [['source'=>true,'target'=>1]]],
            'numeric string index' => [$sources, $targets, [['source'=>'1','target'=>1]]],
            'extra allow field' => [$sources, $targets, [['source'=>1,'target'=>2,'allow'=>true]]],
            'reversed pair shape' => [$sources, $targets, [['target'=>2,'source'=>1]]],
            'pair list is associative' => [$sources, $targets, ['first'=>['source'=>1,'target'=>2]]],
            'source labels are associative' => [['first'=>'label'], $targets, $pairs],
            'target labels are associative' => [$sources, ['first'=>'description'], $pairs],
            'formula source label' => [['=CMD'], $targets, $pairs],
            'formula target label' => [$sources, ['+x'], $pairs],
            'unicode control source label' => [["\u{200B}label"], $targets, $pairs],
            'uppercase source label' => [['Label'], $targets, $pairs],
            'overlong label' => [[str_repeat('a', 33)], $targets, $pairs],
            'nested label' => [[['label'=>'value']], $targets, $pairs],
        ];
    }

    #[DataProvider('malformedFixtures')]
    public function test_invalid_mapping_fails_closed_without_echoing_values(
        array $source,
        array $target,
        array $pairs,
    ): void {
        try {
            (new SyntheticColumnMappingInspector())->assess($source, $target, $pairs);
            self::fail('Malformed mapping passed validation.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Mapeamento sintetico de colunas invalido.', $exception->getMessage());
            self::assertStringNotContainsString('label', $exception->getMessage());
            self::assertStringNotContainsString('description', $exception->getMessage());
        }
    }

    public function test_mapping_is_never_an_authority_or_mutation(): void
    {
        $result = (new SyntheticColumnMappingInspector())->assess(
            ['label'], ['description'], [['source' => 1, 'target' => 1]],
        );
        foreach (['apply', 'import', 'export', 'save', 'delete', 'authorize',
            'allow', 'mergeLeads', 'persist', 'create', 'notify', 'bill'] as $method) {
            self::assertFalse(method_exists($result, $method));
        }
        self::assertFalse($result->canImport());
    }
}
