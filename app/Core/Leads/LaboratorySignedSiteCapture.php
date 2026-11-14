<?php

declare(strict_types=1);

namespace App\Core\Leads;

use App\Core\Identity\PrincipalReference;
use Illuminate\Support\Str;
use LogicException;

/**
 * PG05: a CI-ONLY signed envelope verification seam, not a production webhook.
 *
 * Synthetic key is an openly visible disposable CI fixture. There is NO
 * operational source authentication, key rotation, server-side authorized
 * binding, consent, trusted time source, or public endpoint. Neither a
 * valid HMAC nor a PrincipalReference permits real business effects.
 */
final readonly class LaboratorySignedSiteCapture
{
    private const DOMAIN = 'imagine.crm.lab.sites-capture.v1';
    private const MAX_TTL_SECONDS = 300;
    private const FUTURE_SKEW_SECONDS = 30;

    public function __construct(
        private LaboratoryTenantLeadLedger $ledger,
        private string $ciSigningKey,
    ) {}

    /**
     * @return array{status_code:string,can_publish:false,can_contact:false,can_activate:false}
     */
    public function captureSigned(
        PrincipalReference $principal,
        string $organization,
        string $leadId,
        string $syntheticTitle,
        string $sourceCode,
        SyntheticIdempotencyClaim $claim,
        string $nonce,
        int $issuedAt,
        int $expiresAt,
        int $now,
        string $signature,
    ): array {
        $this->assertCiOnly();

        if (!Str::isUuid($organization)
            || strtolower($organization) !== $claim->identity->organizationReference
            || !Str::isUuid($leadId)
            || strtolower($leadId) === '00000000-0000-0000-0000-000000000000'
            || preg_match('/\A[A-Z0-9][A-Z0-9_-]{2,63}\z/D', $syntheticTitle) !== 1
            || !in_array($sourceCode, ['sites', 'landing', 'onepage'], true)
            || preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $nonce) !== 1
            || strtolower($nonce) === '00000000-0000-0000-0000-000000000000'
            || preg_match('/\A[0-9a-f]{64}\z/D', $signature) !== 1
            || $issuedAt <= 0
            || $expiresAt <= $issuedAt
            || $expiresAt - $issuedAt > self::MAX_TTL_SECONDS
            || $now <= 0
            || $issuedAt > $now + self::FUTURE_SKEW_SECONDS
            || $expiresAt <= $now) {
            return self::denied();
        }

        $payload = self::canonicalForCi(
            $claim, $leadId, $syntheticTitle, $sourceCode, $nonce,
            $issuedAt, $expiresAt,
        );
        $expected = hash_hmac('sha256', $payload, $this->ciSigningKey);
        if (!hash_equals($expected, $signature)) {
            return self::denied();
        }

        // Validity of this synthetic MAC is only a laboratory integrity check.
        // Membership/RLS and immutable nonce reservation remain in the ledger.
        return $this->ledger->captureSynthetic(
            $principal, $organization, $leadId,
            $syntheticTitle, $sourceCode, $claim, strtolower($nonce),
        );
    }

    /**
     * Public ONLY to construct deterministic synthetic test vectors.
     * A canonical string is NOT proof of origin or authentication.
     * Fields are fixed-format, so newline delimiters are unambiguous.
     */
    public static function canonicalForCi(
        SyntheticIdempotencyClaim $claim,
        string $leadId,
        string $syntheticTitle,
        string $sourceCode,
        string $nonce,
        int $issuedAt,
        int $expiresAt,
    ): string {
        return implode("\n", [
            self::DOMAIN,
            $claim->identity->organizationReference,
            $claim->identity->bindingReference,
            $claim->identity->submissionReference,
            $claim->siteReference,
            $claim->formReference,
            $claim->destinationReference,
            strtolower($leadId),
            $syntheticTitle,
            $sourceCode,
            strtolower($nonce),
            (string) $issuedAt,
            (string) $expiresAt,
        ]);
    }

    private function assertCiOnly(): void
    {
        $configured = getenv('CRM_LEADS_SIGNING_KEY_CI');
        if (!app()->environment('testing')
            || getenv('CI') !== 'true'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || getenv('CRM_LEADS_LAB') !== 'synthetic-only'
            || !is_string($configured)
            || strlen($configured) < 32
            || !hash_equals($configured, $this->ciSigningKey)) {
            throw new LogicException('Verificacao sintética de origem disponivel somente no CI descartavel.');
        }
    }

    private static function denied(): array
    {
        return [
            'status_code' => 'synthetic_integrity_denied',
            'can_publish' => false,
            'can_contact' => false,
            'can_activate' => false,
        ];
    }
}
