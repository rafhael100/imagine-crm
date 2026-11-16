<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Independent QA gate: static Blade labels must have a programmatic association.
 * This is a review safeguard, not a WCAG accessibility certification or
 * replacement for keyboard/screen-reader testing in a browser.
 */
final class PrivateCrmAccessibleFormLabelsTest extends TestCase
{
    private static function source(): string
    {
        $path = dirname(__DIR__, 2).'/resources/views/crm-private-journey.blade.php';
        $source = file_get_contents($path);
        self::assertIsString($source);
        return $source;
    }

    public function test_each_label_is_implicitly_nested_or_explicitly_targets_a_form_control(): void
    {
        $source = self::source();
        preg_match_all('~<label\b([^>]*)>(.*?)</label>~s', $source, $labels, PREG_SET_ORDER);
        self::assertGreaterThanOrEqual(32, count($labels));

        foreach ($labels as $label) {
            $attrs = $label[1];
            $body = $label[2];
            $hasNestedInput = preg_match('~<(input|select|textarea)\b~', $body) === 1;
            $hasFor = preg_match('~\bfor="([a-zA-Z][\w-]*)"~', $attrs, $matches) === 1;
            self::assertTrue($hasNestedInput || $hasFor, 'Unassociated form label: '.substr(trim(strip_tags($body)), 0, 50));

            if ($hasFor) {
                $escaped = preg_quote($matches[1], '~');
                self::assertSame(
                    1,
                    preg_match('~<(?:input|select|textarea)\b[^>]*\bid="'.$escaped.'"~s', $source),
                    'Label refers to a missing input/select/textarea: '.$matches[1],
                );
            }
        }
    }

    public function test_new_private_labels_target_distinct_inputs_and_selects(): void
    {
        $source = self::source();
        preg_match_all('~\bid="(crm-field-a11y-\d+)"~', $source, $ids);
        self::assertCount(16, $ids[1]);
        self::assertCount(count($ids[1]), array_unique($ids[1]));

        foreach ($ids[1] as $id) {
            $quoted = preg_quote($id, '~');
            self::assertSame(1, substr_count($source, 'for="'.$id.'"'));
            self::assertSame(
                1,
                preg_match('~<(?:input|select|textarea)\b[^>]*\bid="'.$quoted.'"~s', $source),
            );
        }
    }

    public function test_csrf_and_private_route_guards_remain_in_the_template(): void
    {
        $source = self::source();
        self::assertGreaterThanOrEqual(10, substr_count($source, '@csrf'));
        self::assertStringContainsString('HOMOLOGAÇÃO PRIVADA', $source);
        self::assertStringContainsString('NENHUMA COBRANÇA REAL', $source);
        self::assertStringContainsString('data-account-id=', $source);
    }
}
