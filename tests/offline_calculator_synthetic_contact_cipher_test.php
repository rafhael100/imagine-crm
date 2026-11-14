<?php
declare(strict_types=1);

require_once __DIR__.'/../app/Core/Leads/SyntheticCalculatorContactBoundary.php';
require_once __DIR__.'/../app/Core/Leads/SyntheticCalculatorContactCipher.php';
use App\Core\Leads\SyntheticCalculatorContactCipher as Cipher;

function passCipher(bool $condition,string $message): void{
    if(!$condition)throw new RuntimeException('Synthetic contact crypto test: '.$message);
}
function denyCipher(callable $fn,string $message): void{
    try{$fn();}catch(InvalidArgumentException){return;}
    throw new RuntimeException('Expected crypto rejection: '.$message);
}
$tenant='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$submission='123e4567-e89b-42d3-a456-426614174000';
$otherTenant='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$keyId='lab-key-rotation-01';
$fixtureKey='IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$key=random_bytes(32);
$record=[
  'submission_id'=>$submission,
  'contact_name'=>'Sample Customer',
  'contact_email'=>'client@example.test',
  'report_requested'=>true,
  'marketing_requested'=>false,
  'privacy_notice_version'=>'lab-notice-v1'
];
$sealed=Cipher::seal($record,$fixtureKey,$tenant,$submission,$keyId,$key);
$encoded=json_encode($sealed,JSON_THROW_ON_ERROR);
passCipher(!str_contains($encoded,'Sample Customer'),'name redacted in envelope');
passCipher(!str_contains($encoded,'client@example.test'),'email redacted in envelope');
foreach (['production_authorized','contact_identity_verified',
  'marketing_consent_proven','can_send_report','can_send_marketing'] as $flag)
  passCipher($sealed[$flag]===false,'deny '.$flag);
$opened=Cipher::openFixture($sealed,$tenant,$submission,$keyId,$key);
passCipher($opened['contact_name']==='Sample Customer','fixture name private recovery');
passCipher($opened['contact_email']==='client@example.test','fixture email private recovery');
$sealedAgain=Cipher::seal($record,$fixtureKey,$tenant,$submission,$keyId,$key);
passCipher($sealedAgain['nonce_b64']!==$sealed['nonce_b64'],'fresh random nonce');
passCipher($sealedAgain['ciphertext_b64']!==$sealed['ciphertext_b64'],'distinct ciphertext');
denyCipher(fn()=>Cipher::openFixture($sealed,$otherTenant,$submission,$keyId,$key),'tenant binding');
denyCipher(fn()=>Cipher::openFixture($sealed,$tenant,
 '33333333-3333-4333-8333-333333333333',$keyId,$key),'submission binding');
denyCipher(fn()=>Cipher::openFixture($sealed,$tenant,$submission,'lab-key-rotation-02',$key),
  'key rotation identifier');
denyCipher(fn()=>Cipher::openFixture($sealed,$tenant,$submission,$keyId,random_bytes(32)),
  'wrong key');
$bad=$sealed;$bad['tag_b64']=base64_encode(random_bytes(16));
denyCipher(fn()=>Cipher::openFixture($bad,$tenant,$submission,$keyId,$key),'tag manipulation');
$bad=$sealed;$bad['nonce_b64']=base64_encode(random_bytes(12));
denyCipher(fn()=>Cipher::openFixture($bad,$tenant,$submission,$keyId,$key),'nonce manipulation');
$bad=$sealed;$bad['ciphertext_b64']=base64_encode(random_bytes(45));
denyCipher(fn()=>Cipher::openFixture($bad,$tenant,$submission,$keyId,$key),'ciphertext manipulation');
$bad=$sealed;$bad['production_authorized']=true;
denyCipher(fn()=>Cipher::openFixture($bad,$tenant,$submission,$keyId,$key),'forged release flag');
$bad=$sealed;$bad['consent_proven']=true;
denyCipher(fn()=>Cipher::openFixture($bad,$tenant,$submission,$keyId,$key),'forged consent field');
$bad=$sealed;$bad['ciphertext_b64']='@@@';
denyCipher(fn()=>Cipher::openFixture($bad,$tenant,$submission,$keyId,$key),'invalid encoding');
$bad=$record;$bad['contact_email']='client@gmail.com';
denyCipher(fn()=>Cipher::seal($bad,$fixtureKey,$tenant,$submission,$keyId,$key),'real email cannot be sealed');
$bad=$record;$bad['submission_id']='33333333-3333-4333-8333-333333333333';
denyCipher(fn()=>Cipher::seal($bad,$fixtureKey,$tenant,$submission,$keyId,$key),'cross-submission fixture');
echo "PASS: fresh AES-256-GCM synthetic contact encryption, tenant AAD, tampering/key mismatch, all effects disabled, NO real PII\n";
