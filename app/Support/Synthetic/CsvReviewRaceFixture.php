<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

use InvalidArgumentException;
use RuntimeException;

/**
 * A single-process, forgeable in-memory teaching model for review races.
 *
 * NO database, lock, authentication, durable snapshot, multi-tenant permission,
 * audit ledger or import operation exists here. Never wire this to runtime.
 */
final class CsvReviewRaceFixture
{
    /** @var list<list<string>> */
    private array $rows;

    /** @var list<array{column:int,target:string}> */
    private array $mapping;

    private int $version = 1;

    private int $recordedReviews = 0;

    private int $lastDecisionCount = 0;

    /** @var array<string,string> */
    private array $recordedRequests = [];

    private string $fingerprint;

    /**
     * @param list<list<string>> $rows Invented already-split CSV-like cells.
     * @param list<array{column:int,target:string}> $mapping Fixture-only destinations.
     */
    public function __construct(array $rows, array $mapping = [])
    {
        $this->fingerprint = CsvReviewSnapshotPreview::fingerprint($rows, $mapping);
        $this->rows = $rows;
        $this->mapping = $mapping;
    }

    /** Public, forgeable lab marker. Not a secret, identity or permission. */
    public function marker(): string
    {
        return $this->fingerprint;
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @param list<array{row:int,status:string}> $decisions
     * @param string $syntheticFault Only fixture_none, fixture_abort_before_record,
     *     or fixture_lost_ack_after_record. Not operational fault handling.
     * @return array{
     *   status:string,fixture_version:int,recorded_review_events:int,
     *   last_decision_count:int,human_verified:bool,
     *   review_required:bool,import_permitted:bool
     * }
     */
    public function submit(
        int $expectedVersion,
        string $expectedFingerprint,
        string $idempotencyKey,
        array $decisions,
        string $syntheticFault = 'fixture_none',
    ): array {
        if (!in_array($syntheticFault, [
            'fixture_none',
            'fixture_abort_before_record',
            'fixture_lost_ack_after_record',
        ], true)) {
            throw new InvalidArgumentException('Invalid synthetic failure window.');
        }

        if ($expectedVersion < 1
            || preg_match('/\Afixture-sha256-v1:[a-f0-9]{64}\z/D', $expectedFingerprint) !== 1
            || preg_match('/\Afixture_key_[a-z0-9]{1,24}\z/D', $idempotencyKey) !== 1) {
            throw new InvalidArgumentException('Malformed synthetic review request.');
        }

        // Normalize only the closed fixture command shape, before any replay
        // comparison. An attacker cannot smuggle tenant, actor, ALLOW or PII.
        $normalized = self::canonicalDecisions($decisions);
        $requestHash = hash('sha256', json_encode([
            'expected_version' => $expectedVersion,
            'fingerprint' => $expectedFingerprint,
            'decisions' => $normalized,
        ], JSON_THROW_ON_ERROR));

        // A changed snapshot takes precedence over an old idempotency result.
        // Returning a past "recorded" response would mislead the reviewer.
        if (!hash_equals($this->fingerprint, $expectedFingerprint)) {
            return $this->state('fixture_stale_no_op');
        }

        if (isset($this->recordedRequests[$idempotencyKey])) {
            return $this->state(hash_equals(
                $this->recordedRequests[$idempotencyKey],
                $requestHash,
            ) ? 'fixture_idempotent_replay_no_op' : 'fixture_idempotency_conflict_no_op');
        }

        // The version is incremented after each distinct simulated write,
        // even though the fixture bytes themselves are unchanged.
        if ($expectedVersion !== $this->version) {
            return $this->state('fixture_stale_no_op');
        }

        if (count($this->recordedRequests) >= 99) {
            return $this->state('fixture_history_limit_no_op');
        }

        // This check is deliberately AFTER CAS-like in-memory version check.
        // It re-evaluates fixture findings; no caller can clear an alert by
        // supplying a synthetic "approved" or "clean" flag.
        CsvReviewSnapshotPreview::reconcile(
            $this->rows, $this->mapping, $expectedFingerprint, $normalized,
        );

        // Explicit fault injection is restricted to this invented model.
        // Reject BEFORE the local recording step; no key is consumed.
        if ($syntheticFault === 'fixture_abort_before_record') {
            return $this->state('fixture_abort_no_op');
        }

        $this->recordedRequests[$idempotencyKey] = $requestHash;
        ++$this->version;
        ++$this->recordedReviews;
        $this->lastDecisionCount = count($normalized);

        // Simulate a lost response AFTER a local event was recorded.
        // A caller must not treat this exception as proof of rollback.
        if ($syntheticFault === 'fixture_lost_ack_after_record') {
            throw new RuntimeException('Synthetic acknowledgement unavailable; outcome unknown.');
        }

        return $this->state('fixture_recorded_only');
    }

    /**
     * Model a change to the already invented matrix/mapping. A real system
     * MUST use a trusted durable transaction and authenticated permissions.
     *
     * @param list<list<string>> $rows
     * @param list<array{column:int,target:string}> $mapping
     * @return array<string,mixed>
     */
    public function replaceFixture(
        int $expectedVersion,
        array $rows,
        array $mapping = [],
    ): array {
        if ($expectedVersion < 1) {
            throw new InvalidArgumentException('Malformed synthetic version.');
        }

        $candidate = CsvReviewSnapshotPreview::fingerprint($rows, $mapping);
        if ($expectedVersion !== $this->version) {
            return $this->state('fixture_stale_no_op');
        }
        if (hash_equals($this->fingerprint, $candidate)) {
            return $this->state('fixture_unchanged_no_op');
        }

        $this->rows = $rows;
        $this->mapping = $mapping;
        $this->fingerprint = $candidate;
        ++$this->version;

        // Every previous fake review is now invalid for this version.
        // No old idempotency result may survive this fixture revision.
        $this->recordedReviews = 0;
        $this->lastDecisionCount = 0;
        $this->recordedRequests = [];

        return $this->state('fixture_replaced_review_reset');
    }

    /** @return array<string,mixed> */
    public function summary(): array
    {
        return $this->state('fixture_read_only');
    }

    /**
     * @param array<mixed> $decisions
     * @return list<array{row:int,status:string}>
     */
    private static function canonicalDecisions(array $decisions): array
    {
        if (!array_is_list($decisions) || count($decisions) < 1 || count($decisions) > 99) {
            throw new InvalidArgumentException('Invalid synthetic decisions list.');
        }

        $canonical = [];
        $seen = [];
        foreach ($decisions as $entry) {
            if (!is_array($entry) || count($entry) !== 2
                || !isset($entry['row'], $entry['status'])
                || !is_int($entry['row']) || $entry['row'] < 2
                || !is_string($entry['status'])
                || !in_array($entry['status'], [
                    'fixture_reviewed_no_findings', 'fixture_needs_correction',
                ], true)
                || isset($seen[$entry['row']])) {
                throw new InvalidArgumentException('Invalid synthetic row decision.');
            }

            $seen[$entry['row']] = true;
            $canonical[] = ['row' => $entry['row'], 'status' => $entry['status']];
        }

        usort($canonical, static fn (array $a, array $b): int => $a['row'] <=> $b['row']);

        return $canonical;
    }

    /** @return array<string,mixed> */
    private function state(string $status): array
    {
        return [
            'status' => $status,
            'fixture_version' => $this->version,
            'recorded_review_events' => $this->recordedReviews,
            'last_decision_count' => $this->lastDecisionCount,
            // Neither a public marker, fake idempotency key, nor a successful
            // in-memory CAS establishes a human or business permission.
            'human_verified' => false,
            'review_required' => true,
            'import_permitted' => false,
        ];
    }
}
