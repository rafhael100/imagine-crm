<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

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
            'id="step-two"', 'id="step-three"', 'id="step-four"', 'id="finish"',
            'id="code"', 'inputmode="numeric"', 'maxlength="6"',
            'PROTÓTIPO — DADOS FICTÍCIOS',
            'CNPJ alfanumérico', 'CNPJ numérico', 'Simular entrega',
            'WhatsApp', 'E-mail', '15 minutos', '012345',
            'id="activate-trial"', 'id="trial-duplicate"', 'id="trial-expire"',
            'id="trial-status"', 'id="step-d"', '14 dias',
        ] as $required) {
            self::assertStringContainsString($required, $html);
        }
        self::assertSame(1, substr_count($html, '</body>'));
        self::assertSame(1, substr_count($html, '</html>'));
        self::assertStringContainsString('type="button" disabled id="finish"', $html);
    }

    public function test_seven_offline_trial_limits_are_equal_to_real_core_cli_fixture(): void
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2).'/scripts/ci/emit-composite-signup-trial-lab.php',
        ]);
        $process->setTimeout(30);
        $process->run();
        self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $report = json_decode(trim($process->getOutput()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('REVIEW_ONLY_DENIED', $report['status']);
        self::assertSame(14, $report['trial_days_illustrative']);
        $html = file_get_contents(self::root().'index.html');
        self::assertIsString($html);
        self::assertCount(7, $report['trial_quota_illustrative_limits']);
        self::assertSame(7, substr_count($html, 'class="quota"'));
        foreach ($report['trial_quota_illustrative_limits'] as $meter => $limit) {
            self::assertStringContainsString(
                'data-meter="'.$meter.'" data-limit="'.$limit.'"',
                $html,
            );
        }
        self::assertStringContainsString('type="button" disabled id="activate-trial"', $html);
        self::assertStringContainsString("location.hash==='#previsualizar-trial'", $html);
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
