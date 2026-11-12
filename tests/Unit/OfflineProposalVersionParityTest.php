<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticProposalVersionReview;
use PHPUnit\Framework\TestCase;

final class OfflineProposalVersionParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-111-proposal-versions/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    /** @return list<array<string,mixed>> */
    private static function scenarios(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(),
            $match,
        ));
        $cases = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(5, $cases);

        return $cases;
    }

    public function test_five_offline_version_histories_reconcile_exactly_with_php_core(): void
    {
        $model = new SyntheticProposalVersionReview();
        $ids = [];
        foreach (self::scenarios() as $case) {
            self::assertSame(['id', 'versions', 'expected'], array_keys($case));
            self::assertContains($case['id'], ['draft', 'viewed', 'revision', 'accepted', 'empty']);
            self::assertArrayNotHasKey($case['id'], $ids);
            $ids[$case['id']] = true;
            $report = $model->inspect($case['versions']);
            self::assertSame([
                'latest_version' => $report['latest_version'],
                'latest_claimed_status' => $report['latest_claimed_status'],
                'prior_claimed_accepted_versions' => $report['prior_claimed_accepted_versions'],
                'viewed_without_claimed_acceptance' => $report['viewed_without_claimed_acceptance'],
            ], $case['expected']);
            self::assertSame(count($case['versions']), $report['versions_seen']);
            self::assertFalse($report['authority_verified']);
            self::assertFalse($report['viewed_equals_acceptance']);
            self::assertFalse($report['prior_acceptance_applies_to_latest']);
            foreach (['can_send', 'can_accept', 'can_sign', 'can_charge', 'can_persist'] as $ability) {
                self::assertFalse($report[$ability]);
            }
        }

        self::assertSame(['draft', 'viewed', 'revision', 'accepted', 'empty'], array_keys($ids));
        self::assertSame('draft', $model->inspect([
            ['version' => 1, 'status' => 'accepted', 'viewed' => true],
            ['version' => 2, 'status' => 'draft', 'viewed' => false],
        ])['latest_claimed_status']);
    }

    public function test_local_review_is_privacy_safe_and_never_issues_or_accepts_documents(): void
    {
        $html = self::html();
        self::assertLessThanOrEqual(14000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(5, preg_match_all(
            '/<button type="button" data-case="(?:draft|viewed|revision|accepted|empty)" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.', "connect-src 'none'",
            "form-action 'none'", 'aria-live="polite"',
            'button:focus-visible', 'href="../f3-109-sales-quote-subtotal/index.html"',
            "table.replaceChildren();", 'cell.textContent=value',
            'prior_claimed_accepted_versions',
            'viewed_without_claimed_acceptance',
            "put('rights','Enviar: NÃO · Aceitar: NÃO · Assinar: NÃO · Cobrar: NÃO · Gravar: NÃO');",
            '@media(max-width:390px)',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', 'indexedDB', 'Date.now',
            '.innerHTML', '.outerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe', '<img', '<script src=',
            '<link ', 'onload=', 'onclick=', 'download=', 'http://', 'https://',
            'url(', '@import',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/f3-109-sales-quote-subtotal/index.html');
        $json = json_encode(self::scenarios(), JSON_THROW_ON_ERROR);
        foreach (['tenant', 'cnpj', 'email', 'phone', 'customer', 'proposal_id', 'signature', 'payment'] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
    }
}
