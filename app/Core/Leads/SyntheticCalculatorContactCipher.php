<?php
declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * PERF-CALC-PII-03 — local encryption laboratory, synthetic contacts ONLY.
 *
 * This is a CI-only demonstration of AES-256-GCM tenant-scoped authenticated
 * encryption and selective recovery for a FUTURE report-delivery processor.
 * It is NOT a vault, credential manager, production DB writer, sender or an
 * authorization service. NEVER return a decrypted contact in HTTP/JSON.
 */
final class SyntheticCalculatorContactCipher
{
    /** @return array<string,mixed> */
    public static function seal(
        array $candidate, string $disposableFixtureKey, string $tenantId,
        string $submissionId, string $keyId, string $keyMaterial
    ): array {
        self::assertContext($tenantId, $submissionId, $keyId, $keyMaterial);
        if (($candidate['submission_id'] ?? null) !== $submissionId) {
            throw new InvalidArgumentException('Synthetic record and envelope submission mismatch.');
        }
        SyntheticCalculatorContactBoundary::inspect($candidate, $disposableFixtureKey);

        $payload = json_encode([
            'contact_name' => $candidate['contact_name'],
            'contact_email' => $candidate['contact_email'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext=openssl_encrypt(
            $payload, 'aes-256-gcm', $keyMaterial, OPENSSL_RAW_DATA,
            $nonce, $tag, self::aad($tenantId,$submissionId,$keyId),16
        );
        if (!is_string($ciphertext) || strlen($tag)!==16) {
            throw new InvalidArgumentException('Cannot seal synthetic contact.');
        }
        // Intent and consent MUST NOT be inferred from possession of ciphertext.
        return [
            'schema' => 'synthetic.calc.contact.aes256gcm.v1',
            'key_id' => $keyId,
            'tenant_id' => $tenantId,
            'submission_id' => $submissionId,
            'nonce_b64' => base64_encode($nonce),
            'tag_b64' => base64_encode($tag),
            'ciphertext_b64' => base64_encode($ciphertext),
            'production_authorized' => false,
            'contact_identity_verified' => false,
            'marketing_consent_proven' => false,
            'can_send_report' => false,
            'can_send_marketing' => false,
        ];
    }

    /**
     * Decrypts only within an explicitly trusted, off-network CI fixture.
     * Never call this on an HTTP request. Production must use vetted KMS,
     * RBAC, consent/identity checks, and auditable access.
     *
     * @return array{contact_name:string,contact_email:string}
     */
    public static function openFixture(
        array $sealed, string $tenantId, string $submissionId,
        string $keyId, string $keyMaterial
    ): array {
        self::assertContext($tenantId,$submissionId,$keyId,$keyMaterial);
        $required = [
            'schema','key_id','tenant_id','submission_id','nonce_b64',
            'tag_b64','ciphertext_b64','production_authorized',
            'contact_identity_verified','marketing_consent_proven',
            'can_send_report','can_send_marketing'
        ];
        $actual=array_keys($sealed);
        sort($actual,SORT_STRING);
        sort($required,SORT_STRING);
        if ($actual!==$required ||
            $sealed['schema']!=='synthetic.calc.contact.aes256gcm.v1' ||
            $sealed['key_id']!==$keyId ||
            $sealed['tenant_id']!==$tenantId ||
            $sealed['submission_id']!==$submissionId ||
            $sealed['production_authorized']!==false ||
            $sealed['contact_identity_verified']!==false ||
            $sealed['marketing_consent_proven']!==false ||
            $sealed['can_send_report']!==false ||
            $sealed['can_send_marketing']!==false) {
            throw new InvalidArgumentException('Wrong tenant, scope, key or synthetic-only flags.');
        }
        $nonce=self::decode($sealed['nonce_b64'],12);
        $tag=self::decode($sealed['tag_b64'],16);
        $ciphertext=self::decode($sealed['ciphertext_b64'],null);
        if (strlen($ciphertext)>1024 || strlen($ciphertext)<10) {
            throw new InvalidArgumentException('Invalid encrypted contact size.');
        }
        $plain=openssl_decrypt(
            $ciphertext,'aes-256-gcm',$keyMaterial,OPENSSL_RAW_DATA,
            $nonce,$tag,self::aad($tenantId,$submissionId,$keyId)
        );
        if (!is_string($plain)) {
            throw new InvalidArgumentException('Authentication failed for synthetic ciphertext.');
        }
        $data=json_decode($plain,true,8,JSON_THROW_ON_ERROR);
        if (!is_array($data) || array_keys($data)!==['contact_name','contact_email'] ||
            !is_string($data['contact_name']) || !is_string($data['contact_email'])) {
            throw new InvalidArgumentException('Unexpected sealed contact shape.');
        }
        // Do not return arbitrary decrypted PII from an untrusted ciphertext.
        if (preg_match('/\ASample [A-Za-z0-9 ]{2,64}\z/D',$data['contact_name'])!==1 ||
            preg_match('/\A[a-z0-9][a-z0-9._+-]{0,62}@(example\.test|example\.invalid)\z/D',
                $data['contact_email'])!==1) {
            throw new InvalidArgumentException('Only fictitious data can be unsealed.');
        }
        return $data;
    }

    private static function aad(string $tenant,string $submission,string $keyId): string
    {
        return implode("\n",['imagine.calculator.synthetic.contact.v1',$tenant,$submission,$keyId]);
    }

    private static function assertContext(
        string $tenantId,string $submissionId,string $keyId,string $keyMaterial
    ): void {
        $uuid='/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D';
        if (preg_match($uuid,$tenantId)!==1 ||
            preg_match($uuid,$submissionId)!==1 ||
            preg_match('/\Alab-key-[a-z0-9-]{4,32}\z/D',$keyId)!==1 ||
            strlen($keyMaterial)!==32) {
            throw new InvalidArgumentException('Invalid synthetic crypto context.');
        }
    }

    private static function decode(mixed $encoded, ?int $length): string
    {
        if (!is_string($encoded) || strlen($encoded)>4096 ||
            preg_match('/\A[A-Za-z0-9+\/]+={0,2}\z/D',$encoded)!==1) {
            throw new InvalidArgumentException('Invalid synthetic base64 field.');
        }
        $decoded=base64_decode($encoded,true);
        if (!is_string($decoded) || base64_encode($decoded)!==$encoded ||
            ($length!==null && strlen($decoded)!==$length)) {
            throw new InvalidArgumentException('Noncanonical ciphertext encoding.');
        }
        return $decoded;
    }
}
