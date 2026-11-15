<?php

declare(strict_types=1);

namespace App\Support\Synthetic;

/**
 * F242: key-id is an UNTRUSTED lookup hint for a globally unique CRM record.
 * Never use tenant_ref or organization_id from HTTP/claims as an authority.
 *
 * Only the original pinned Agents Ed25519 verifier can establish that the
 * caller controls the corresponding secret key, then atomic PG nonce is
 * claimed. This returns synthetic diagnostic evidence, not CRM access.
 */
final class AiaPgTenantBoundServiceProofLab
{
    /**
     * @return ?array{tenant_ref:string,organization_id:string}
     */
    public static function verifyAndConsume(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        int $now,
        AiaPgServiceTenantBindingLab $bindings,
        AiaTrustedPublicKeySource $keyring,
        AiaNonceClaimSource $nonces,
    ): ?array {
        try {
            if (strlen($canonicalRequest) > 2048
                || $canonicalRequest === ''
                || strlen($canonicalClaims) > 1024
                || $canonicalClaims === ''
                || strlen($signatureBase64) > 128
                || $signatureBase64 === '') {
                return null;
            }
            $claims = json_decode($canonicalClaims, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($claims) || !is_string($claims['key_id'] ?? null)
                || !is_string($claims['tenant_ref'] ?? null)
                || json_encode(
                    $claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                ) !== $canonicalClaims) {
                return null;
            }

            // The only pre-authenticated input used by routing is a key-id
            // hint. Organization comes from immutable-ish server-owned table,
            // not from the signed claim itself (which is untrusted until
            // signature verification) or from app.tenant_id.
            $bound = $bindings->resolveByKeyId($claims['key_id']);
            if ($bound === null || !hash_equals(
                $bound['tenant_ref'], $claims['tenant_ref']
            )) {
                return null;
            }

            if (!AiaPgEd25519LabBridge::matchesWithServerKeyring(
                $canonicalRequest, $canonicalClaims, $signatureBase64,
                $bound['tenant_ref'], $now, $keyring, $nonces,
            )) {
                return null;
            }
            return $bound; // diagnostic ONLY: do not use as an ACL
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * F243: use server-owned binding while the PG row remains locked.
     * A concurrent binding revocation UPDATE cannot commit between
     * tenant resolution, signature verification and anti-replay insert.
     * A true return is only evidence; it never grants any CRM operation.
     *
     * @return ?array{tenant_ref:string,organization_id:string}
     */
    public static function verifyWithStableBinding(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        int $now,
        AiaPgLockedServiceTenantBindingLab $bindings,
        AiaTrustedPublicKeySource $keyring,
        AiaNonceClaimSource $nonces,
    ): ?array {
        try {
            if ($canonicalRequest === '' || strlen($canonicalRequest) > 2048
                || $canonicalClaims === '' || strlen($canonicalClaims) > 1024
                || $signatureBase64 === '' || strlen($signatureBase64) > 128) {
                return null;
            }
            $claims = json_decode($canonicalClaims, true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($claims) || !is_string($claims['key_id'] ?? null)
                || !is_string($claims['tenant_ref'] ?? null)
                || json_encode(
                    $claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                ) !== $canonicalClaims) {
                return null;
            }
            return $bindings->withinLockedBinding(
                $claims['key_id'],
                static function (array $bound) use (
                    $canonicalRequest, $canonicalClaims, $signatureBase64,
                    $now, $claims, $keyring, $nonces
                ): bool {
                    return hash_equals($bound['tenant_ref'], $claims['tenant_ref'])
                        && AiaPgEd25519LabBridge::matchesWithServerKeyring(
                            $canonicalRequest, $canonicalClaims,
                            $signatureBase64, $bound['tenant_ref'], $now,
                            $keyring, $nonces,
                        );
                }
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * F244: stable peer binding + stable CRM public key while consuming
     * a nonce. Locks are acquired in deterministic order:
     * service binding -> public key -> nonce INSERT.
     * Returns only diagnostic identity, NEVER an authorization to read.
     *
     * @return ?array{tenant_ref:string,organization_id:string}
     */
    public static function verifyWithStableIdentity(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        int $now,
        AiaPgLockedServiceTenantBindingLab $bindings,
        AiaPgLockedServiceKeyringLab $keyring,
        AiaNonceClaimSource $nonces,
    ): ?array {
        try {
            if ($canonicalRequest === '' || strlen($canonicalRequest) > 2048
                || $canonicalClaims === '' || strlen($canonicalClaims) > 1024
                || $signatureBase64 === '' || strlen($signatureBase64) > 128) {
                return null;
            }
            $claims = json_decode($canonicalClaims,true,4,JSON_THROW_ON_ERROR);
            if (!is_array($claims) || !is_string($claims['key_id'] ?? null)
                || !is_string($claims['tenant_ref'] ?? null)
                || json_encode(
                    $claims,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                ) !== $canonicalClaims) {
                return null;
            }
            return $bindings->withinLockedBinding(
                $claims['key_id'],
                static function (array $bound) use (
                    $claims,$canonicalRequest,$canonicalClaims,
                    $signatureBase64,$now,$keyring,$nonces
                ): bool {
                    if (!hash_equals($bound['tenant_ref'],$claims['tenant_ref'])) {
                        return false;
                    }
                    return $keyring->withinLockedPublicKey(
                        $claims['key_id'],$bound['tenant_ref'],
                        static fn (array $trusted): bool =>
                            AiaPgEd25519LabBridge::matchesLabFixture(
                                $canonicalRequest,$canonicalClaims,
                                $signatureBase64,$trusted,
                                $bound['tenant_ref'],$now,$nonces
                            )
                    );
                }
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array{contract:string,state:string,projection:null,real_authorization:false,external_calls:0} */
    public static function inspectDenied(
        string $canonicalRequest,
        string $canonicalClaims,
        string $signatureBase64,
        int $now,
        AiaPgServiceTenantBindingLab $bindings,
        AiaTrustedPublicKeySource $keyring,
        AiaNonceClaimSource $nonces,
    ): array {
        self::verifyAndConsume(
            $canonicalRequest, $canonicalClaims, $signatureBase64, $now,
            $bindings, $keyring, $nonces,
        );
        return AiaReadOnlyMockBoundary::inspectCanonicalJson($canonicalRequest);
    }
}
