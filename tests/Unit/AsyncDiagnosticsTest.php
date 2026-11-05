<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Async\AsyncDiagnostic;
use App\Core\Async\AsyncFailureKind as Kind;
use App\Core\Async\AsyncObservation;
use App\Core\Async\AsyncState;
use App\Core\Async\ExecutionClass;
use App\Core\Async\ReconciliationKind;
use App\Core\Async\ReconciliationTrace;
use App\Core\Identity\SessionRevoked;
use App\Core\Tenancy\ContaminatedTenantConnection;
use App\Core\Tenancy\MembershipDenied;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;
use LogicException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Fixtures\AsyncCases;
use Tests\Fixtures\SyntheticAsyncInbox;
use Tests\TestCase;
use Throwable;

final class AsyncDiagnosticsTest extends TestCase
{
    public static function failures(): array
    {
        $cases = [
            'registry revocation' => [new SessionRevoked(), Kind::SessionRevoked, 'session_registry'],
            'authentication incomplete' => [new AuthenticationException('synthetic-token'), Kind::SessionUnproven, 'human_boundary'],
            'membership rejection' => [new MembershipDenied(), Kind::MembershipDenied, 'membership_boundary'],
            'dirty context' => [new ContaminatedTenantConnection(), Kind::ContextContamination, 'tenant_connection'],
            'ACL denied' => [new AuthorizationException('synthetic-token'), Kind::AuthorityUnproven, 'authority_boundary'],
            'forged exception text' => [new RuntimeException('session_revoked infrastructure_unavailable'), Kind::UnexpectedFailure, 'boundary_failure'],
            'programming error' => [new \TypeError('synthetic@example.invalid'), Kind::UnexpectedFailure, 'boundary_failure'],
        ];
        foreach (['08006', '57P01', '55P03', '40001', '42501', '23505'] as $state) {
            $failure = new PDOException('synthetic-token synthetic@example.invalid');
            $failure->errorInfo = [$state, 7, 'synthetic SQL tenant payload'];
            $kind = match ($state) { '08006', '57P01' => Kind::InfrastructureUnavailable,
                '55P03', '40001' => Kind::ConcurrencyBlocked, default => Kind::UnexpectedFailure };
            $cases['SQLSTATE '.$state] = [$failure, $kind, $kind === Kind::UnexpectedFailure ? 'boundary_failure' : 'database_driver'];
        }
        return $cases;
    }

    #[DataProvider('failures')]
    public function test_diagnosis_uses_known_types_and_closed_driver_codes_without_retaining_the_error(Throwable $failure, Kind $kind, string $source): void
    {
        $diagnostic = AsyncDiagnostic::fromFailure($failure);
        $this->assertSame($kind, $diagnostic->kind); $this->assertSame($source, $diagnostic->source);
        $serialized = serialize($diagnostic);
        foreach (['synthetic-token', 'synthetic@example.invalid', 'synthetic SQL', 'errorInfo', 'trace', 'previous'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serialized);
        }
    }

    public function test_observation_has_only_backend_references_and_labels_class_as_unverified(): void
    {
        $correlation = (string) Str::uuid();
        $before = now()->getTimestamp();
        $one = AsyncObservation::inspection($correlation, AsyncDiagnostic::authority(), ExecutionClass::DeferredHuman);
        $two = AsyncObservation::inspection($correlation, AsyncDiagnostic::authority(), ExecutionClass::DeferredHuman);
        $this->assertNotSame($one->id, $two->id);
        $this->assertSame($correlation, $one->causationId);
        $this->assertSame('unverified_claim', $one->signal()['class_evidence']);
        $this->assertSame('laboratory_partial', $one->signal()['coverage']);
        $this->assertSame('deny', $one->signal()['result']);
        $this->assertGreaterThanOrEqual($before, $one->occurredAt);
        $this->assertLessThanOrEqual(now()->getTimestamp(), $one->occurredAt);
        $this->assertSame(['schema_version', 'type', 'observation_id', 'correlation_id', 'causation_id', 'stage',
            'classification', 'source', 'execution_class_claim', 'class_evidence', 'result', 'occurred_at', 'coverage'], array_keys($one->signal()));
    }

    public static function reconciliationKinds(): array
    {
        return array_map(fn ($kind) => [$kind], ReconciliationKind::cases());
    }

    #[DataProvider('reconciliationKinds')]
    public function test_reconciliation_remains_pending_without_admission_confirmation_or_retry(ReconciliationKind $kind): void
    {
        $parent = AsyncObservation::inspection((string) Str::uuid(), AsyncDiagnostic::authority(), null);
        $trace = ReconciliationTrace::pending($kind, $parent);
        $this->assertSame(Kind::ReconciliationNeeded, $trace->diagnostic->kind);
        $this->assertSame($parent->id, $trace->causeId);
        $this->assertSame(AsyncState::ReconciliationRequired, $trace->lifecycle->state);
        foreach ([AsyncState::Admitted, AsyncState::Forwarded, AsyncState::OfficiallyConfirmed, AsyncState::Received] as $state) {
            try { $trace->lifecycle->transition($state); $this->fail('No execution or retry transition.'); }
            catch (LogicException) { $this->assertSame('reconciliation_required', $trace->signal()['state']); }
        }
    }

    public function test_test_only_inbox_derives_duplicate_and_conflict_without_trusting_a_payload_category(): void
    {
        $inbox = new SyntheticAsyncInbox(); $input = AsyncCases::request();
        $parent = AsyncObservation::inspection((string) Str::uuid(), AsyncDiagnostic::authority(), null);
        $request = \App\Core\Async\AsyncAuthorityRequest::fromArray($input);
        $this->assertSame(ReconciliationKind::EvidenceIncomplete, $inbox->trace($request, $parent)->kind);
        $this->assertSame(ReconciliationKind::DuplicateObserved, $inbox->trace($request, $parent)->kind);
        $input['intent']['action'] = 'update';
        $this->assertSame(ReconciliationKind::ConflictObserved,
            $inbox->trace(\App\Core\Async\AsyncAuthorityRequest::fromArray($input), $parent)->kind);
        $this->assertStringNotContainsString(AsyncCases::OPERATION, json_encode($inbox->trace($request, $parent)->signal()));
    }
}
