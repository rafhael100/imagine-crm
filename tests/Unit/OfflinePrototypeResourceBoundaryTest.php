<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * SEC-UX-01: fail-closed resource boundary for the nine-screen offline fixture.
 * This is a static regression check, not a browser security audit.
 */
final class OfflinePrototypeResourceBoundaryTest extends TestCase
{
    public function test_fixture_does_not_load_external_resources_or_submit_forms(): void
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
        $policies = $xpath->query('//meta[translate(@http-equiv, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="content-security-policy"]');
        self::assertNotFalse($policies);
        self::assertSame(1, $policies->length);

        $policy = $policies->item(0)->getAttribute('content');
        foreach ([
            "default-src 'none'",
            "connect-src 'none'",
            "form-action 'none'",
            "object-src 'none'",
            "base-uri 'none'",
            "font-src 'none'",
            "img-src 'self' data:",
        ] as $directive) {
            self::assertStringContainsString($directive, $policy);
        }

        foreach (['form', 'iframe', 'object', 'embed', 'base', 'link', 'video', 'audio', 'source'] as $tag) {
            self::assertSame(0, $document->getElementsByTagName($tag)->length, $tag.' must remain absent');
        }

        // One reviewed local logo is allowed; all other resources stay denied.
        $images = $xpath->query('//img');
        self::assertNotFalse($images);
        self::assertSame(1, $images->length);
        $logo = $images->item(0);
        self::assertInstanceOf(DOMElement::class, $logo);
        self::assertSame('../assets/logo-imagine.png', $logo->getAttribute('src'));
        self::assertSame('Imagine', $logo->getAttribute('alt'));
        self::assertSame('200', $logo->getAttribute('width'));
        self::assertSame('55', $logo->getAttribute('height'));
        self::assertFileExists(dirname(__DIR__, 2).'/prototypes/assets/logo-imagine.png');

        $elements = $xpath->query('//*');
        self::assertNotFalse($elements);
        foreach ($elements as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }
            foreach (['src', 'srcset', 'action', 'formaction', 'poster', 'data'] as $attribute) {
                if ($attribute === 'src' && strtolower($element->tagName) === 'img') {
                    self::assertSame(
                        '../assets/logo-imagine.png',
                        $element->getAttribute('src'),
                        'Only the audited local Imagine logo may load',
                    );
                    continue;
                }
                self::assertFalse($element->hasAttribute($attribute), $element->tagName.'@'.$attribute.' may introduce a resource or action');
            }
            if ($element->hasAttribute('href')) {
                self::assertSame('a', strtolower($element->tagName), 'Only anchors may carry local navigation links');
                self::assertContains(
                    $element->getAttribute('href'),
                    ['#conteudo', '../f4-15-followup-dashboard/index.html'],
                    'Only explicit, vetted local navigation destinations are permitted'
                );
            }
        }

        self::assertSame(9, $xpath->query('//section[@data-screen]')->length);
    }
}
