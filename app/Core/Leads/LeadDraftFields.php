<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Optional display/contact candidates, not an ingestion or form validation rule.
 *
 * The Imagine Site Form "name + at least one contact" contract is channel-specific
 * and must not be imposed as a universal CRM lead/contact schema. Nothing here
 * establishes consent, deduplication or permission to store personal information.
 */
final readonly class LeadDraftFields
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
    ) {
        foreach ([$name, $email, $phone] as $field) {
            if ($field === null) {
                continue;
            }

            // Detailed e-mail/telephone formats depend on each channel policy.
            if (trim($field) === '' || strlen($field) > 1024
                || preg_match('/[\x00-\x1f\x7f]/', $field)) {
                throw new InvalidArgumentException('Campo de lead invalido.');
            }
        }
    }
}
