<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticCsvImportPreflight;
use App\Core\Portability\SyntheticCsvReviewReportV1;
use App\Core\Portability\SyntheticCsvReviewReportV1JsonEncoder;
use App\Core\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

final class OfflineCsvReviewV1ParityTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const OTHER = '22222222-2222-4222-8222-222222222222';
    private const PRINCIPAL = '33333333-3333-4333-8333-333333333333';
    private const CORRELATION = '44444444-4444-4444-8444-444444444444';

    private static function html(): string
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-106-csv-review-v1/index.html');
        self::assertIsString($source);

        return $source;
    }

    /** @return list<array<string,mixed>> */
    private static function scenarios(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(), $matches,
        ));
        $cases = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(3, $cases);

        return $cases;
    }

    public function test_three_offline_reports_match_real_php_v1_build_and_json_encoder(): void
    {
        $cases = self::scenarios();
        $preflight = new SyntheticCsvImportPreflight();
        $context = new TenantContext(self::TENANT, self::PRINCIPAL, self::CORRELATION);
        $scenarios = [
            [
                "stage;name;note\nSYNTHETIC_A;SYNTHETIC_B;safe\n",
                self::TENANT, null,
            ],
            [
                "stage;name;note\n;SYNTHETIC_A;\"=1+1\"\n;SYNTHETIC_A;\"=1+1\"\n",
                self::TENANT, ['name', 'stage', 'missing'],
            ],
            [
                "stage;name;note\n;SYNTHETIC_A;\"=1+1\"\n;SYNTHETIC_A;\"=1+1\"\n",
                self::OTHER, ['name', 'stage', 'missing'],
            ],
        ];

        foreach ($scenarios as $i => [$csv, $claimedTenant, $required]) {
            $diagnostic = $preflight->inspect(
                $csv, ['stage', 'name', 'note'],
                $context, $claimedTenant, ['stage', 'name'], ';', $required,
            );
            $php = (new SyntheticCsvReviewReportV1())->build($diagnostic);
            self::assertSame(['clean', 'warnings', 'scope'][$i], $cases[$i]['id']);
            self::assertSame($php, $cases[$i]['report']);
            self::assertSame(
                json_encode($php, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                (new SyntheticCsvReviewReportV1JsonEncoder())->encode($diagnostic),
            );
            foreach (['allowed', 'can_import', 'can_persist_lead', 'can_enable_import_route'] as $flag) {
                self::assertFalse($php[$flag]);
            }
        }
        self::assertSame('SCOPE_NOT_VERIFIED', $cases[0]['report']['denial_reason']);
        self::assertSame('SCOPE_NOT_VERIFIED', $cases[1]['report']['denial_reason']);
        self::assertSame('CONTRACT_INVALID', $cases[2]['report']['denial_reason']);
    }

    public function test_offline_reports_never_include_contact_fields_cell_text_or_unverified_identity(): void
    {
        $html = self::html();
        $json = json_encode(self::scenarios(), JSON_THROW_ON_ERROR);
        self::assertLessThanOrEqual(16000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(3, preg_match_all(
            '/<button type="button" data-case="(?:clean|warnings|scope)" aria-pressed="(?:true|false)">/',
            $html,
        ));

        foreach ([
            'SYNTHETIC_A', 'SYNTHETIC_B', '=1+1',
            '"stage"', '"name"', '"note"', '"missing"',
            self::TENANT, self::OTHER, self::PRINCIPAL, self::CORRELATION,
            'private@example.test',
        ] as $secret) {
            self::assertStringNotContainsString($secret, $json);
        }
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.',
            "connect-src 'none'", "form-action 'none'",
            'aria-live="polite"', 'a:focus-visible',
            'button:focus-visible', 'get(\'alerts\').replaceChildren();',
            'a.textContent=label;b.textContent=position',
            'Nenhuma empresa foi autenticada.', 'Importação proibida',
            '@media(max-width:390px)',
            'href="../f3-72-csv-checklist/index.html"',
        ] as $required) {
            // The action never includes an imported CSV row or user-provided label.
            self::assertStringContainsString($required, $html);
        }

        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', 'indexedDB', 'Date.now',
            '.innerHTML', '.outerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe', '<img', '<script src=',
            '<link ', 'http://', 'https://', 'download=', 'onload=', 'onclick=',
            'url(', '@import',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }

        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/f3-72-csv-checklist/index.html');
    }
}
