<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticProposalValidityPreview;
use PHPUnit\Framework\TestCase;

final class OfflineProposalValidityParityTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-120-proposal-validity/index.html',
        );
        self::assertIsString($html);
        return $html;
    }

    /** @return list<array<string,mixed>> */
    private static function cases(): array
    {
        self::assertSame(1, preg_match(
            '/const SCENARIOS=Object\.freeze\((\[[\s\S]*?\])\);/',
            self::html(), $matches,
        ));
        $cases = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);
        self::assertTrue(array_is_list($cases));
        self::assertCount(5, $cases);
        return $cases;
    }

    public function test_five_visible_validity_scenarios_match_core_php_at_exact_seconds(): void
    {
        $seen = [];
        foreach (self::cases() as $case) {
            self::assertSame(['id', 'issued', 'expires', 'observed', 'expected'], array_keys($case));
            self::assertContains($case['id'], ['before', 'start', 'last', 'expired', 'after']);
            self::assertArrayNotHasKey($case['id'], $seen);
            $seen[$case['id']] = true;
            $r = (new SyntheticProposalValidityPreview(
                $case['issued'], $case['expires'], $case['observed'],
            ))->readout();

            self::assertSame([
                'phase' => $r['phase'],
                'seconds_until_issue' => $r['seconds_until_issue'],
                'seconds_until_expiry' => $r['seconds_until_expiry'],
            ], $case['expected']);
            self::assertSame(7 * 86400, $r['validity_seconds']);
            self::assertSame(1793534400, $r['issued_at_epoch']);
            self::assertSame(1794139200, $r['expires_at_epoch']);
            self::assertTrue($r['informational_only']);
            foreach ([
                'validity_policy_verified', 'link_authorized', 'acceptance_verified',
                'authorized', 'can_send', 'can_accept', 'can_renew',
                'can_sign', 'can_charge', 'can_persist',
            ] as $flag) {
                self::assertFalse($r[$flag]);
            }
        }
        self::assertSame(['before', 'start', 'last', 'expired', 'after'], array_keys($seen));
    }

    public function test_page_has_no_real_document_data_network_or_acceptance_capability(): void
    {
        $html = self::html();
        self::assertLessThanOrEqual(12000, strlen($html));
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(5, preg_match_all(
            '/<button type="button" data-case="(?:before|start|last|expired|after)" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            'PROTÓTIPO — DADOS FICTÍCIOS.', "connect-src 'none'",
            "form-action 'none'", 'aria-live="polite"',
            'button:focus-visible', 'href="../f3-111-proposal-versions/index.html"',
            'Date.parse(item.issued)', 'seconds_until_expiry',
            "put('rights','Enviar: NÃO · Aceitar: NÃO · Renovar: NÃO · Assinar: NÃO · Cobrar: NÃO · Gravar: NÃO');",
            '@media(max-width:390px)',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'localStorage', 'sessionStorage', 'indexedDB', 'Date.now',
            '.innerHTML', '.outerHTML', 'insertAdjacentHTML',
            '<form', '<input', '<textarea', '<iframe', '<img',
            '<script src=', '<link ', 'onload=', 'onclick=', 'download=',
            'http://', 'https://', 'url(', '@import',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        $json = json_encode(self::cases(), JSON_THROW_ON_ERROR);
        foreach (['tenant', 'customer', 'email', 'phone', 'proposal_id', 'signature', 'payment'] as $private) {
            self::assertStringNotContainsString('"'.$private.'"', $json);
        }
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/f3-111-proposal-versions/index.html');
    }
}
