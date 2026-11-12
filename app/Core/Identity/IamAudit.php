<?php

declare(strict_types=1);

namespace App\Core\Identity;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** Append-only laboratory security evidence, not the official Auditoria Geral. */
class IamAudit
{
    public const ACTIONS = ['password', 'mfa', 'recovery', 'step_up', 'refresh', 'logout', 'revoke', 'revoke_all', 'provision', 'rotate', 'issue_recovery', 'reconcile', 'anomaly'];

    public function append(Connection $db, string $correlation, string $action, string $outcome, ?string $actor = null, ?string $subject = null, ?string $origin = null): void
    {
        if (!Str::isUuid($correlation) || !in_array($action, self::ACTIONS, true)
            || !in_array($outcome, ['attempted', 'succeeded', 'denied', 'compensated'], true)
            || ($actor !== null && !Str::isUuid($actor)) || ($subject !== null && !Str::isUuid($subject))
            || ($origin !== null && !preg_match('/\A[a-f0-9]{64}\z/D', $origin))) {
            throw new InvalidArgumentException('Invalid IAM audit envelope.');
        }
        // No free-form metadata, exception messages, login, IP, tokens or factors.
        $db->table(IamRegistryTable::name($db, 'crm_iam_audit_events'))->insert([
            'id' => (string) Str::uuid(), 'correlation_id' => $correlation,
            'action' => $action, 'outcome' => $outcome, 'actor_id' => $actor,
            'subject_id' => $subject, 'origin_key' => $origin, 'occurred_at' => now()->getTimestamp(),
        ]);
    }
}
