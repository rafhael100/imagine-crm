<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plans\CommercialPlanReferenceCatalog;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * WEB-P2: detect drift between the client-visible PREVIEW and the Core catalog.
 *
 * This is not price homologation: source CRM-REV-02 still has open decisions.
 * The static HTML must never be an authoritative payment or entitlement source.
 */
final class CommercialPageCatalogParityTest extends TestCase
{
    private static function page(): DOMXPath
    {
        $path = dirname(__DIR__, 2).'/prototypes/site-comercial-crm/index.html';
        self::assertFileExists($path);

        $markup = file_get_contents($path);
        self::assertIsString($markup);
        self::assertLessThan(120_000, strlen($markup));

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            self::assertTrue(
                $document->loadHTML('<?xml encoding="UTF-8">'.$markup, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING),
                'Página HTML comercial deve ser legível',
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }

    /** @return list<DOMElement> */
    private static function nodes(DOMXPath $xpath, string $expression): array
    {
        $matches = $xpath->query($expression);
        self::assertInstanceOf(DOMNodeList::class, $matches);
        $out = [];

        foreach ($matches as $node) {
            self::assertInstanceOf(DOMElement::class, $node);
            $out[] = $node;
        }

        return $out;
    }

    private static function value(DOMXPath $xpath, DOMNode $scope, string $expression): string
    {
        $matches = $xpath->query($expression, $scope);
        self::assertInstanceOf(DOMNodeList::class, $matches);
        self::assertSame(1, $matches->length, 'Elemento único obrigatório: '.$expression);

        return trim((string) $matches->item(0)?->textContent);
    }

    private static function brlCents(string $display): int
    {
        $match = [];
        self::assertSame(
            1,
            preg_match('/^R\$\s*([0-9]{1,3}(?:\.[0-9]{3})*)(?:,([0-9]{2}))?(?:\s*\/mês)?$/u', trim($display), $match),
            'Mensalidade deve ter formato explícito em reais: '.$display,
        );

        $whole = (int) str_replace('.', '', $match[1]);
        $cents = isset($match[2]) ? (int) $match[2] : 0;

        return $whole * 100 + $cents;
    }

    public function test_all_four_cards_use_reference_catalog_prices_and_capacities(): void
    {
        $xpath = self::page();
        $plans = CommercialPlanReferenceCatalog::plans();
        $cards = self::nodes(
            $xpath,
            "//section[@id='planos']//div[contains(concat(' ',normalize-space(@class),' '),' pricing ')]/article",
        );
        self::assertCount(count($plans), $cards);

        foreach ($cards as $i => $card) {
            $expected = $plans[$i];
            self::assertSame($expected['name'], self::value($xpath, $card, './h3'));
            self::assertSame(
                $expected['monthly_cents'],
                self::brlCents(self::value($xpath, $card, "./div[contains(concat(' ',normalize-space(@class),' '),' price ')]/text()[1]")),
                'Preço exposto divergente do Core: '.$expected['code'],
            );

            $facts = self::value($xpath, $card, "./div[contains(concat(' ',normalize-space(@class),' '),' facts ')]");
            self::assertStringContainsString(
                (string) $expected['seats_reference'].' usuário',
                $facts,
                'Assentos visuais não coincidem: '.$expected['code'],
            );
            self::assertStringContainsString(
                $expected['storage_label'].' de anexos',
                $facts,
                'Franquia visual não coincide: '.$expected['code'],
            );
            self::assertCount(
                1,
                self::nodes($xpath, ".//button[@data-action='Plano ".$expected['name']."']", $card),
            );
        }
    }

    public function test_comparison_table_has_exactly_the_same_reference_values(): void
    {
        $xpath = self::page();
        $plans = CommercialPlanReferenceCatalog::plans();
        $tables = self::nodes($xpath, "//section[@id='planos']//table[contains(@class,'compare-table')]");
        self::assertCount(1, $tables);

        $table = $tables[0];
        $headers = self::nodes($xpath, './thead/tr/th', $table);
        self::assertCount(1 + count($plans), $headers);

        foreach ($plans as $i => $plan) {
            self::assertSame($plan['name'], trim($headers[$i + 1]->textContent));
        }

        $rows = self::nodes($xpath, './tbody/tr', $table);
        self::assertCount(4, $rows);
        $expectedLabels = [
            'Mensalidade prevista',
            'Usuários de referência',
            'Anexos e arquivos',
            'Recursos e limites',
        ];

        foreach ($rows as $i => $row) {
            self::assertSame($expectedLabels[$i], self::value($xpath, $row, './th'));
        }

        $prices = self::nodes($xpath, './td', $rows[0]);
        $seats = self::nodes($xpath, './td', $rows[1]);
        $storage = self::nodes($xpath, './td', $rows[2]);
        self::assertCount(4, $prices);
        self::assertCount(4, $seats);
        self::assertCount(4, $storage);

        foreach ($plans as $i => $plan) {
            self::assertSame($plan['monthly_cents'], self::brlCents($prices[$i]->textContent));
            self::assertSame((string) $plan['seats_reference'], trim($seats[$i]->textContent));
            self::assertSame($plan['storage_label'], trim($storage[$i]->textContent));
        }
    }

    public function test_commercial_funnel_is_explicitly_non_operational(): void
    {
        $xpath = self::page();
        $plans = CommercialPlanReferenceCatalog::plans();

        self::assertCount(0, self::nodes($xpath, '//form'));
        self::assertCount(0, self::nodes($xpath, '//input'));
        self::assertCount(0, self::nodes($xpath, '//iframe'));
        self::assertCount(0, self::nodes($xpath, '//a[starts-with(@href,"http")]'));
        self::assertCount(1, self::nodes($xpath, '//meta[@name="robots" and @content="noindex,nofollow"]'));
        self::assertCount(1, self::nodes($xpath, "//a[@href='./fluxo-demo/index.html']"));

        foreach ($plans as $plan) {
            self::assertFalse($plan['price_homologated']);
            self::assertFalse($plan['entitlements_homologated']);
            self::assertFalse($plan['can_checkout']);
        }

        $source = file_get_contents(dirname(__DIR__, 2).'/prototypes/site-comercial-crm/index.html');
        self::assertIsString($source);
        self::assertStringContainsString('ainda sujeitos à homologação', $source);
        self::assertStringContainsString('Nenhum botão desta página cria assinatura ou efetua cobrança.', $source);
        self::assertStringContainsString("form-action 'none'", $source);
        self::assertStringContainsString("connect-src 'none'", $source);
    }
}
