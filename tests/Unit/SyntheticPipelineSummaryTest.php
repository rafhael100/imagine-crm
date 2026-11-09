<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticPipelineSummary;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntheticPipelineSummaryTest extends TestCase
{
    public function test_aggregates_only_mock_counts_and_denies_authority(): void
    {
        $result = (new SyntheticPipelineSummary())->summarize([
            ['stage' => 'entrada', 'score' => 30],
            ['stage' => 'em-contato', 'score' => 55],
            ['stage' => 'qualificacao', 'score' => 85],
            ['stage' => 'entrada', 'score' => 95],
        ]);
        self::assertSame(4, $result['total']);
        self::assertSame(['entrada' => 2, 'em-contato' => 1, 'qualificacao' => 1], $result['stages']);
        self::assertSame(['frio' => 1, 'morno' => 1, 'quente' => 2], $result['temperatures']);
        self::assertSame(['entrada' => 1, 'em-contato' => 0, 'qualificacao' => 1], $result['hot_by_stage']);
        self::assertFalse($result['authorized']);
        self::assertSame(['total', 'stages', 'temperatures', 'hot_by_stage', 'authorized'], array_keys($result));
    }

    public function test_empty_fixtures_return_zero_counts_without_authority(): void
    {
        $result = (new SyntheticPipelineSummary())->summarize([]);
        self::assertSame(0, $result['total']);
        self::assertSame([0, 0, 0], array_values($result['stages']));
        self::assertSame([0, 0, 0], array_values($result['temperatures']));
        self::assertFalse($result['authorized']);
    }

    public function test_twenty_synthetic_entries_are_allowed_without_persistence(): void
    {
        $result = (new SyntheticPipelineSummary())->summarize(array_fill(0, 20, ['stage' => 'entrada', 'score' => 0]));
        self::assertSame(20, $result['total']);
        self::assertSame(20, $result['temperatures']['frio']);
    }

    public static function invalidFixtures(): array
    {
        return [
            'unknown stage' => [[['stage' => 'ganho', 'score' => 50]]],
            'untrusted tenant field' => [[['stage' => 'entrada', 'score' => 10, 'tenant_id' => 'forged']]],
            'untrusted principal field' => [[['stage' => 'entrada', 'score' => 10, 'principal' => 'forged']]],
            'untrusted lead id' => [[['stage' => 'entrada', 'score' => 10, 'lead_id' => 'forged']]],
            'score string' => [[['stage' => 'entrada', 'score' => '90']]],
            'score negative' => [[['stage' => 'entrada', 'score' => -1]]],
            'score too high' => [[['stage' => 'entrada', 'score' => 101]]],
            'missing stage' => [[['score' => 30]]],
            'reversed keys' => [[['score' => 30, 'stage' => 'entrada']]],
            'more than 20' => [array_fill(0, 21, ['stage' => 'entrada', 'score' => 0])],
            'not a list' => [[7 => ['stage' => 'entrada', 'score' => 0]]],
        ];
    }

    #[DataProvider('invalidFixtures')]
    public function test_untrusted_or_malformed_fixtures_are_rejected(array $fixtures): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SyntheticPipelineSummary())->summarize($fixtures);
    }
}
