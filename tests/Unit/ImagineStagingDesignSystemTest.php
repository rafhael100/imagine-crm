<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ImagineStagingDesignSystemTest extends TestCase
{
    public function test_original_portal_tokens_are_local_to_the_crm_and_do_not_require_external_stylesheets(): void
    {
        $root = dirname(__DIR__, 2);
        $tokens = file_get_contents($root.'/resources/views/partials/imagine-staging-tokens.blade.php');
        $this->assertIsString($tokens);
        foreach ([
            '--imagine-orange: #f9941d;',
            '--imagine-ink: #171b19;',
            '--imagine-white: #ffffff;',
            '--imagine-surface: #f7f7f8;',
            '--imagine-border: #e8e8e8;',
            '--imagine-sidebar-width: 240px;',
            '--imagine-font-ui: Arial, Helvetica, sans-serif;',
            '--imagine-touch-target: 44px;',
        ] as $mustExist) {
            $this->assertStringContainsString($mustExist, $tokens);
        }

        foreach ([
            'crm-staging-marketing.blade.php',
            'crm-staging-demo.blade.php',
        ] as $page) {
            $view = file_get_contents($root.'/resources/views/'.$page);
            $this->assertIsString($view);
            $this->assertStringContainsString("@include('partials.imagine-staging-tokens')", $view);
            $this->assertStringContainsString('var(--imagine-font-ui)', $view);
            $this->assertStringContainsString('var(--imagine-ink)', $view);
            $this->assertStringContainsString('focus-visible', $view);
            $this->assertStringContainsString('--imagine-touch-target', $view);
            $this->assertStringNotContainsString('fonts.googleapis.com', $view);
        }
    }

    public function test_commercial_and_kanban_views_support_keyboard_and_explicit_language_selection(): void
    {
        $root = dirname(__DIR__, 2);
        $marketing = file_get_contents($root.'/resources/views/crm-staging-marketing.blade.php');
        $kanban = file_get_contents($root.'/resources/views/crm-staging-demo.blade.php');
        $this->assertIsString($marketing);
        $this->assertIsString($kanban);
        $this->assertStringContainsString('class="skip" href="#main"', $marketing);
        $this->assertStringContainsString('class="skip" href="#main-content"', $kanban);
        $this->assertStringContainsString('id="main-content"', $kanban);
        $this->assertStringContainsString('aria-current="location"', $kanban);
        foreach (['lang=pt', 'lang=en', 'lang=es'] as $language) {
            $this->assertStringContainsString($language, $marketing);
            $this->assertStringContainsString($language, $kanban);
        }
    }
}
