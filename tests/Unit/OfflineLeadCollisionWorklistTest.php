<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadCollisionReviewWorklist;
use PHPUnit\Framework\TestCase;

final class OfflineLeadCollisionWorklistTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-197-lead-collision-worklist/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    public function test_offline_fixture_has_eight_pairs_five_filters_and_no_customer_capture(): void
    {
        $html = self::html();
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(8, substr_count($html, 'submission_relation:'));
        self::assertSame(5, substr_count($html, '<button type="button" data-reason='));
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', $html);
        foreach ([
            "default-src 'none'", "connect-src 'none'",
            "form-action 'none'", "frame-src 'none'", "img-src data:",
            "object-src 'none'", "base-uri 'none'",
            'referrer" content="no-referrer"',
            'min-height:44px', '@media(max-width:400px)',
            '@media(prefers-reduced-motion:reduce)',
            'replaceChildren()', 'textContent=',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            'https://', 'http://', '@import', 'url(', '<form', '<input',
            '<textarea', 'fetch(', 'XMLHttpRequest', 'WebSocket',
            'localStorage', 'sessionStorage', 'indexedDB',
            '<iframe', '.innerHTML', 'eval(', 'document.cookie',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_demo_order_matches_core_php_priority_and_cannot_authorize_merge(): void
    {
        $fixtures = [
            [0,1,'same','unavailable','unavailable'],
            [0,2,'different','same','unavailable'],
            [1,2,'same','different','unavailable'],
            [1,3,'different','different','different'],
            [2,4,'different','unavailable','same'],
            [2,3,'same','same','different'],
            [3,4,'same','unavailable','unavailable'],
            [3,5,'different','unavailable','unavailable'],
        ];
        $pairs = array_map(static fn (array $p): array => [
            'left' => $p[0], 'right' => $p[1],
            'submission_relation' => $p[2],
            'email_relation' => $p[3],
            'phone_relation' => $p[4],
        ], $fixtures);

        $result = (new SyntheticLeadCollisionReviewWorklist())->inspect(6, $pairs, 3, 0);
        self::assertSame(8, $result['pair_count']);
        self::assertSame(4, $result['priority_review_count']);
        self::assertSame([2,5,1],
            array_column($result['page_items'], 'pair_position'));
        self::assertSame(3, $result['page_count']);
        self::assertFalse($result['can_merge']);
        self::assertFalse($result['can_reconcile']);
        self::assertFalse($result['can_persist']);
        self::assertStringContainsString(
            "const ORDER=Object.freeze({CONFLICTING_RETRY_CLAIM:0,POSSIBLE_PERSON_MATCH:1,RETRY_CLAIM_UNVERIFIED:2,INSUFFICIENT_EVIDENCE:3})",
            self::html(),
        );
    }

    public function test_uses_byte_identical_original_imagine_brand_png(): void
    {
        $html = self::html();
        self::assertSame(1, preg_match(
            '/<img class="brand-logo" alt="Imagine" width="160" height="44" src="data:image\/png;base64,([A-Za-z0-9+\/=]+)">/',
            $html, $match,
        ));
        $image = base64_decode($match[1], true);
        self::assertIsString($image);
        self::assertSame('678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77',
            sha1('blob '.strlen($image)."\0".$image));
    }

    public function test_demo_navigation_keeps_state_without_calls_or_untrusted_markup(): void
    {
        $html = self::html();
        self::assertStringContainsString('b.dataset.reason', $html);
        self::assertStringContainsString('page=0;render();', $html);
        self::assertStringContainsString('if(!next.disabled){page+=1;render();}', $html);
        self::assertStringContainsString("previous.disabled=page===0;", $html);
        self::assertStringContainsString("next.disabled=pages===0||page>=pages-1;", $html);
        self::assertStringContainsString('role="status" aria-live="polite"', $html);
    }
}
