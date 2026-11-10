<?php

declare(strict_types=1);

namespace App\Core\Portability;

/**
 * A comparison with a caller-supplied digest, NEVER a trusted backup receipt.
 *
 * Nothing in this data object authenticates an origin, proves completeness,
 * authorizes a restore, grants data access or confirms tenant isolation.
 */
final readonly class SyntheticBackupIntegrityAssessment
{
    public function __construct(public string $reasonCode) {}

    public function matchesSelfDeclaredDigest(): bool
    {
        return $this->reasonCode === 'SELF_CLAIM_MATCH_UNTRUSTED';
    }

    public function canRestore(): bool
    {
        return false;
    }

    public function canAcceptAsVerifiedBackup(): bool
    {
        return false;
    }

    public function canDeleteOriginal(): bool
    {
        return false;
    }
}
