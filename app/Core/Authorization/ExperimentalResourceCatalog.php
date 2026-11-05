<?php

declare(strict_types=1);

namespace App\Core\Authorization;

/** Closed, immutable DESCRIPTIONS of synthetic test cases. No business grants. */
final class ExperimentalResourceCatalog
{
    public const POLICY_VERSION = 'lab-deny-v1';
    private const FIELDS = [
        'lab_id' => 'F-ID', 'lab_status' => 'F-BIZ', 'lab_person' => 'F-PII',
        'lab_restricted' => 'F-RES', 'lab_consent' => 'F-CONS', 'lab_amount' => 'F-FIN',
        'lab_secret' => 'F-SEC', 'lab_evidence' => 'F-EVID', 'lab_artifact' => 'F-ART',
    ];

    public function describes(AuthorizationIntent $intent): bool
    {
        if ($intent->schemaVersion !== 1 || $intent->policyVersion !== self::POLICY_VERSION
            || $intent->resource !== 'lab.record'
            || !in_array($intent->action, ['read', 'search', 'update', 'export', 'report', 'execute'], true)) {
            return false;
        }
        foreach ($intent->fields as $field => $classification) {
            if ((self::FIELDS[$field] ?? null) !== $classification) {
                return false;
            }
        }

        return true; // A schema match, NEVER an authorization decision.
    }
}
