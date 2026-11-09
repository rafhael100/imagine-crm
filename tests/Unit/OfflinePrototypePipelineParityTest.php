<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\LeadScoringBands;
use App\Core\Leads\SyntheticPipelineSummary;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * F3-33 — parity of four fixed HTML fixture rows with the PHP lab summary.
 * No backend request, database, score calculation, rights or real lead data.
 */
final class OfflinePrototypePipelineParityTest extends TestCase
{
    public function test_offline_lead_fixture_matches_php_summary_and_temperature_bands(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        $document = new DOMDocument();
        $old = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }

        $xpath = new DOMXPath($document);
        $rows = $xpath->query('//section[@id="screen-leads"]//tbody/tr[@data-lead-fixture-stage]');
        self::assertNotFalse($rows);
        self::assertSame(4, $rows->length);

        $expectedTemperatures = ['frio' => 'cold', 'morno' => 'warm', 'quente' => 'hot'];
        $fixtures = [];
        $bands = new LeadScoringBands();

        foreach ($rows as $position => $row) {
            self::assertInstanceOf(DOMElement::class, $row);
            $scoreString = $row->getAttribute('data-lead-fixture-score');
            self::assertMatchesRegularExpression('/^(?:0|[1-9][0-9]?)$|^100$/D', $scoreString);
            $score = (int) $scoreString;

            self::assertSame((string) $position, $row->getAttribute('data-lead-fixture-order'));
            $temperature = $bands->classify($score)->value;
            self::assertSame($expectedTemperatures[$temperature], $row->getAttribute('data-lead-fixture-temperature'));

            $fixtures[] = ['stage' => $row->getAttribute('data-lead-fixture-stage'), 'score' => $score];
        }

        $summary = (new SyntheticPipelineSummary())->summarize($fixtures);
        self::assertSame(4, $summary['total']);
        self::assertSame(['entrada' => 2, 'em-contato' => 1, 'qualificacao' => 1], $summary['stages']);
        self::assertSame(['frio' => 1, 'morno' => 1, 'quente' => 2], $summary['temperatures']);
        self::assertFalse($summary['authorized']);
    }
}
