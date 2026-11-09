<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * SEC-UX-03: regression guard for HTML/JS sinks in the synthetic fixture.
 * Static checks only; not a security audit or proof of runtime authorization.
 */
final class OfflinePrototypeDomSinkGuardTest extends TestCase
{
    public function test_offline_fixture_has_no_inline_event_handlers_or_dynamic_html_sinks(): void
    {
        $html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
        self::assertIsString($html);

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//*') as $node) {
            self::assertInstanceOf(DOMElement::class, $node);
            foreach ($node->attributes as $attribute) {
                self::assertDoesNotMatchRegularExpression(
                    '/^on[a-z]+$/i',
                    $attribute->nodeName,
                    'Inline event handlers must not be introduced in the offline fixture'
                );
            }
        }

        $scripts = $xpath->query('//script');
        self::assertSame(1, $scripts->length, 'Only the existing offline script is allowed');
        $script = $scripts->item(0);
        self::assertInstanceOf(DOMElement::class, $script);
        self::assertFalse($script->hasAttribute('src'));

        $javascript = $script->textContent;
        foreach ([
            '/\\beval\\s*\\(/i',
            '/\\bFunction\\s*\\(/',
            '/\\bdocument\\s*\\.\\s*write(?:ln)?\\s*\\(/i',
            '/\\b(?:innerHTML|outerHTML)\\b/',
            '/\\binsertAdjacentHTML\\s*\\(/',
            '/\\b(?:setTimeout|setInterval)\\s*\\(/',
            '/\\b(?:fetch|WebSocket|XMLHttpRequest|EventSource|sendBeacon)\\b/',
            '/\\b(?:localStorage|sessionStorage|indexedDB)\\b/',
            '/\\b(?:postMessage|window\\s*\\.\\s*open)\\s*\\(/',
        ] as $forbiddenPattern) {
            self::assertDoesNotMatchRegularExpression($forbiddenPattern, $javascript);
        }

        self::assertStringContainsString('replaceChildren(', $javascript);
        self::assertStringContainsString('textContent', $javascript);
        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }
}
