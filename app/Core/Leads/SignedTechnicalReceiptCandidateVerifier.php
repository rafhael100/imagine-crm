<?php
declare(strict_types=1);

namespace App\Core\Leads;

/**
 * Verifies a synthetic metadata-only receiver attestation.
 * MAC integrity is not proof of an operational CRM commit, live origin,
 * authenticated tenant, customer consent, delivery or go-live approval.
 */
final class SignedTechnicalReceiptCandidateVerifier
{
    private const DOMAIN='imagine.crm.f362.synthetic-receipt.v1';
    private const SCHEMA='imagine.crm.f362.synthetic-technical-receipt.v1';
    private const STATE='SYNTHETIC_COMMITTED_NO_REAL_LEAD';
    private const REFERENCES=[
        'organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'
    ];
    private const RECEIPT_KEYS=[
        'schema','organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference','state',
        'issued_at','expires_at','nonce','mac'
    ];
    private const FRAME_KEYS=[
        'protocol','schema_version','organization_reference','binding_reference',
        'submission_reference','site_reference','form_reference','destination_reference'
    ];

    public function verify(array $expectedFrame,array $receipt,string $receiptKey,int $now):array
    {
        if (array_keys($expectedFrame)!==self::FRAME_KEYS
            ||$expectedFrame['protocol']!=='imagine.lead-intake'
            ||$expectedFrame['schema_version']!==1
            ||array_keys($receipt)!==self::RECEIPT_KEYS
            ||$receipt['schema']!==self::SCHEMA
            ||$receipt['state']!==self::STATE
            ||strlen($receiptKey)<32||strlen($receiptKey)>128) {
            return self::denied('RECEIPT_CONTRACT_DENIED');
        }
        foreach(self::REFERENCES as $name){
            if(!self::uuid($expectedFrame[$name])||!self::uuid($receipt[$name])
                ||!hash_equals($expectedFrame[$name],$receipt[$name])){
                return self::denied('RECEIPT_CONTEXT_DENIED');
            }
        }
        $iat=$receipt['issued_at'];
        $exp=$receipt['expires_at'];
        $nonce=$receipt['nonce'];
        $mac=$receipt['mac'];
        if(!is_int($iat)||!is_int($exp)||$now<=0||$iat<=0
            ||$iat<$now-120||$iat>$now+30
            ||$exp<=$now||$exp<=$iat||$exp>$iat+120
            ||!self::uuid($nonce)
            ||!is_string($mac)||preg_match('/\A[a-f0-9]{64}\z/D',$mac)!==1) {
            return self::denied('RECEIPT_FRESHNESS_DENIED');
        }
        if(!hash_equals(hash_hmac('sha256',self::canonical($receipt),$receiptKey),$mac)){
            return self::denied('RECEIPT_MAC_DENIED');
        }
        return self::denied('SYNTHETIC_RECEIPT_INTEGRITY_ONLY');
    }

    public static function canonical(array $receipt):string
    {
        $fields=[self::DOMAIN,self::SCHEMA];
        foreach(self::REFERENCES as $key)$fields[]=(string)$receipt[$key];
        $fields[]=self::STATE;
        foreach(['issued_at','expires_at','nonce'] as $field)$fields[]=(string)$receipt[$field];
        return implode("\n",$fields);
    }

    private static function uuid(mixed $v):bool
    {
        return is_string($v)
            &&preg_match('/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/D',$v)===1
            &&$v!=='00000000-0000-0000-0000-000000000000';
    }
    private static function denied(string $reason):array
    {
        return [
            'schema'=>'imagine.crm.f362.receipt-assessment.v1',
            'reasonCode'=>$reason,
            'realCrmLeadPersisted'=>false,
            'tenantAuthenticatedForProduction'=>false,
            'canAcknowledgeVisitor'=>false,
            'canAutoRetryUnknownOutcome'=>false,
            'canDeploy'=>false,
        ];
    }
}
