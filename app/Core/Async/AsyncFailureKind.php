<?php

declare(strict_types=1);

namespace App\Core\Async;

/** Internal laboratory taxonomy, NEVER an authorization or client response code. */
enum AsyncFailureKind: string
{
    case ContractInvalid = 'contract_invalid';
    case AuthorityUnproven = 'authority_unproven';
    case SessionRevoked = 'session_revoked';
    case SessionUnproven = 'session_unproven';
    case MembershipDenied = 'membership_denied';
    case InfrastructureUnavailable = 'infrastructure_unavailable';
    case ContextContamination = 'context_contamination';
    case ConcurrencyBlocked = 'concurrency_blocked';
    case UnexpectedFailure = 'unexpected_failure';
    case ReconciliationNeeded = 'reconciliation_needed';
    case ObserverUnavailable = 'observer_unavailable';
    case LaboratoryDisabled = 'laboratory_disabled';
}
