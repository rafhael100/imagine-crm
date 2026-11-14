<?php
declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * PERF-CALC-PII-01 — SYNTHETIC CONTACT BOUNDARY (NO-GO).
 *
 * Exercises the future contact field validation without storing or returning
 * raw names/emails. The only accepted addresses end in .test or .invalid,
 * so REAL visitor contact data cannot enter this laboratory.
 *
 * A fingerprint is NOT user identity, confirmation, proven consent or legal
 * authority; any real integration must replace this with independently
 * authenticated tenant binding, a vault key, lawful retention, verified
 * consent evidence and delivery receipts before it can be enabled.
 */
final class SyntheticCalculatorContactBoundary
{
    /** @return array<string,mixed> */
    public static function inspect(array $candidate, string $disposableCiKey): array
    {
        $fields = [
            'submission_id', 'contact_name', 'contact_email',
            'report_requested', 'marketing_requested', 'privacy_notice_version',
        ];
        $actual = array_keys($candidate);
        sort($actual, SORT_STRING);
        sort($fields, SORT_STRING);
        if ($actual !== $fields) {
            throw new InvalidArgumentException('Unexpected synthetic contact fields.');
        }

        $name = $candidate['contact_name'];
        $email = $candidate['contact_email'];
        if (!is_string($name) || strlen($name) > 100 ||
            preg_match('/\ASample [A-Za-z0-9 ]{2,64}\z/D', $name) !== 1 ||
            !is_string($email) || strlen($email) > 120 ||
            filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
            preg_match('/\A[a-z0-9][a-z0-9._+-]{0,62}@(example\.test|example\.invalid)\z/D', $email) !== 1) {
            throw new InvalidArgumentException('Only fictitious contact fixtures are permitted.');
        }
        if (!is_string($candidate['submission_id']) ||
            preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $candidate['submission_id']) !== 1 ||
            !is_bool($candidate['report_requested']) ||
            !is_bool($candidate['marketing_requested']) ||
            (!$candidate['report_requested'] && !$candidate['marketing_requested']) ||
            $candidate['privacy_notice_version'] !== 'lab-notice-v1' ||
            strlen($disposableCiKey) < 32 ||
            $disposableCiKey !== 'IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123') {
            throw new InvalidArgumentException('Invalid synthetic contact intent/fixture key.');
        }

        return [
            'state' => 'SYNTHETIC_CONTACT_VALIDATION_ONLY',
            'schema' => 'imagine.crm.synthetic-calculator-contact.v1',
            'submission_id' => $candidate['submission_id'],
            // An keyed identifier is generated for metadata-only PG tests.
            // No username/domain can be reconstructed from this response.
            'contact_fingerprint' => hash_hmac('sha256', strtolower($email), $disposableCiKey),
            'report_request_claimed' => $candidate['report_requested'],
            'marketing_interest_claimed' => $candidate['marketing_requested'],
            'notice_version_claimed' => $candidate['privacy_notice_version'],
            'contact_identity_verified' => false,
            'report_requested_by_verified_contact' => false,
            'marketing_consent_proven' => false,
            'can_send_report' => false,
            'can_send_marketing' => false,
            'can_store_pii' => false,
            'can_enroll_nurture' => false,
            'real_crm_lead_created' => false,
        ];
    }
}
