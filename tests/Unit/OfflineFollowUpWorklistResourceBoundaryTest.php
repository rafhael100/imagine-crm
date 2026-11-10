<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Static, fail-closed checks for the offline worklist's HTML resource boundary.
 * This does not replace browser/network instrumentation or operational security review.
 */
final class OfflineFollowUpWorklistResourceBoundaryTest extends TestCase
{
    private static function source(): string
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/prototypes/f4-28-followup-worklist/index.html');
        self::assertIsString($source);

        return $source;
    }

    /**
     * @return list<string>
     */
    private static function violations(string $html): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                return ['invalid-html'];
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $violations = [];
        $xpath = new DOMXPath($document);
        $policies = $xpath->query(
            '//meta[translate(@http-equiv, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="content-security-policy"]',
        );

        if ($policies === false || $policies->length !== 1) {
            $violations[] = 'csp-count';
        } else {
            $policy = $policies->item(0)->getAttribute('content');
            foreach ([
                "default-src 'none'",
                "connect-src 'none'",
                "form-action 'none'",
                "img-src 'none'",
                "font-src 'none'",
                "object-src 'none'",
                "base-uri 'none'",
            ] as $directive) {
                if (!str_contains($policy, $directive)) {
                    $violations[] = 'csp-directive';
                }
            }
        }

        foreach ([
            'form', 'iframe', 'object', 'embed', 'base', 'link',
            'img', 'picture', 'video', 'audio', 'source', 'track', 'svg',
        ] as $forbiddenTag) {
            if ($document->getElementsByTagName($forbiddenTag)->length > 0) {
                $violations[] = 'external-resource-tag';
            }
        }

        foreach ($document->getElementsByTagName('style') as $style) {
            if (preg_match('/@import\b|url\s*\(/i', $style->textContent) === 1) {
                $violations[] = 'css-resource';
            }
        }

        $links = [];
        $elements = $xpath->query('//*');
        if ($elements === false) {
            return ['invalid-document-query'];
        }

        foreach ($elements as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }

            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->name);
                if (str_starts_with($name, 'on')) {
                    $violations[] = 'inline-event-handler';
                }
                if (in_array($name, [
                    'src', 'srcset', 'srcdoc', 'action', 'formaction',
                    'poster', 'ping', 'data', 'background',
                ], true)) {
                    $violations[] = 'resource-or-action-attribute';
                }
            }

            if ($element->hasAttribute('href')) {
                $destination = $element->getAttribute('href');
                if (strtolower($element->tagName) !== 'a'
                    || !in_array($destination, [
                        '#main', '../f4-15-followup-dashboard/index.html',
                    ], true)) {
                    $violations[] = 'external-href';
                }
                $links[] = $destination;
            }
            if ($element->hasAttribute('target') || $element->hasAttribute('download')) {
                $violations[] = 'external-navigation-attribute';
            }
        }

        $expected = ['#main', '../f4-15-followup-dashboard/index.html'];
        sort($links);
        sort($expected);
        if ($links !== $expected) {
            $violations[] = 'navigation-drift';
        }

        return array_values(array_unique($violations));
    }

    public function test_current_worklist_keeps_a_local_only_resource_boundary(): void
    {
        self::assertSame([], self::violations(self::source()));
    }

    public function test_detects_remote_navigation_even_without_a_resource_tag(): void
    {
        $injected = str_replace(
            'href="../f4-15-followup-dashboard/index.html"',
            'href="https://outside.example.invalid/collect"',
            self::source(),
        );
        self::assertNotSame(self::source(), $injected);
        self::assertContains('external-href', self::violations($injected));
    }

    public function test_detects_new_remote_resource_and_inline_event_handler(): void
    {
        $injected = str_replace(
            '</body>',
            '<img src="https://outside.example.invalid/pixel"><button onclick="alert(1)">x</button></body>',
            self::source(),
        );
        $violations = self::violations($injected);
        self::assertContains('external-resource-tag', $violations);
        self::assertContains('resource-or-action-attribute', $violations);
        self::assertContains('inline-event-handler', $violations);
    }

    public function test_detects_weakened_csp_and_css_imports(): void
    {
        $weakened = str_replace("connect-src 'none'", "connect-src *", self::source());
        self::assertContains('csp-directive', self::violations($weakened));

        $injected = str_replace(
            '<style>',
            '<style>@import url(https://outside.example.invalid/font.css);',
            self::source(),
        );
        self::assertContains('css-resource', self::violations($injected));
    }
}
