<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * UX-SEC-02: navigation and control integrity of synthetic/offline screens.
 * Static assertions only; manual accessibility review remains required.
 */
final class OfflinePrototypeNavigationIntegrityTest extends TestCase
{
    public function test_navigation_targets_are_unique_present_and_locally_scoped(): void
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
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) {
            self::assertInstanceOf(DOMElement::class, $element);
            $id = $element->getAttribute('id');
            self::assertNotSame('', $id);
            self::assertArrayNotHasKey($id, $ids, 'Duplicate HTML id: '.$id);
            $ids[$id] = $element;
        }

        $screens = $xpath->query('//section[@data-screen]');
        self::assertSame(9, $screens->length);
        $screenIds = [];
        foreach ($screens as $section) {
            self::assertInstanceOf(DOMElement::class, $section);
            $screenIds[] = $section->getAttribute('id');
            self::assertNotSame('', $section->getAttribute('aria-label'));
        }

        $nav = $xpath->query('//nav[@id="demo-navigation"]//button[@data-page]');
        self::assertSame(9, $nav->length);
        $targets = [];
        $current = 0;
        foreach ($nav as $button) {
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'));
            $target = $button->getAttribute('aria-controls');
            self::assertArrayHasKey($target, $ids);
            self::assertContains($target, $screenIds);
            self::assertNotContains($target, $targets);
            $targets[] = $target;
            if ($button->getAttribute('aria-current') === 'page') {
                ++$current;
                self::assertFalse($ids[$target]->hasAttribute('hidden'));
            } else {
                self::assertTrue($ids[$target]->hasAttribute('hidden'));
            }
        }
        self::assertSame(1, $current);
        self::assertEqualsCanonicalizing($screenIds, $targets);

        foreach ($xpath->query('//button') as $button) {
            self::assertInstanceOf(DOMElement::class, $button);
            self::assertSame('button', $button->getAttribute('type'), 'Every offline button must be non-submit');
            if ($button->hasAttribute('aria-controls')) {
                self::assertArrayHasKey($button->getAttribute('aria-controls'), $ids);
            }
        }
    }
}
