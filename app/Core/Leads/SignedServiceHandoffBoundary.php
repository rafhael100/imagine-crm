<?php
declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Candidate Core boundary for signed technical handoff frames.
 * Verifying a MAC and server-pinned metadata does NOT authenticate a customer,
 * create a lead, verify consent, claim a nonce or authorize deployment.
 * Deliberately not connected to any public/controller/queue entry point.
 */
final class SignedServiceHandoffBoundary
{
    private const FRAME_KEYS=[
        'protocol','schema_version','organization_reference','binding_reference',
        'submission_reference','site_reference','form_reference','destination_reference',
    ];
    private const BINDING_KEYS=[
        'organization_reference','binding_reference','site_reference',
        'form_reference','destination_reference',
    ];
    private const REQUEST_DOMAIN='imagine.portal.operational-preparation.v1';

    public function inspect(
        array $frame,array $trustedServerBinding,array $auth,string $requestKey,int $now
    ):array {
        if(array_keys($frame)!==self::FRAME_KEYS
           ||($frame['protocol']??null)!=='imagine.lead-intake'
           ||($frame['schema_version']??null)!==1
           ||!self::uuid($frame['submission_reference']??null)) {
            return self::denied('FRAME_CONTRACT_DENIED');
        }
        if(array_keys($trustedServerBinding)!==self::BINDING_KEYS)
            return self::denied('TRUSTED_SERVER_BINDING_REQUIRED');
        foreach(self::BINDING_KEYS as $field){
            if(!self::uuid($frame[$field]??null)
              ||!self::uuid($trustedServerBinding[$field]??null)
              ||!hash_equals($trustedServerBinding[$field],$frame[$field])) {
                return self::denied('SERVER_TENANT_CONTEXT_DENIED');
            }
        }
        if(strlen($requestKey)<32||strlen($requestKey)>128)
            return self::denied('SERVICE_KEY_UNAVAILABLE');
        if(array_keys($auth)!==['issued_at','expires_at','nonce','mac'])
            return self::denied('SIGNED_REQUEST_DENIED');
        $iat=$auth['issued_at'];$exp=$auth['expires_at'];
        $nonce=$auth['nonce'];$mac=$auth['mac'];
        if(!is_int($iat)||!is_int($exp)||$now<=0||$iat<=0
          ||$iat<$now-120||$iat>$now+30||$exp<=$now||$exp<=$iat
          ||$exp>$iat+120||!self::uuid($nonce)||!is_string($mac)
          ||preg_match('/\A[a-f0-9]{64}\z/D',$mac)!==1) {
            return self::denied('SIGNED_REQUEST_DENIED');
        }
        $expected=hash_hmac('sha256',self::canonical($frame,$iat,$exp,$nonce),$requestKey);
        if(!hash_equals($expected,$mac))return self::denied('SIGNED_REQUEST_DENIED');
        return self::denied('SIGNED_TECHNICAL_FRAME_ONLY');
    }

    public static function canonical(array $frame,int $iat,int $exp,string $nonce):string {
        $parts=[self::REQUEST_DOMAIN];
        foreach(self::BINDING_KEYS as $field)$parts[]=$frame[$field];
        $parts[]=$frame['submission_reference'];
        array_push($parts,(string)$iat,(string)$exp,$nonce);
        return implode("\n",$parts);
    }
    private static function uuid(mixed $v):bool {
        return is_string($v)
          && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D',$v)===1
          && $v!=='00000000-0000-0000-0000-000000000000';
    }
    private static function denied(string $reason):array {
        return [
          'schema'=>'imagine.crm.signed-handoff-candidate.v1',
          'reasonCode'=>$reason,
          'authenticatedServicePrincipal'=>false,
          'authenticatedOperationalTenant'=>false,
          'atomicNonceClaimed'=>false,
          'realLeadPersisted'=>false,
          'canAcknowledgeVisitor'=>false,
          'canDeploy'=>false,
        ];
    }
}
