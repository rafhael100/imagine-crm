<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Descriptive identity of a delivery, never proof of tenancy or authorization.
 *
 * Its three UUIDs MUST be independently verified against a trusted server-side
 * integration binding before any persistence or business action is considered.
 * A retry can reuse the exact same tuple without merging separate submissions
 * merely because their contact fields look alike.
 */
final readonly class LeadSubmissionIdentity
{
    public string $organizationReference;
    public string $bindingReference;
    public string $submissionReference;

    public function __construct(
        string $organizationReference,
        string $bindingReference,
        string $submissionReference,
    ) {
        $this->organizationReference = self::uuid($organizationReference);
        $this->bindingReference = self::uuid($bindingReference);
        $this->submissionReference = self::uuid($submissionReference);
    }

    public function sameLogicalDeliveryAs(self $other): bool
    {
        return $this->organizationReference === $other->organizationReference
            && $this->bindingReference === $other->bindingReference
            && $this->submissionReference === $other->submissionReference;
    }

    private static function uuid(string $value): string
    {
        if (!preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/i', $value)
            || strtolower($value) === '00000000-0000-0000-0000-000000000000') {
            throw new InvalidArgumentException('Referencia de submissao invalida.');
        }

        return strtolower($value);
    }
}
