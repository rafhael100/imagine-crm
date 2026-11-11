<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-78: compare at most 50 untrusted technical claims entirely in memory.
 *
 * Reports only positional collisions. Deliberately does not merge contacts,
 * infer consent/identity, allow retry, or accept a visitor's tenant claim.
 * No service, storage, I/O, payload, hashes or person data.
 */
final class SyntheticSubmissionClaimBatchInspector
{
    private const MAX_CLAIMS = 50;

    /** @param mixed $claims */
    public function inspect(mixed $claims): SyntheticSubmissionClaimBatchSummary
    {
        if (!is_array($claims)
            || !array_is_list($claims)
            || count($claims) < 1
            || count($claims) > self::MAX_CLAIMS) {
            self::invalid();
        }

        $pairInspector = new SyntheticSubmissionClaimInspector();

        // Reject the WHOLE batch on malformed claims, never return partial
        // results. Reuse the same field and UUID validation as F3-77.
        foreach ($claims as $claim) {
            if ($pairInspector->compare($claim, $claim)->reasonCode !== 'SAME_SUBMISSION_CLAIM_UNVERIFIED') {
                self::invalid();
            }
        }

        $same = [];
        $cross = [];

        // 50 bounded claims => at most 1,225 pairs. Compare original strings
        // transiently; never hash, store, export or echo any identifiers.
        foreach ($claims as $right => $claim) {
            for ($left = 0; $left < $right; ++$left) {
                $assessment = $pairInspector->compare($claims[$left], $claim);
                if ($assessment->reasonCode === 'SAME_SUBMISSION_CLAIM_UNVERIFIED') {
                    $same[] = ['first' => $left + 1, 'repeated' => $right + 1];
                } elseif ($assessment->reasonCode === 'SUBMISSION_ORGANIZATION_CLAIM_CONFLICT') {
                    $cross[] = ['first' => $left + 1, 'conflicting' => $right + 1];
                }
            }
        }

        return new SyntheticSubmissionClaimBatchSummary(
            claimsInspected: count($claims),
            sameOrganizationClaimPositions: $same,
            crossOrganizationClaimPositions: $cross,
        );
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Lote de referencias sinteticas invalido.');
    }
}
