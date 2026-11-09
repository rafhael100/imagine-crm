<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Immutable synthetic request identity + original destination assertions.
 *
 * Logical ledger scope is (organization, submission), NOT contact content.
 * An existing scope must pin the binding/site/form/destination and reject
 * a changed destination instead of silently creating a fresh delivery.
 *
 * No UUID here has been authenticated or resolved against a trusted service.
 */
final readonly class SyntheticIdempotencyClaim
{
    public string $siteReference;
    public string $formReference;
    public string $destinationReference;

    public function __construct(
        public LeadSubmissionIdentity $identity,
        string $siteReference,
        string $formReference,
        string $destinationReference,
    ) {
        $this->siteReference = self::uuid($siteReference);
        $this->formReference = self::uuid($formReference);
        $this->destinationReference = self::uuid($destinationReference);
    }

    public function sameLogicalLedgerScopeAs(self $other): bool
    {
        return $this->identity->organizationReference === $other->identity->organizationReference
            && $this->identity->submissionReference === $other->identity->submissionReference;
    }

    public function samePinnedContextAs(self $other): bool
    {
        return $this->identity->sameLogicalDeliveryAs($other->identity)
            && $this->siteReference === $other->siteReference
            && $this->formReference === $other->formReference
            && $this->destinationReference === $other->destinationReference;
    }

    /**
     * Opaque lab comparison hint only. Not a secret, signature or access token.
     */
    public function syntheticLedgerFingerprint(): string
    {
        return hash('sha256', implode("\x00", [
            'imagine-synthetic-ledger-v1',
            $this->identity->organizationReference,
            $this->identity->submissionReference,
        ]));
    }

    private static function uuid(string $value): string
    {
        if (preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $value) !== 1
            || strtolower($value) === '00000000-0000-0000-0000-000000000000') {
            throw new InvalidArgumentException('Synthetic scope reference invalid.');
        }

        return strtolower($value);
    }
}
