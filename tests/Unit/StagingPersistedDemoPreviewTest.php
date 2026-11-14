<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Commercial\StagingPersistedDemoPreview;
use Illuminate\Database\Connection;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class StagingPersistedDemoPreviewTest extends TestCase
{
    public function test_sql_demo_does_not_accept_request_tenant_identity_or_write_permissions(): void
    {
        $method = new ReflectionMethod(StagingPersistedDemoPreview::class, 'presentation');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertSame('db', $parameters[0]->getName());
        $this->assertSame('language', $parameters[1]->getName());

        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Core/Commercial/StagingPersistedDemoPreview.php',
        );
        $this->assertIsString($source);
        $this->assertStringContainsString("SET TRANSACTION READ ONLY", $source);
        $this->assertStringContainsString("FORCE", file_get_contents(
            dirname(__DIR__, 2).'/tests/Labs/demo/ci-persisted-kanban-fixture.sql',
        ));
        $this->assertStringNotContainsString('request()->query', $source);
        $this->assertStringNotContainsString('Route::post', $source);
        $this->assertStringNotContainsString('lockForUpdate', $source);
    }

    public function test_synthetic_sql_endpoint_is_denied_without_private_staging_gate(): void
    {
        $db = $this->createMock(Connection::class);
        $this->expectException(LogicException::class);
        StagingPersistedDemoPreview::presentation($db, 'pt');
    }
}
