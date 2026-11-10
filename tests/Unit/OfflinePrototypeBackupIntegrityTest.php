<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\SyntheticBackupIntegrityInspector;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-REC-LAB-04: static HTML only. The PHP component is a comparison of
 * untrusted fake inputs, and Chromium CI verifies client-side interactions.
 */
final class OfflinePrototypeBackupIntegrityTest extends TestCase
{
    public function test_three_accessible_backup_scenarios_are_only_inside_import_demo(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($doc->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($doc);
        self::assertSame(1, $xpath->query('//section[@id="screen-import"]//div[@data-backup-demo]')->length);
        $buttons = $xpath->query('//section[@data-backup-demo]//button[@data-backup-check]');
        self::assertSame(3, $buttons->length);
        $modes = [];
        $pressed = 0;
        foreach ($buttons as $button) {
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'));
            $modes[] = $button->getAttribute('data-backup-check');
            if ($button->getAttribute('aria-pressed') === 'true') {
                ++$pressed;
            }
        }
        self::assertSame(['consistent', 'mismatch', 'forged'], $modes);
        self::assertSame(1, $pressed);
        self::assertSame('true', $buttons->item(0)->getAttribute('aria-pressed'));
        self::assertSame(
            1,
            $xpath->query('//section[@data-backup-demo]//div[@id="backup-integrity-preview" and @role="status" and @aria-live="polite" and @aria-atomic="true"]')->length,
        );
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }

    public function test_visual_scenarios_are_grounded_in_non_authorizing_php_outcomes(): void
    {
        $inspector = new SyntheticBackupIntegrityInspector();
        $content = 'invented backup fixture';
        $manifest = [
            'format' => 'synthetic-blob-v1',
            'declared_bytes' => strlen($content),
            'recorded_at_utc' => '2026-11-10T03:00:00Z',
            'sha256' => hash('sha256', $content),
        ];
        $consistent = $inspector->inspect($manifest, $content);
        $mismatch = $inspector->inspect($manifest, 'Invented backup fixture');
        $forgedContent = 'attacker-changed-demo';
        $forged = $inspector->inspect(array_replace($manifest, [
            'declared_bytes' => strlen($forgedContent),
            'sha256' => hash('sha256', $forgedContent),
        ]), $forgedContent);

        self::assertSame('SELF_CLAIM_MATCH_UNTRUSTED', $consistent->reasonCode);
        self::assertSame('SELF_CLAIM_MISMATCH', $mismatch->reasonCode);
        self::assertSame('SELF_CLAIM_MATCH_UNTRUSTED', $forged->reasonCode);
        foreach ([$consistent, $mismatch, $forged] as $assessment) {
            self::assertFalse($assessment->canRestore());
            self::assertFalse($assessment->canAcceptAsVerifiedBackup());
            self::assertFalse($assessment->canDeleteOriginal());
        }

        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        self::assertStringContainsString($consistent->reasonCode, $html);
        self::assertStringContainsString($mismatch->reasonCode, $html);
        self::assertStringContainsString('Nenhum backup foi criado ou restaurado.', $html);
    }

    public function test_view_remains_text_only_without_a_real_backup_or_restore_channel(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);
        foreach ([
            'function showBackupIntegrityCase(code)',
            "showBackupIntegrityCase('consistent');",
            'backupIntegrityPreview.replaceChildren();',
            "const inner=document.createElement('div');",
            'Nenhuma operação foi autorizada.',
            "connect-src 'none'",
            "form-action 'none'",
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach (['fetch(', 'XMLHttpRequest', 'WebSocket', 'localStorage', 'sessionStorage', '<form', '<input', '<textarea'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }
}
