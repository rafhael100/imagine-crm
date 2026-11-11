<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-79: classify three unverified technical reference claims per attempt.
 *
 * This is a structural contradiction detector, NOT server-authenticated
 * binding or service identity. It never admits or retries real deliveries.
 */
final class SyntheticSubmissionBindingClaimInspector
{
    private const REQUIRED = [
        'organization_reference',
        'submission_reference',
        'binding_reference',
    ];

    public function compare(mixed $first, mixed $second): SyntheticSubmissionClaimAssessment
    {
        if (!self::valid($first) || !self::valid($second)) {
            return new SyntheticSubmissionClaimAssessment('CLAIM_INVALID');
        }

        $basic = new SyntheticSubmissionClaimInspector();
        $left = [
            'organization_reference' => $first['organization_reference'],
            'submission_reference' => $first['submission_reference'],
        ];
        $right = [
            'organization_reference' => $second['organization_reference'],
            'submission_reference' => $second['submission_reference'],
        ];
        $relation = $basic->compare($left, $right)->reasonCode;

        // Even an identical binding claim is not authenticated. This
        // comparison merely spots inconsistent untrusted strings.
        if ($relation !== 'SAME_SUBMISSION_CLAIM_UNVERIFIED') {
            return new SyntheticSubmissionClaimAssessment($relation);
        }

        if (strcasecmp($first['binding_reference'], $second['binding_reference']) !== 0) {
            return new SyntheticSubmissionClaimAssessment('SUBMISSION_BINDING_CLAIM_CONFLICT');
        }

        return new SyntheticSubmissionClaimAssessment(
            'SAME_SUBMISSION_SAME_BINDING_CLAIM_UNVERIFIED',
        );
    }

    private static function valid(mixed $value): bool
    {
        if (!is_array($value) || count($value) !== count(self::REQUIRED)) {
            return false;
        }

        foreach (self::REQUIRED as $key) {
            if (!array_key_exists($key, $value) || !self::validUuid($value[$key])) {
                return false;
            }
        }

        return true;
    }

    private static function validUuid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $value) === 1
            && strtolower($value) !== '00000000-0000-0000-0000-000000000000';
    }
}
