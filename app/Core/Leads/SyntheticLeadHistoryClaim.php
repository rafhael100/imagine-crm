<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * Untrusted, metadata-only event CLAIM for an isolated history laboratory.
 *
 * UUID shapes are not proof of tenant ownership, record existence or actor
 * authentication. Kinds/positions are fixtures, never a business event schema.
 * No notes, contents, contact fields, credentials or consent are accepted.
 */
final readonly class SyntheticLeadHistoryClaim
{
    public string $tenantReference;
    public string $leadReference;
    public string $eventReference;
    public ?string $claimedActorReference;

    public function __construct(
        string $tenantReference,
        string $leadReference,
        string $eventReference,
        ?string $claimedActorReference,
        public int $claimedPosition,
        public string $claimedKind,
    ) {
        $this->tenantReference = self::uuid($tenantReference);
        $this->leadReference = self::uuid($leadReference);
        $this->eventReference = self::uuid($eventReference);
        $this->claimedActorReference = $claimedActorReference === null
            ? null
            : self::uuid($claimedActorReference);

        if ($claimedPosition < 1 || $claimedPosition > 1000000
            || !in_array($claimedKind, [
                'created', 'assigned', 'note_added', 'stage_changed', 'archived', 'restored',
            ], true)) {
            throw new InvalidArgumentException('Invalid synthetic history claim.');
        }
    }

    private static function uuid(string $value): string
    {
        if (preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD', $value) !== 1
            || strtolower($value) === '00000000-0000-0000-0000-000000000000') {
            throw new InvalidArgumentException('Invalid synthetic history claim.');
        }

        return strtolower($value);
    }
}
