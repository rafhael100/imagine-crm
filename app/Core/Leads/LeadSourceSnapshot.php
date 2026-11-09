<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Optional, unverified source labels supplied to a future intake process.
 *
 * An absent label is null, not guessed. These descriptions DO NOT authenticate
 * a site, prove campaign attribution, or prove a legal basis for processing.
 */
final readonly class LeadSourceSnapshot
{
    public function __construct(
        public ?string $site = null,
        public ?string $page = null,
        public ?string $form = null,
        public ?string $formVersion = null,
        public ?string $campaign = null,
    ) {
        foreach ([$site, $page, $form, $formVersion, $campaign] as $value) {
            self::validate($value);
        }
    }

    private static function validate(?string $value): void
    {
        if ($value === null) {
            return;
        }

        if (trim($value) === '' || strlen($value) > 255
            || preg_match('/[\x00-\x1f\x7f]/', $value)) {
            throw new InvalidArgumentException('Metadado de origem invalido.');
        }
    }
}
