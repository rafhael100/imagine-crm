<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/** Closed laboratory observations, not official incident classifications. */
enum LedgerGap: string
{
    case InvalidSample = 'invalid_sample';
    case CrossTenant = 'cross_tenant_sample';
    case MissingLedger = 'missing_ledger';
    case MissingAttempt = 'missing_attempt';
    case MissingInbox = 'missing_inbox';
    case MissingOutbox = 'missing_outbox';
    case DoubleAssociation = 'double_association';
    case OrphanAttempt = 'orphan_attempt';
    case OrphanAssociation = 'orphan_association';
    case UnexpectedRecord = 'unexpected_record';
    case ApparentDuplicate = 'apparent_duplicate';
    case DigestMismatch = 'digest_mismatch';
    case VersionMismatch = 'version_mismatch';
    case CorrelationMismatch = 'correlation_mismatch';
    case AttemptMismatch = 'attempt_mismatch';
    case InvalidState = 'invalid_state';
    case InvalidValidity = 'invalid_validity';
    case Expired = 'expired_sample';
    case ConflictObserved = 'conflict_observed';
    case SchemaUnavailable = 'schema_unavailable';
    case UnsafeReader = 'unsafe_reader';
    case ReadUnavailable = 'read_unavailable';
    case SampleLimit = 'sample_limit';
    case LaboratoryDisabled = 'laboratory_disabled';
    case PredecessorUnobserved = 'predecessor_unobserved';
    case OlderVersionObserved = 'older_version_observed';
    case SameIntentNewAttempt = 'same_intent_new_attempt';
    case DuplicateAttempt = 'duplicate_attempt';
    case CausalConflict = 'causal_conflict';
}
