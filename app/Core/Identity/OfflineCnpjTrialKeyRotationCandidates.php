<?php

declare(strict_types=1);

namespace App\Core\Identity;

use InvalidArgumentException;

/**
 * F3-232 — offline internal candidate derivation for historical HMAC keys.
 *
 * Prevents a misleading assumption that one *new-key* digest can detect an
 * earlier trial claimed under a retired key. Derives bounded candidates only.
 * NO database read, atomic uniqueness, registration, owner or Receita proof.
 *
 * WARNING: returned fingerprints remain pseudonymous sensitive identifiers:
 * do not emit to HTTP, UI, logs, telemetry, emails or users.
 */
final class OfflineCnpjTrialKeyRotationCandidates
{
    public const MAX_KEY_VERSIONS = 4;

    /**
     * @param mixed $cnpj Structurally valid numeric/alphanumeric CNPJ
     * @param mixed $keyVersions List 1..4 of strictly increasing
     *   ['version' => int(1..65535), 'secret' => string(32..128 bytes)]
     * @return array<string,mixed> Internal-only pseudonymous lookup candidates
     */
    public function derive(
        #[\SensitiveParameter] mixed $cnpj,
        #[\SensitiveParameter] mixed $keyVersions,
    ): array {
        if (!is_string($cnpj)
            || !is_array($keyVersions) || !array_is_list($keyVersions)
            || $keyVersions === [] || count($keyVersions) > self::MAX_KEY_VERSIONS) {
            self::invalid();
        }

        $lastVersion = 0;
        $secrets = [];
        $out = [];
        $single = new OfflineCnpjTrialHmacBinding();
        foreach ($keyVersions as $entry) {
            if (!is_array($entry) || array_keys($entry) !== ['version', 'secret']
                || !is_int($entry['version'])
                || $entry['version'] < 1 || $entry['version'] > 65535
                || $entry['version'] <= $lastVersion
                || !is_string($entry['secret'])
                || strlen($entry['secret']) < 32 || strlen($entry['secret']) > 128) {
                self::invalid();
            }
            foreach ($secrets as $priorSecret) {
                // Reject accidental same key stored under multiple versions.
                if (hash_equals($priorSecret, $entry['secret'])) {
                    self::invalid();
                }
            }

            try {
                $fingerprint = $single->derive($cnpj, $entry['secret']);
            } catch (InvalidArgumentException) {
                self::invalid();
            }

            $out[] = [
                'version' => $entry['version'],
                'internal_binding' => $fingerprint,
            ];
            $lastVersion = $entry['version'];
            $secrets[] = $entry['secret'];
        }

        return [
            'candidate_count' => count($out),
            'candidate_versions_and_bindings' => $out,
            'current_key_version' => $lastVersion,
            'historical_key_coverage_verified' => false,
            'key_custody_verified' => false,
            'fiscal_owner_verified' => false,
            'atomic_trial_uniqueness_verified' => false,
            'informational_only' => true,
            'can_lookup' => false,
            'can_claim_trial' => false,
            'can_register' => false,
            'can_grant_access' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        // No CNPJ, key, digest or candidate index in errors.
        throw new InvalidArgumentException('Versoes de vinculo CNPJ trial invalidas.');
    }
}
