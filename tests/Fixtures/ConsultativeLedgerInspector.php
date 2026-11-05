<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncAuthorityBridge;
use App\Core\Async\AsyncAuthorityRequest;
use App\Core\Async\AsyncDiagnostic;
use Throwable;

/** Revalidation is independent of integrity. A consistent sample never authorizes an effect. */
final readonly class ConsultativeLedgerInspector
{
    public function __construct(private PostgresLedgerReader $reader, private AsyncAuthorityBridge $bridge, private string $fixtureTenant) {}

    public function inspect(array $input, LedgerFixtureManifest $manifest): LedgerIntegrityReport
    {
        $denial = $this->bridge->inspect($input);
        if (!config('identity.experimental') || !app()->environment('local', 'testing')) {
            return new LedgerIntegrityReport([LedgerGap::LaboratoryDisabled], AsyncDiagnostic::disabled(), $denial);
        }
        try { $request = AsyncAuthorityRequest::fromArray($input); }
        catch (\InvalidArgumentException) { return new LedgerIntegrityReport([LedgerGap::InvalidSample], AsyncDiagnostic::contract(), $denial); }
        if ($request->tenantId !== $this->fixtureTenant) { return new LedgerIntegrityReport([LedgerGap::CrossTenant], denial: $denial); }
        try { return (new LedgerIntegrityProbe())->inspect($this->reader->sample(), $manifest, $this->fixtureTenant, $denial); }
        catch (LedgerReadFailure $failure) { return new LedgerIntegrityReport([$failure->gap], AsyncDiagnostic::fromFailure($failure), $denial); }
        catch (Throwable $failure) { return new LedgerIntegrityReport([LedgerGap::ReadUnavailable], AsyncDiagnostic::fromFailure($failure), $denial); }
    }
}
