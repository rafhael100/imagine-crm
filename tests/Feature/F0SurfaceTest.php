<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class F0SurfaceTest extends TestCase
{
    public function test_health_check(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_public_home_route_is_not_published(): void
    {
        $this->get('/')->assertNotFound();
    }
}
