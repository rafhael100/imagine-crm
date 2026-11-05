<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncDenial;
use App\Core\Async\AsyncDiagnostic;
use Illuminate\Support\Str;

/** Consultative comparison only. No connection, storage, repair or effect API. */
final class LedgerIntegrityProbe
{
    public const COLUMNS = [
        'ledger' => ['id', 'tenant_id', 'operation_id', 'idempotency_key', 'revision', 'scope_kind', 'scope_id', 'policy_version', 'digest', 'state', 'created_at', 'expires_at'],
        'attempts' => ['id', 'tenant_id', 'ledger_id', 'state', 'outcome', 'correlation_id', 'causation_id', 'created_at'],
        'inbox' => ['id', 'tenant_id', 'ledger_id', 'state'],
        'outbox' => ['id', 'tenant_id', 'ledger_id', 'state'],
    ];

    public function inspect(array $sample, LedgerFixtureManifest $manifest, string $tenant, ?AsyncDenial $denial = null): LedgerIntegrityReport
    {
        if (!config('identity.experimental') || !app()->environment('local', 'testing')) {
            return new LedgerIntegrityReport([LedgerGap::LaboratoryDisabled], AsyncDiagnostic::disabled(), $denial);
        }
        if (!in_array($tenant, [AuthorizationCases::TENANT, AuthorizationCases::OTHER], true)
            || !self::sameKeys($sample, array_keys(self::COLUMNS))) { return new LedgerIntegrityReport([LedgerGap::InvalidSample], denial: $denial); }
        // Validate all shapes BEFORE interpreting values; never echo any field.
        foreach (self::COLUMNS as $table => $columns) {
            if (!is_array($sample[$table]) || !array_is_list($sample[$table]) || count($sample[$table]) > 200) {
                return new LedgerIntegrityReport([LedgerGap::InvalidSample], denial: $denial);
            }
            foreach ($sample[$table] as $row) {
                if (!is_array($row) || !self::sameKeys($row, $columns)) { return new LedgerIntegrityReport([LedgerGap::InvalidSample], denial: $denial); }
                foreach ($row as $column => $value) {
                    if (str_ends_with($column, '_id') || $column === 'id' || $column === 'idempotency_key') {
                        if (!is_string($value) || !Str::isUuid($value)) { return new LedgerIntegrityReport([LedgerGap::InvalidSample], denial: $denial); }
                    } elseif (in_array($column, ['revision', 'created_at', 'expires_at'], true)) {
                        if (!is_int($value) || $value < 1) { return new LedgerIntegrityReport([LedgerGap::InvalidSample], denial: $denial); }
                    } elseif (!is_string($value)) { return new LedgerIntegrityReport([LedgerGap::InvalidSample], denial: $denial); }
                }
                if ($row['tenant_id'] !== $tenant) { return new LedgerIntegrityReport([LedgerGap::CrossTenant], denial: $denial); }
            }
        }
        $gaps = []; $expected = []; $expectedAttempts = []; $expectedVersions = [];
        foreach ($manifest->cases as [$description, $receipts]) {
            if ($description->request->tenantId !== $tenant) { return new LedgerIntegrityReport([LedgerGap::CrossTenant], denial: $denial); }
            $key = LedgerFixtureManifest::key($description); $expected[$key] = $description;
            $r = $description->request;
            $expectedVersions[implode('|', [$tenant, $r->operationId, $r->intent->scope, $r->intent->scopeId])][$description->revision] = true;
            foreach ($receipts as $receipt) { $expectedAttempts[$receipt->attemptId] = [$key, $receipt]; }
        }
        $ledgers = []; $logical = []; $delivery = []; $seen = [];
        foreach ($sample['ledger'] as $row) {
            $key = self::key($row);
            $deliveryKey = implode('|', [$tenant, $row['idempotency_key'], $row['revision'], $row['scope_kind'], $row['scope_id']]);
            if (isset($ledgers[$row['id']]) || isset($logical[$key]) || isset($delivery[$deliveryKey])) { $gaps[] = LedgerGap::ApparentDuplicate; }
            $ledgers[$row['id']] = $row; $logical[$key] = true; $delivery[$deliveryKey] = true;
            if (!in_array($row['revision'], [1, 2], true) || $row['policy_version'] !== 'lab-async-deny-v1') { $gaps[] = LedgerGap::VersionMismatch; }
            $versionKey = implode('|', [$tenant, $row['operation_id'], $row['scope_kind'], $row['scope_id']]);
            if (isset($expectedVersions[$versionKey]) && !isset($expectedVersions[$versionKey][$row['revision']])) { $gaps[] = LedgerGap::VersionMismatch; }
            if ($row['state'] !== 'registered_for_test') { $gaps[] = LedgerGap::InvalidState; }
            if (!in_array($row['scope_kind'], ['tenant', 'record', 'team', 'unit'], true)) { $gaps[] = LedgerGap::InvalidSample; }
            if (!preg_match('/\A[0-9a-f]{64}\z/D', $row['digest'])) { $gaps[] = LedgerGap::DigestMismatch; }
            if ($row['created_at'] > now()->getTimestamp() || $row['expires_at'] <= $row['created_at']) { $gaps[] = LedgerGap::InvalidValidity; }
            if ($row['expires_at'] <= now()->getTimestamp()) { $gaps[] = LedgerGap::Expired; }
            if (!isset($expected[$key])) { $gaps[] = LedgerGap::UnexpectedRecord; continue; }
            $seen[$key] = true; $description = $expected[$key];
            if (!hash_equals($description->digest, $row['digest'])) { $gaps[] = LedgerGap::DigestMismatch; }
            if ($row['idempotency_key'] !== $description->request->idempotencyKey) { $gaps[] = LedgerGap::AttemptMismatch; }
            if ($row['expires_at'] !== $description->request->expiresAt || $row['created_at'] < $description->request->issuedAt) { $gaps[] = LedgerGap::InvalidValidity; }
        }
        foreach ($expected as $key => $_) { if (!isset($seen[$key])) { $gaps[] = LedgerGap::MissingLedger; } }
        foreach (['inbox' => LedgerGap::MissingInbox, 'outbox' => LedgerGap::MissingOutbox] as $table => $missing) {
            $counts = []; $ids = [];
            foreach ($sample[$table] as $row) {
                if (isset($ids[$row['id']])) { $gaps[] = LedgerGap::ApparentDuplicate; }
                $ids[$row['id']] = true; $counts[$row['ledger_id']] = ($counts[$row['ledger_id']] ?? 0) + 1;
                if (!isset($ledgers[$row['ledger_id']])) { $gaps[] = LedgerGap::OrphanAssociation; }
                if ($row['state'] !== 'registered_for_test') { $gaps[] = LedgerGap::InvalidState; }
            }
            foreach ($ledgers as $id => $_) {
                if (($counts[$id] ?? 0) === 0) { $gaps[] = $missing; }
                if (($counts[$id] ?? 0) > 1) { $gaps[] = LedgerGap::DoubleAssociation; }
            }
        }
        $ids = []; $first = [];
        foreach ($sample['attempts'] as $row) {
            if (isset($ids[$row['id']])) { $gaps[] = LedgerGap::ApparentDuplicate; }
            $ids[$row['id']] = true;
            if (!isset($ledgers[$row['ledger_id']])) { $gaps[] = LedgerGap::OrphanAttempt; }
            else {
                $parent = $ledgers[$row['ledger_id']];
                if ($row['created_at'] < $parent['created_at'] || $row['created_at'] >= $parent['expires_at'] || $row['created_at'] > now()->getTimestamp()) { $gaps[] = LedgerGap::InvalidValidity; }
                if ($row['outcome'] === 'first_seen') { $first[$row['ledger_id']] = ($first[$row['ledger_id']] ?? 0) + 1; }
            }
            if ($row['state'] !== 'reconciliation_required' || !in_array($row['outcome'], ['first_seen', 'duplicate', 'conflict'], true)) { $gaps[] = LedgerGap::InvalidState; }
            if ($row['outcome'] === 'conflict') { $gaps[] = LedgerGap::ConflictObserved; }
            if (!isset($expectedAttempts[$row['id']])) { $gaps[] = LedgerGap::UnexpectedRecord; continue; }
            [$key, $receipt] = $expectedAttempts[$row['id']];
            if (!isset($ledgers[$row['ledger_id']]) || self::key($ledgers[$row['ledger_id']]) !== $key || $row['outcome'] !== $receipt->outcome) { $gaps[] = LedgerGap::AttemptMismatch; }
            if ($row['correlation_id'] !== $receipt->denial->observation->correlationId || $row['causation_id'] !== $receipt->denial->observation->id) { $gaps[] = LedgerGap::CorrelationMismatch; }
        }
        foreach ($expectedAttempts as $id => $_) { if (!isset($ids[$id])) { $gaps[] = LedgerGap::MissingAttempt; } }
        foreach ($ledgers as $id => $_) {
            if (($first[$id] ?? 0) === 0) { $gaps[] = LedgerGap::MissingAttempt; }
            if (($first[$id] ?? 0) > 1) { $gaps[] = LedgerGap::ApparentDuplicate; }
        }
        return new LedgerIntegrityReport($gaps, denial: $denial);
    }

    private static function key(array $row): string
    {
        return implode('|', [$row['tenant_id'], $row['operation_id'], $row['revision'], $row['scope_kind'], $row['scope_id']]);
    }

    private static function sameKeys(array $row, array $expected): bool
    {
        $keys = array_keys($row); sort($keys); sort($expected); return $keys === $expected;
    }
}
