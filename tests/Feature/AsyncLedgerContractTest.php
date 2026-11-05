<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Fixtures\AsyncCases;
use Tests\Fixtures\LedgerDescription;
use Tests\TestCase;

final class AsyncLedgerContractTest extends TestCase
{
    private const PATH = 'database/experimental-migrations/async';

    public function test_closed_synthetic_content_and_contract_reject_free_payload_and_unknown_versions(): void
    {
        foreach (AsyncCases::malformed() as [$input]) {
            try { LedgerDescription::parse($input, 1, 'lab.alpha'); $this->fail('Untrusted contract accepted.'); }
            catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        foreach ([[0, 'lab.alpha'], [3, 'lab.alpha'], [1, 'synthetic@example.invalid'], [1, '{"token":"fixture"}']] as [$revision, $content]) {
            try { LedgerDescription::parse(AsyncCases::request(), $revision, $content); $this->fail('Free payload/version accepted.'); }
            catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }

    public function test_digest_is_canonical_but_does_not_erase_semantic_changes(): void
    {
        $input = AsyncCases::request(); $first = LedgerDescription::parse($input, 1, 'lab.alpha');
        $input = array_reverse($input, true); $input['correlation_id'] = $input['tenant_id'];
        $input['operation_id'] = strtoupper($input['operation_id']);
        $this->assertSame($first->digest, LedgerDescription::parse($input, 1, 'lab.alpha')->digest);
        $this->assertNotSame($first->digest, LedgerDescription::parse($input, 1, 'lab.beta')->digest);
        $this->assertNotSame($first->digest, LedgerDescription::parse($input, 2, 'lab.alpha')->digest);
        $input['intent']['action'] = 'read';
        $this->assertNotSame($first->digest, LedgerDescription::parse($input, 1, 'lab.alpha')->digest);
    }

    public function test_migration_is_opt_in_and_refuses_disabled_staging_production_and_sqlite(): void
    {
        $files = glob(base_path(self::PATH).'/*.php'); $this->assertCount(1, $files);
        $discovered = $this->app['migrator']->getMigrationFiles(database_path('migrations'));
        $this->assertArrayNotHasKey(basename($files[0], '.php'), $discovered);
        foreach ([['production', true], ['staging', true], ['testing', false]] as [$env, $enabled]) {
            $this->app->instance('env', $env); config(['identity.experimental' => $enabled]);
            foreach (['up', 'down'] as $direction) {
                try { (require $files[0])->$direction(); $this->fail('Unsafe migration profile accepted.'); }
                catch (\RuntimeException $e) { $this->assertSame('Synthetic async schema is laboratory only.', $e->getMessage()); }
            }
        }
        $this->app->instance('env', 'testing'); config(['identity.experimental' => true]);
        if (DB::getDriverName() === 'sqlite') {
            try { (require $files[0])->up(); $this->fail('SQLite is not an RLS proof.'); }
            catch (\RuntimeException $e) { $this->assertSame('PostgreSQL required for synthetic ledger.', $e->getMessage()); }
        }
    }
}
