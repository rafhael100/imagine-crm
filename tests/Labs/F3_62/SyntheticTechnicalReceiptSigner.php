<?php
declare(strict_types=1);

namespace Tests\Labs\F3_62;

use App\Core\Leads\SignedTechnicalReceiptCandidateVerifier;

/** CI-only signer: never import into runtime or operational HTTP routes. */
final class SyntheticTechnicalReceiptSigner
{
    public static function sign(array $frame,string $key,int $now):array
    {
        $hex=bin2hex(random_bytes(16));
        $hex[12]='4';
        $hex[16]=dechex((hexdec($hex[16])&3)|8);
        $nonce=substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4)
            .'-'.substr($hex,16,4).'-'.substr($hex,20,12);
        $receipt=[
            'schema'=>'imagine.crm.f362.synthetic-technical-receipt.v1',
            'organization_reference'=>$frame['organization_reference'],
            'binding_reference'=>$frame['binding_reference'],
            'submission_reference'=>$frame['submission_reference'],
            'site_reference'=>$frame['site_reference'],
            'form_reference'=>$frame['form_reference'],
            'destination_reference'=>$frame['destination_reference'],
            'state'=>'SYNTHETIC_COMMITTED_NO_REAL_LEAD',
            'issued_at'=>$now,
            'expires_at'=>$now+60,
            'nonce'=>$nonce,
            'mac'=>'',
        ];
        $receipt['mac']=hash_hmac('sha256',
            SignedTechnicalReceiptCandidateVerifier::canonical($receipt),$key);
        return $receipt;
    }
}
