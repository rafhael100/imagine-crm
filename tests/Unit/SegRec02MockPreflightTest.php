<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * SEG-REC-02: exercises the CI preflight against FAKE docker/psql binaries.
 * Nothing in this class contacts a Docker daemon or a PostgreSQL server.
 * All "successful connection" output is synthetic; it is not a real proof
 * of database identity, network isolation, DDL permission or durability.
 */
final class SegRec02MockPreflightTest extends TestCase
{
    private string $fakeDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDirectory = sys_get_temp_dir().'/seg-rec-02-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->fakeDirectory, 0700));
        $fixture = dirname(__DIR__).'/Fixtures/SegRec02FakeCiCli.php';
        foreach (['docker', 'psql'] as $tool) {
            self::assertTrue(copy($fixture, $this->fakeDirectory.'/'.$tool));
            self::assertTrue(chmod($this->fakeDirectory.'/'.$tool, 0700));
        }
    }

    protected function tearDown(): void
    {
        foreach (['docker', 'psql', 'calls.jsonl'] as $name) {
            $path = $this->fakeDirectory.'/'.$name;
            if (is_file($path)) {
                unlink($path);
            }
        }
        if (is_dir($this->fakeDirectory)) {
            rmdir($this->fakeDirectory);
        }
        parent::tearDown();
    }

    private function profile(array $changes = []): array
    {
        $path = getenv('PATH') ?: '/usr/bin:/bin';

        return array_replace([
            'PATH' => $this->fakeDirectory.PATH_SEPARATOR.$path,
            'SEG_REC_02_CASE' => 'ok',
            'SEG_REC_02_LOG' => $this->fakeDirectory.'/calls.jsonl',
            'CI' => 'true',
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_REPOSITORY' => 'rafhael100/imagine-crm',
            'GITHUB_EVENT_NAME' => 'pull_request',
            'GITHUB_REF' => 'refs/pull/28/merge',
            'GITHUB_BASE_REF' => 'dev/fast-track-crm-2026-11-04',
            'GITHUB_HEAD_REF' => 'dev/seg-rec-02-mock-preflight-2026-11-08',
            'RUNNER_ENVIRONMENT' => 'github-hosted',
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
            'DB_DATABASE' => 'imagine_crm_ci',
            'CRM_TENANT_SCHEMA_LAB_DATABASE' => 'imagine_crm_ci',
            'DB_USERNAME' => 'postgres',
            'DB_PASSWORD' => 'synthetic-only',
            'DB_URL' => false,
            'DATABASE_URL' => false,
            'PGHOST' => false,
            'PGSERVICE' => false,
            'PGSERVICEFILE' => false,
        ], $changes);
    }

    private function invoke(array $overrides = []): Process
    {
        $root = dirname(__DIR__, 2);
        $process = new Process(
            [PHP_BINARY, $root.'/scripts/ci/verify-ephemeral-postgres.php', '--verify'],
            $root,
            $this->profile($overrides)
        );
        $process->setTimeout(15);
        $process->run();

        return $process;
    }

    private function calls(): array
    {
        $path = $this->fakeDirectory.'/calls.jsonl';
        if (!is_file($path)) {
            return [];
        }

        return array_map(
            static fn (string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
            array_values(array_filter(explode("\n", trim((string) file_get_contents($path)))))
        );
    }

    public function test_fully_mocked_good_profile_uses_only_expected_read_only_queries(): void
    {
        $process = $this->invoke();
        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        self::assertStringContainsString('verified GitHub-hosted PostgreSQL service', $process->getOutput());

        $calls = $this->calls();
        self::assertCount(4, $calls);
        self::assertSame(['docker', 'docker', 'docker', 'psql'], array_column($calls, 'tool'));
        self::assertSame(['ps', 'inspect', 'exec'], array_map(
            static fn (array $call): string => $call['args'][0],
            array_slice($calls, 0, 3)
        ));

        foreach (array_slice($calls, 2) as $call) {
            $command = $call['args'];
            $index = array_search('-c', $command, true);
            self::assertNotFalse($index);
            self::assertArrayHasKey($index + 1, $command);
            $sql = $command[$index + 1];
            self::assertStringStartsWith('SELECT ', $sql);
            self::assertStringContainsString('pg_control_system()', $sql);
            self::assertDoesNotMatchRegularExpression(
                '/\b(DROP|TRUNCATE|INSERT|UPDATE|DELETE|ALTER|CREATE|GRANT|REVOKE)\b/i',
                $sql
            );
        }
    }

    public static function negativeInfrastructureCases(): array
    {
        return [
            'Docker ps command error' => ['docker-ps-error'],
            'PostgreSQL service missing' => ['missing-container'],
            'two PostgreSQL services' => ['duplicate-containers'],
            'malformed container id' => ['malformed-container-id'],
            'Docker inspect error' => ['docker-inspect-error'],
            'stopped container' => ['stopped-container'],
            'wrong PostgreSQL image' => ['wrong-image'],
            'host bind-mounted container' => ['bind-mount'],
            'container lacks IP' => ['no-container-ip'],
            'inside PostgreSQL query failure' => ['inside-query-error'],
            'outside PostgreSQL query failure' => ['outside-query-error'],
            'unexpected database name' => ['wrong-db'],
            'unexpected database role' => ['wrong-role'],
            'malformed PostgreSQL system identifier' => ['malformed-system-id'],
            'different PostgreSQL cluster via loopback' => ['cluster-mismatch'],
        ];
    }

    #[DataProvider('negativeInfrastructureCases')]
    public function test_infrastructure_failures_deny_without_schema_changes(string $scenario): void
    {
        $process = $this->invoke(['SEG_REC_02_CASE' => $scenario]);
        self::assertSame(42, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        self::assertStringContainsString('CRM-SEC-CI-01 DENIED:', $process->getErrorOutput());

        foreach ($this->calls() as $call) {
            self::assertContains($call['tool'], ['docker', 'psql']);
            if ($call['tool'] === 'docker') {
                self::assertContains($call['args'][0] ?? '', ['ps', 'inspect', 'exec']);
            }
            if ($call['tool'] === 'psql' || ($call['args'][0] ?? '') === 'exec') {
                $index = array_search('-c', $call['args'], true);
                self::assertNotFalse($index);
                self::assertStringStartsWith('SELECT ', $call['args'][$index + 1]);
            }
        }
    }

    public function test_wrong_profile_is_rejected_before_any_fake_tool_is_called(): void
    {
        $process = $this->invoke(['DB_HOST' => 'persistent-db.example.invalid']);
        self::assertSame(42, $process->getExitCode());
        self::assertStringContainsString('unexpected environment or database setting: DB_HOST', $process->getErrorOutput());
        self::assertSame([], $this->calls());
    }

    public function test_production_event_is_rejected_before_any_fake_tool_is_called(): void
    {
        $process = $this->invoke([
            'GITHUB_EVENT_NAME' => 'workflow_dispatch',
            'GITHUB_REF' => 'refs/heads/main',
        ]);
        self::assertSame(42, $process->getExitCode());
        self::assertStringContainsString('GitHub event is not authorized', $process->getErrorOutput());
        self::assertSame([], $this->calls());
    }
}
