<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OfflineSignupOtpImagineUxPrototypeTest extends TestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 2).'/prototypes/f3-240-signup-otp/';
    }

    public function test_original_imagine_logo_is_byte_identical_to_portal_node_source(): void
    {
        $logo = file_get_contents(self::root().'assets/logo-imagine.png');
        self::assertIsString($logo);
        self::assertStringStartsWith("\x89PNG\r\n\x1A\n", $logo);
        $gitBlobSha = hash('sha1', 'blob '.strlen($logo)."\0".$logo);
        self::assertSame('678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77', $gitBlobSha);
    }

    public function test_visual_identity_and_responsive_keyboard_access_are_explicit(): void
    {
        $html = file_get_contents(self::root().'index.html');
        self::assertIsString($html);
        foreach ([
            'src="assets/logo-imagine.png"', 'alt="Logomarca original da Imagine"',
            '#f9941d', '#171b19', '#f7f7f8',
            '@media(max-width:880px)', '@media(max-width:440px)',
            '.skip:focus', 'button:focus-visible', 'input:focus-visible',
            'href="#conteudo"', 'role="status" aria-live="polite"',
            'id="step-two"', 'id="step-three"', 'id="finish"',
            'id="code"', 'inputmode="numeric"', 'maxlength="6"',
            'PROTÓTIPO — DADOS FICTÍCIOS',
            'CNPJ alfanumérico', 'CNPJ numérico', 'Simular entrega',
            'WhatsApp', 'E-mail', '15 minutos', '012345',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(1, substr_count($html, '</html>'));
        self::assertStringContainsString('type="button" disabled id="finish"', $html);
    }

    public function test_offline_csp_and_no_transport_storage_or_real_signup_are_enforced(): void
    {
        $html = file_get_contents(self::root().'index.html');
        self::assertIsString($html);
        foreach ([
            "default-src 'none'", "connect-src 'none'", "form-action 'none'",
            "script-src 'unsafe-inline'", "img-src 'self'",
            "frame-src 'none'", "object-src 'none'", "base-uri 'none'",
        ] as $rule) {
            self::assertStringContainsString($rule, $html);
        }
        foreach ([
            '<form', 'fetch(', 'XMLHttpRequest', 'WebSocket', 'EventSource',
            'navigator.sendBeacon', 'localStorage', 'sessionStorage', 'indexedDB',
            'window.open(', 'document.cookie', 'eval(', 'new Function(',
            'http://', 'https://', 'onload=', 'onclick=', '.innerHTML',
            'mailto:', 'tel:', 'action=', 'iframe', '<link rel="stylesheet"',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $html);
        }
        self::assertSame(0, substr_count($html, '<form'));
    }
}
