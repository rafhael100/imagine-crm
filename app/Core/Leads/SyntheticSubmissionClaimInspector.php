<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * F3-77: compare logical submission ID claims; NEVER deduplicate a person.
 *
 * Only two syntactic, untrusted UUID claims are considered. The caller is
 * not an authenticated Portal, CRM service, member or tenant. An identical
 * submission reference can be replayed or spoofed, so no positive right
 * or retry acceptance follows from this classification.
 *
 * No payload, signature, email, telephone, name or person fingerprint is
 * accepted, retained or exposed. No I/O.
 */
final class SyntheticSubmissionClaimInspector
{
    private const REQUIRED_KEYS = ['organization_reference', 'submission_reference'];

    public function compare(mixed $first, mixed $second): SyntheticSubmissionClaimAssessment
    {
        if (!self::validClaim($first) || !self::validClaim($second)) {
            return new SyntheticSubmissionClaimAssessment('CLAIM_INVALID');
        }

        // Case normalization only for the HEX representation of a UUID,
        // never email/phone/name nor business data. This is syntax only.
        $sameSubmission = strcasecmp(
            $first['submission_reference'],
            $second['submission_reference'],
        ) === 0;
        $sameOrganizationClaim = strcasecmp(
            $first['organization_reference'],
            $second['organization_reference'],
        ) === 0;

        if ($sameSubmission && !$sameOrganizationClaim) {
            return new SyntheticSubmissionClaimAssessment('SUBMISSION_ORGANIZATION_CLAIM_CONFLICT');
        }

        if ($sameSubmission) {
            return new SyntheticSubmissionClaimAssessment('SAME_SUBMISSION_CLAIM_UNVERIFIED');
        }

        if ($sameOrganizationClaim) {
            // Equal payloads or contacts do not make two different logical
            // submission identifiers the same submission.
            return new SyntheticSubmissionClaimAssessment('DISTINCT_SUBMISSIONS_UNVERIFIED');
        }

        return new SyntheticSubmissionClaimAssessment('UNRELATED_SUBMISSION_CLAIMS');
    }

    private static function validClaim(mixed $value): bool
    {
        if (!is_array($value)
            || count($value) !== count(self::REQUIRED_KEYS)) {
            return false;
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $value)
                || !self::validUuid($value[$key])) {
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
