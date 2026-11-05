<?php

declare(strict_types=1);

namespace App\Core\Async;

use App\Core\Identity\SessionRevoked;
use App\Core\Tenancy\ContaminatedTenantConnection;
use App\Core\Tenancy\MembershipDenied;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

/** No exception, message, SQL, input, personal IDs or stack is retained. */
final readonly class AsyncDiagnostic
{
    private function __construct(public AsyncFailureKind $kind, public string $source, public string $stage) {}

    public static function contract(): self { return new self(AsyncFailureKind::ContractInvalid, 'bridge_contract', 'validation'); }
    public static function authority(): self { return new self(AsyncFailureKind::AuthorityUnproven, 'authority_boundary', 'authority'); }
    public static function disabled(): self { return new self(AsyncFailureKind::LaboratoryDisabled, 'laboratory_gate', 'validation'); }
    public static function reconciliation(): self { return new self(AsyncFailureKind::ReconciliationNeeded, 'synthetic_trace', 'reconciliation'); }

    public static function fromFailure(Throwable $failure): self
    {
        if ($failure instanceof SessionRevoked) { return new self(AsyncFailureKind::SessionRevoked, 'session_registry', 'authority'); }
        if ($failure instanceof MembershipDenied) { return new self(AsyncFailureKind::MembershipDenied, 'membership_boundary', 'authority'); }
        if ($failure instanceof ContaminatedTenantConnection) { return new self(AsyncFailureKind::ContextContamination, 'tenant_connection', 'authority'); }
        if ($failure instanceof AuthenticationException) { return new self(AsyncFailureKind::SessionUnproven, 'human_boundary', 'authority'); }
        if ($failure instanceof AuthorizationException) { return self::authority(); }
        if ($failure instanceof QueryException || $failure instanceof PDOException) {
            // SQLSTATE is compared only to a closed list. Nothing from errorInfo is emitted.
            $state = $failure->errorInfo[0] ?? null;
            if (in_array($state, ['55P03', '40P01', '40001'], true)) {
                return new self(AsyncFailureKind::ConcurrencyBlocked, 'database_driver', 'authority');
            }
            if (in_array($state, ['08000', '08001', '08003', '08004', '08006', '08007', '08P01', '57P01', '57P02', '57P03', '53300'], true)) {
                return new self(AsyncFailureKind::InfrastructureUnavailable, 'database_driver', 'authority');
            }
        }
        if ($failure instanceof \RedisException) {
            return new self(AsyncFailureKind::InfrastructureUnavailable, 'redis_driver', 'authority');
        }
        // No message/stack/previous-exception heuristic and no inferred diagnosis.
        return new self(AsyncFailureKind::UnexpectedFailure, 'boundary_failure', 'authority');
    }
}
