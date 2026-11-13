<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/app/Core/Leads/SignedTechnicalReceiptCandidateVerifier.php';
require_once __DIR__.'/SyntheticTechnicalReceiptSigner.php';

use App\Core\Leads\SignedTechnicalReceiptCandidateVerifier;
use Tests\Labs\F3_62\SyntheticTechnicalReceiptSigner;

$frame=[
    'protocol'=>'imagine.lead-intake','schema_version'=>1,
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'submission_reference'=>'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
$now=1763000000;
$receiptKey=random_bytes(48);
$requestKey=random_bytes(48);
$verifier=new SignedTechnicalReceiptCandidateVerifier();
$receipt=SyntheticTechnicalReceiptSigner::sign($frame,$receiptKey,$now);
$checks=0;
function checkReceipt(array $out,string $reason):void {
    global $checks;
    ++$checks;
    if(($out['reasonCode']??null)!==$reason
       ||($out['schema']??null)!=='imagine.crm.f362.receipt-assessment.v1') {
        throw new RuntimeException('F3-62 unexpected receipt result: '.json_encode($out));
    }
    foreach(['realCrmLeadPersisted','tenantAuthenticatedForProduction',
             'canAcknowledgeVisitor','canAutoRetryUnknownOutcome','canDeploy'] as $field) {
        if(($out[$field]??null)!==false)
            throw new RuntimeException('F3-62 elevated claim: '.$field);
    }
}
checkReceipt($verifier->verify($frame,$receipt,$receiptKey,$now),
    'SYNTHETIC_RECEIPT_INTEGRITY_ONLY');
checkReceipt($verifier->verify($frame,$receipt,$requestKey,$now),'RECEIPT_MAC_DENIED');
checkReceipt($verifier->verify($frame,$receipt,$receiptKey,$now+121),'RECEIPT_FRESHNESS_DENIED');
checkReceipt($verifier->verify($frame,[...$receipt,'mac'=>str_repeat('0',64)],$receiptKey,$now),
    'RECEIPT_MAC_DENIED');
checkReceipt($verifier->verify($frame,[...$receipt,'mac'=>'invalid'],$receiptKey,$now),
    'RECEIPT_FRESHNESS_DENIED');
checkReceipt($verifier->verify($frame,[...$receipt,'state'=>'CRM_LEAD_REAL'],$receiptKey,$now),
    'RECEIPT_CONTRACT_DENIED');
checkReceipt($verifier->verify($frame,[...$receipt,'email'=>'fake@example.invalid'],$receiptKey,$now),
    'RECEIPT_CONTRACT_DENIED');
checkReceipt($verifier->verify($frame,[...$receipt,'nonce'=>'not-uuid'],$receiptKey,$now),
    'RECEIPT_FRESHNESS_DENIED');
checkReceipt($verifier->verify($frame,[...$receipt,'issued_at'=>$now+31],$receiptKey,$now),
    'RECEIPT_FRESHNESS_DENIED');
foreach(['organization_reference','binding_reference','submission_reference',
         'site_reference','form_reference','destination_reference'] as $field) {
    $altered=$receipt;
    $altered[$field]='99999999-9999-4999-8999-999999999999';
    checkReceipt($verifier->verify($frame,$altered,$receiptKey,$now),'RECEIPT_CONTEXT_DENIED');
}
$other=[...$frame,'submission_reference'=>'ffffffff-ffff-4fff-8fff-ffffffffffff'];
checkReceipt($verifier->verify($other,$receipt,$receiptKey,$now),'RECEIPT_CONTEXT_DENIED');
echo "CRM F3-62 PASS: $checks independent MAC/freshness/context checks; never operational ACK.\n";
