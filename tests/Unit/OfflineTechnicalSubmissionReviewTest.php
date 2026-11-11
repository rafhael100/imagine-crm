<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticSubmissionBindingBatchInspector;
use PHPUnit\Framework\TestCase;

final class OfflineTechnicalSubmissionReviewTest extends TestCase
{
    private const ORG_A = '11111111-1111-4111-8111-111111111111';
    private const ORG_B = '22222222-2222-4222-8222-222222222222';
    private const SUB_A = '33333333-3333-4333-8333-333333333333';
    private const SUB_B = '44444444-4444-4444-8444-444444444444';
    private const BIND_A = '55555555-5555-4555-8555-555555555555';
    private const BIND_B = '66666666-6666-4666-8666-666666666666';

    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-81-submission-review/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    /** @return array{organization_reference:string,submission_reference:string,binding_reference:string} */
    private static function claim(string $org, string $sub, string $binding): array
    {
        return [
            'organization_reference' => $org,
            'submission_reference' => $sub,
            'binding_reference' => $binding,
        ];
    }

    /** @return list<list<array<string,string>>> */
    private static function fixtures(): array
    {
        return [
            [
                self::claim(self::ORG_A, self::SUB_A, self::BIND_A),
                self::claim(self::ORG_A, self::SUB_B, self::BIND_A),
            ],
            [
                self::claim(self::ORG_A, self::SUB_A, self::BIND_A),
                self::claim(strtoupper(self::ORG_A), strtoupper(self::SUB_A), strtoupper(self::BIND_A)),
            ],
            [
                self::claim(self::ORG_A, self::SUB_A, self::BIND_A),
                self::claim(self::ORG_A, self::SUB_A, self::BIND_B),
            ],
            [
                self::claim(self::ORG_A, self::SUB_A, self::BIND_A),
                self::claim(self::ORG_B, self::SUB_A, self::BIND_A),
            ],
        ];
    }

    /** @return list<array<string,mixed>> */
    private static function browserScenarios(): array
    {
        $html = self::html();
        self::assertSame(1, preg_match(
            '/const SCENARIOS = Object\.freeze\((\[[\s\S]*?\])\);/',
            $html,
            $matches,
        ));
        $reports = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(4, $reports);

        return $reports;
    }

    public function test_all_four_offline_reports_match_actual_bounded_three_key_php_inspector(): void
    {
        $displayed = self::browserScenarios();
        $core = new SyntheticSubmissionBindingBatchInspector();
        foreach (self::fixtures() as $index => $claims) {
            $result = $core->inspect($claims);
            self::assertSame([
                'claims' => $result->claimsInspected,
                'same' => $result->sameClaimPositions,
                'binding' => $result->bindingConflictPositions,
                'organization' => $result->organizationConflictPositions,
                'allowed' => $result->canPersistLead(),
            ], $displayed[$index], 'PHP/HTML parity case '.$index);

            self::assertFalse($result->canTreatAsAuthorizedRetry());
            self::assertFalse($result->canAcknowledgeDelivery());
            self::assertFalse($result->canMergeContacts());
        }
    }

    public function test_browser_metadata_never_exposes_raw_uuids_contacts_or_business_fields(): void
    {
        $json = json_encode(self::browserScenarios(), JSON_THROW_ON_ERROR);
        foreach ([self::ORG_A, self::ORG_B, self::SUB_A, self::SUB_B,
            self::BIND_A, self::BIND_B, 'PRIVATE_PERSON',
            'private@example.test', 'organization_reference', 'binding_reference'] as $raw) {
            self::assertStringNotContainsString($raw, $json);
        }
        self::assertStringContainsString('PROTÓTIPO — DADOS FICTÍCIOS.', self::html());
        self::assertStringContainsString('Entrega não autorizada', self::html());
        self::assertStringContainsString('report.allowed!==false', self::html());
    }

    public function test_ui_is_keyboard_native_offline_and_safely_updates_only_text_nodes(): void
    {
        $html = self::html();
        self::assertSame(4, preg_match_all(
            '/<button type="button" data-case="[0-3]" aria-pressed="(?:true|false)">/',
            $html,
        ));
        foreach ([
            '<main id="conteudo">', 'id="review-status"',
            'aria-live="polite"', 'id="finding-rows"',
            'id="count-claims"', 'id="count-same"',
            'id="count-binding"', 'id="count-org"',
            "byId('finding-rows').replaceChildren();",
            "category.textContent=label;",
            "detail.textContent=positions;",
            "connect-src 'none'", "form-action 'none'",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket',
            'localStorage', 'sessionStorage', 'indexedDB', 'document.cookie',
            '<form', '<input', '<textarea', '<img', '<iframe', '<link',
            '.innerHTML', '.outerHTML', 'insertAdjacentHTML', 'document.write',
            'onload=', 'onclick=', 'download=', '<script src=', '@import', 'url('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        preg_match_all('/\bhref="([^"]+)"/', $html, $matches);
        self::assertSame(['#conteudo', '../index.html'], $matches[1]);
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/index.html');
        self::assertStringContainsString('@media(max-width:370px)', $html);
    }
}
