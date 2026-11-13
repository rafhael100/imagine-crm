<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** F3-201 — visual-only IN-118 rehearsal; not an authorized backup system. */
final class OfflinePortabilityCenterTest extends TestCase
{
    private static function html(): string
    {
        $html = file_get_contents(
            dirname(__DIR__, 2).'/prototypes/f3-201-portability-center/index.html',
        );
        self::assertIsString($html);

        return $html;
    }

    public function test_all_seven_intents_and_four_plans_remain_local_buttons(): void
    {
        $html = self::html();
        self::assertSame(4, substr_count($html, '<button type="button" data-plan='));
        self::assertSame(7, substr_count($html, '<button type="button" data-operation='));
        self::assertSame(3, substr_count($html, '<button type="button" data-target='));
        self::assertSame(1, substr_count($html, 'id="review-example"'));
        foreach ([
            'Exportar CSV', 'Exportar XLSX', 'Importar CSV',
            'Importar XLSX', 'Backup completo', 'Restaurar backup',
            'Migrar entre contas', 'Básico', 'Essencial', 'Profissional',
            'Empresarial', 'PROTÓTIPO — DADOS FICTÍCIOS.',
            'nenhuma operação autorizada', 'não é bloqueado automaticamente',
        ] as $label) {
            self::assertStringContainsString($label, $html);
        }
    }

    public function test_only_the_exact_original_imagine_logo_is_embedded(): void
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
        self::assertSame(1, substr_count($html, '<img'));
    }

    public function test_strict_offline_boundaries_no_upload_download_or_customer_input(): void
    {
        $html = self::html();
        foreach ([
            "default-src 'none'", "connect-src 'none'", "font-src 'none'",
            "form-action 'none'", "frame-src 'none'",
            "object-src 'none'", "base-uri 'none'", "img-src data:",
            'referrer" content="no-referrer"', 'min-height:44px',
            '@media(max-width:440px)', '@media(max-width:800px)',
            '@media(prefers-reduced-motion:reduce)',
            'replaceChildren()', '.textContent=',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        foreach ([
            '<form', '<input', '<textarea', '<select', 'type="file"',
            'http://', 'https://', '@import', 'url(', '<iframe',
            '<link', 'WebSocket', 'fetch(', 'XMLHttpRequest',
            'navigator.sendBeacon', 'localStorage', 'sessionStorage',
            'indexedDB', 'document.cookie', '.innerHTML', '.outerHTML',
            'download=', 'window.open(', 'onload=', 'onclick=', 'eval(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_review_control_does_not_create_runtime_or_approved_action(): void
    {
        $html = self::html();
        self::assertStringContainsString(
            "b.dataset.operation===operation", $html,
        );
        self::assertStringContainsString(
            "el('warning-list').replaceChildren();", $html,
        );
        self::assertStringContainsString(
            "Nenhum arquivo foi lido, gravado ou enviado.", $html,
        );
        self::assertStringContainsString(
            "A conta destino já tem registros: não mesclar automaticamente.", $html,
        );
        self::assertStringContainsString(
            "Segredos e credenciais não podem ser transferidos automaticamente.", $html,
        );
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(1, substr_count($html, 'id="operation-status"'));
    }
}
