<?php
declare(strict_types=1);

namespace Tests\Labs\F3_55;

use PDO;
use Throwable;

/**
 * Read-only, signed metadata reconciliation on a disposable PostgreSQL fixture.
 * No Laravel route, actual lead, visitor ACK or permission to re-send.
 * The PDO connection must already be authenticated with a least-privilege role.
 */
final class SignedSyntheticReadback
{
    private const FRAME_KEYS = [
        'protocol','schema_version','organization_reference','binding_reference',
        'submission_reference','site_reference','form_reference','destination_reference',
    ];
    private const BINDING_KEYS = [
        'organization_reference','binding_reference','site_reference',
        'form_reference','destination_reference',
    ];
    private const DOMAIN = 'imagine.crm.f355.synthetic-readback.v1';

    public function inspect(
        PDO $db, array $frame, array $trustedBinding,
        array $auth, string $lookupKey, int $now
    ): array {
        if (array_keys($frame) !== self::FRAME_KEYS
            || ($frame['protocol'] ?? null) !== 'imagine.lead-intake'
            || ($frame['schema_version'] ?? null) !== 1
            || !self::validUuid($frame['submission_reference'] ?? null)) {
            return self::result('CONTRACT_DENIED');
        }
        if (array_keys($trustedBinding) !== self::BINDING_KEYS) {
            return self::result('SERVER_BINDING_REQUIRED');
        }
        foreach (self::BINDING_KEYS as $field) {
            if (!self::validUuid($frame[$field] ?? null)
                || !self::validUuid($trustedBinding[$field] ?? null)
                || !hash_equals($trustedBinding[$field],$frame[$field])) {
                return self::result('SERVER_CONTEXT_DENIED');
            }
        }
        if (strlen($lookupKey) < 32 || strlen($lookupKey) > 128) {
            return self::result('READBACK_KEY_UNAVAILABLE');
        }
        if (array_keys($auth) !== ['issued_at','expires_at','nonce','mac']) {
            return self::result('READBACK_AUTH_DENIED');
        }
        $iat=$auth['issued_at'];
        $exp=$auth['expires_at'];
        $nonce=$auth['nonce'];
        $mac=$auth['mac'];
        if (!is_int($iat) || !is_int($exp)
            || $now <= 0 || $iat <= 0 || $iat < $now-120 || $iat > $now+30
            || $exp <= $now || $exp <= $iat || $exp > $iat+120
            || !self::validUuid($nonce)
            || !is_string($mac) || preg_match('/\A[a-f0-9]{64}\z/D',$mac)!==1) {
            return self::result('READBACK_AUTH_DENIED');
        }
        $message=self::message($frame,$iat,$exp,$nonce);
        if (!hash_equals(hash_hmac('sha256',$message,$lookupKey),$mac)) {
            return self::result('READBACK_AUTH_DENIED');
        }
        try {
            $stmt=$db->prepare(
                'SELECT delivery_state FROM crm_f353_nonce_lab.synthetic_delivery_receipts
                 WHERE organization_reference=CAST(:org AS uuid)
                 AND submission_reference=CAST(:sub AS uuid) LIMIT 1'
            );
            $stmt->execute([
                'org'=>$trustedBinding['organization_reference'],
                'sub'=>$frame['submission_reference'],
            ]);
            $state=$stmt->fetchColumn();
        } catch (Throwable) {
            return self::result('READBACK_UNAVAILABLE_HOLD_UNKNOWN');
        }
        return $state==='SYNTHETIC_COMMITTED_NO_REAL_LEAD'
            ? self::result('SYNTHETIC_METADATA_FOUND_NO_REAL_LEAD',true)
            : self::result('NOT_FOUND_HOLD_UNKNOWN');
    }

    public static function message(array $frame,int $iat,int $exp,string $nonce): string
    {
        return implode("\n",[
            self::DOMAIN,
            ...array_map(static fn(mixed $v):string=>(string)$v,array_values($frame)),
            (string)$iat,(string)$exp,$nonce
        ]);
    }

    private static function validUuid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D',$value)===1
            && $value!=='00000000-0000-0000-0000-000000000000';
    }

    private static function result(string $reason,bool $found=false):array
    {
        return [
            'schema'=>'imagine.crm.f355.synthetic-readback-result.v1',
            'reasonCode'=>$reason,
            'syntheticMetadataLocated'=>$found,
            'realCrmLeadPersisted'=>false,
            'tenantAuthenticatedForProduction'=>false,
            'canAcknowledgeVisitor'=>false,
            'canAutoRetryUnknownOutcome'=>false,
            'canDeploy'=>false,
        ];
    }
}
