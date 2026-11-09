<?php

declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Pure HMAC-SHA256 exercise for versioned, synthetic lead METADATA.
 *
 * This code does not validate live API origins, membership, KMS-managed
 * secrets, response provenance, transport TLS, payload authenticity,
 * authorized binding, nonce uniqueness, clock trust, or CRM persistence.
 * It performs NO I/O and MUST NOT be wired to an operational endpoint.
 */
final class SyntheticLeadReceiptMacInspector
{
    private const LAB_DOMAIN = 'imagine.lead-intake.synthetic-receipt.v1';
    private const MAX_TTL_SECONDS = 300;
    private const FUTURE_SKEW_SECONDS = 30;

    public function inspect(
        SyntheticIdempotencyClaim $expected,
        SyntheticIdempotencyClaim $reported,
        int $issuedAt,
        int $expiresAt,
        int $now,
        mixed $nonce,
        mixed $signature,
        string $syntheticKey,
    ): SyntheticLeadReceiptMacAssessment {
        if (!$expected->samePinnedContextAs($reported)) {
            return new SyntheticLeadReceiptMacAssessment('PINNED_CONTEXT_MISMATCH');
        }

        if (!$this->validWindow($issuedAt, $expiresAt, $now)) {
            return new SyntheticLeadReceiptMacAssessment('TIME_WINDOW_INVALID');
        }

        if (!is_string($nonce)
            || preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D', $nonce) !== 1
            || $nonce === '00000000-0000-0000-0000-000000000000') {
            return new SyntheticLeadReceiptMacAssessment('NONCE_FORMAT_INVALID');
        }

        if (!is_string($signature)
            || preg_match('/\A[0-9a-f]{64}\z/D', $signature) !== 1
            || strlen($syntheticKey) < 32) {
            return new SyntheticLeadReceiptMacAssessment('CRYPTO_INPUT_INVALID');
        }

        $message = implode("\n", [
            self::LAB_DOMAIN,
            $reported->identity->organizationReference,
            $reported->identity->bindingReference,
            $reported->identity->submissionReference,
            $reported->siteReference,
            $reported->formReference,
            $reported->destinationReference,
            (string) $issuedAt,
            (string) $expiresAt,
            $nonce,
        ]);
        $expectedMac = hash_hmac('sha256', $message, $syntheticKey);

        if (!hash_equals($expectedMac, $signature)) {
            return new SyntheticLeadReceiptMacAssessment('MAC_MISMATCH');
        }

        // MAC consistency on a supplied key is NOT origin authentication:
        // the key source, privileges, binding, anti-replay ledger, transport,
        // and durable storage are all outside this laboratory.
        return new SyntheticLeadReceiptMacAssessment('LAB_MAC_MATCH_UNTRUSTED');
    }

    private function validWindow(int $issuedAt, int $expiresAt, int $now): bool
    {
        return $issuedAt > 0
            && $now > 0
            && $expiresAt > $issuedAt
            && $expiresAt - $issuedAt <= self::MAX_TTL_SECONDS
            && $issuedAt <= $now + self::FUTURE_SKEW_SECONDS
            && $expiresAt > $now;
    }
}
