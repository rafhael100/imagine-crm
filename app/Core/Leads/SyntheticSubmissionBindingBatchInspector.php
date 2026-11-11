<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-80: scan a bounded sequence of three-key submission/binding CLAIMS.
 *
 * The warnings are neither identity proof nor an idempotency ledger. No I/O.
 */
final class SyntheticSubmissionBindingBatchInspector
{
    private const MAX_CLAIMS = 50;

    public function inspect(mixed $claims): SyntheticSubmissionBindingBatchSummary
    {
        if (!is_array($claims) || !array_is_list($claims)
            || count($claims) < 1 || count($claims) > self::MAX_CLAIMS) {
            self::invalid();
        }

        $inspector = new SyntheticSubmissionBindingClaimInspector();

        // Validate EVERY claim before computing results. Any invalid claim
        // rejects the whole batch without leaking a partial position map.
        foreach ($claims as $claim) {
            if ($inspector->compare($claim, $claim)->reasonCode
                !== 'SAME_SUBMISSION_SAME_BINDING_CLAIM_UNVERIFIED') {
                self::invalid();
            }
        }

        $same = [];
        $binding = [];
        $organization = [];

        // At most 1,225 in-memory comparisons of strictly synthetic claims.
        foreach ($claims as $right => $claim) {
            for ($left = 0; $left < $right; ++$left) {
                $reason = $inspector->compare($claims[$left], $claim)->reasonCode;
                if ($reason === 'SAME_SUBMISSION_SAME_BINDING_CLAIM_UNVERIFIED') {
                    $same[] = ['first' => $left + 1, 'repeated' => $right + 1];
                } elseif ($reason === 'SUBMISSION_BINDING_CLAIM_CONFLICT') {
                    $binding[] = ['first' => $left + 1, 'conflicting' => $right + 1];
                } elseif ($reason === 'SUBMISSION_ORGANIZATION_CLAIM_CONFLICT') {
                    $organization[] = ['first' => $left + 1, 'conflicting' => $right + 1];
                }
            }
        }

        return new SyntheticSubmissionBindingBatchSummary(
            claimsInspected: count($claims),
            sameClaimPositions: $same,
            bindingConflictPositions: $binding,
            organizationConflictPositions: $organization,
        );
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Lote de vinculos tecnicos sinteticos invalido.');
    }
}
