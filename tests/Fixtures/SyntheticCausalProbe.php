<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncDiagnostic;
use Illuminate\Support\Str;

/** Arrival-order observations for a closed synthetic sample; no global ordering policy. */
final class SyntheticCausalProbe
{
    public function inspect(array $events, string $tenant): LedgerIntegrityReport
    {
        if (!config('identity.experimental') || !app()->environment('local', 'testing')) {
            return new LedgerIntegrityReport([LedgerGap::LaboratoryDisabled], AsyncDiagnostic::disabled());
        }
        if (!in_array($tenant, [AuthorizationCases::TENANT, AuthorizationCases::OTHER], true)
            || !array_is_list($events) || count($events) > 200) { return new LedgerIntegrityReport([LedgerGap::InvalidSample]); }
        $gaps = []; $seenEvents = []; $seenAttempts = []; $intentions = []; $versions = [];
        foreach ($events as $event) {
            $keys = is_array($event) ? array_keys($event) : []; sort($keys);
            $expected = ['event_id', 'tenant_id', 'operation_id', 'revision', 'attempt_id', 'predecessor_id', 'content']; sort($expected);
            if ($keys !== $expected || !is_int($event['revision']) || !in_array($event['revision'], [1, 2], true)
                || !in_array($event['content'], ['lab.alpha', 'lab.beta'], true)) { return new LedgerIntegrityReport([LedgerGap::InvalidSample]); }
            foreach (['event_id', 'tenant_id', 'operation_id', 'attempt_id', 'predecessor_id'] as $key) {
                if ($key === 'predecessor_id' && $event[$key] === null) { continue; }
                if (!is_string($event[$key]) || !Str::isUuid($event[$key])) { return new LedgerIntegrityReport([LedgerGap::InvalidSample]); }
                $event[$key] = strtolower($event[$key]);
            }
            if ($event['tenant_id'] !== $tenant) { return new LedgerIntegrityReport([LedgerGap::CrossTenant]); }
            $logical = $event['operation_id'].'|'.$event['revision'];
            if ($event['predecessor_id'] !== null && !isset($seenEvents[$event['predecessor_id']])) { $gaps[] = LedgerGap::PredecessorUnobserved; }
            if ($event['predecessor_id'] === $event['event_id']) { $gaps[] = LedgerGap::CausalConflict; }
            if (isset($seenEvents[$event['event_id']]) && $seenEvents[$event['event_id']] !== $event) { $gaps[] = LedgerGap::CausalConflict; }
            if (isset($seenAttempts[$event['attempt_id']])) {
                $gaps[] = LedgerGap::DuplicateAttempt;
                if ($seenAttempts[$event['attempt_id']] !== $event) { $gaps[] = LedgerGap::CausalConflict; }
            } elseif (isset($intentions[$logical])) {
                $gaps[] = $intentions[$logical] === $event['content'] ? LedgerGap::SameIntentNewAttempt : LedgerGap::CausalConflict;
            }
            if ($event['revision'] < ($versions[$event['operation_id']] ?? 0)) { $gaps[] = LedgerGap::OlderVersionObserved; }
            $versions[$event['operation_id']] = max($event['revision'], $versions[$event['operation_id']] ?? 0);
            $seenEvents[$event['event_id']] = $event; $seenAttempts[$event['attempt_id']] = $event;
            $intentions[$logical] ??= $event['content'];
        }
        return new LedgerIntegrityReport($gaps); // Always unknown / needs_owner, even for apparently ordered samples.
    }
}
